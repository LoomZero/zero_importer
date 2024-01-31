<?php

namespace Drupal\zero_importer\Base\Importer;

use Drupal;
use Drupal\zero_importer\Base\Row\ZImportRowInterface;
use Drupal\zero_importer\Exception\ZImportPrepareException;
use Drupal\zero_importer\Exception\ZImportSkipException;
use Drupal\zero_util\Data\DataArray;
use Throwable;

class ZImporterChild {

  private ZImportRowInterface $row;
  private string $entityType;
  private ?string $entityBundle;
  private array $options;
  private $prepareDefinition = NULL;
  private $findDefinition;
  private $fillDefinition;
  private bool $alwaysFill = FALSE;
  private $catch = NULL;
  private $multiple = FALSE;
  private $create_entity = FALSE;

  public function __construct(ZImportRowInterface $row, string $entity_type, string $entity_bundle = NULL, array $options = []) {
    $this->row = $row;
    $this->entityType = $entity_type;
    $this->entityBundle = $entity_bundle;
    $this->options = $options;
  }

  public function getEntityType(): string {
    return $this->entityType;
  }

  public function getEntityBundle(): string {
    return $this->entityBundle;
  }

  public function getOptions(): array {
    return $this->options;
  }

  public function prepare(callable $prepareDefinition): self {
    $this->prepareDefinition = $prepareDefinition;
    return $this;
  }

  /**
   * @param callable|array $findDefinition
   *
   * @return $this
   */
  public function find($findDefinition): self {
    $this->findDefinition = $findDefinition;
    return $this;
  }

  /**
   * @param callable $createDefinition
   *
   * @return $this
   */
  public function create(callable $fillDefinition = NULL): self {
    $this->fillDefinition = $fillDefinition;
    $this->create_entity = TRUE;
    return $this;
  }

  public function fill(callable $fillDefinition): self {
    $this->fillDefinition = $fillDefinition;
    $this->alwaysFill = TRUE;
    return $this;
  }

  public function catch(callable $catch): self {
    $this->catch = $catch;
    return $this;
  }

  /**
   * @param $value
   */
  public function replace($value, ZImportRowInterface $row = NULL) {
    if ($value === NULL) return NULL;
    if ($row === NULL) $row = $this->row;
    return DataArray::replaceAll($value, function(string $value, string $match, string $root) use ($row): string {
      $parts = explode('.', $match);
      if ($parts[0] === '@DEF') {
        switch ($parts[1]) {
          case 'keys':
            $definition = Drupal::entityTypeManager()->getDefinition(($parts[2] === '_' ? $this->entityType : $parts[2]));
            return $definition->getKey($parts[3]);
          case 'bundle':
            return $this->replace($this->entityBundle, $row);
        }
      }
      return $row->replace($value, $match, $root);
    });
  }

  public function multiple($multiple = TRUE): self {
    $this->multiple = $multiple;
    return $this;
  }

  public function execute() {
    $results = $this->row->each(function($index, ZImportRowInterface $row) {
      try {
        $prepareDefinition = $this->prepareDefinition;
        if ($prepareDefinition !== NULL) {
          $row = $prepareDefinition($this, $this->row->getImporter()->getSource(), $row);
          if (!$row instanceof ZImportRowInterface) {
            $row = $this->row->getImporter()->createRow($row);
          }
        }
        $findDefinition = $this->findDefinition;
        if (is_callable($findDefinition)) {
          $findDefinition = $findDefinition($this, $row);
        }
        $findDefinition = $this->replace($findDefinition, $row);

        $storage = Drupal::entityTypeManager()->getStorage($this->entityType);
        $entities = $storage->loadByProperties($findDefinition);

        $entity = NULL;
        if (count($entities) > 1) {
          throw new ZImportPrepareException('Loading resulted in more than 1 entry. Entity Type: ' . $this->entityType . '; Keys: ' . print_r($findDefinition, TRUE) . '; Found: ' . implode(', ', array_keys($entities)));
        } else if (count($entities) > 0) {
          $entity = array_shift($entities);
        }

        if (($entity === NULL || $this->alwaysFill) && $this->create_entity) {
          if ($entity === NULL) {
            $findDefinition ??= [];
            if ($this->entityBundle !== NULL) {
              $findDefinition[Drupal::entityTypeManager()->getDefinition($this->entityType)->getKey('bundle')] = $this->entityBundle;
            }
            $entity = $storage->create($findDefinition);
          }
          $fillDefinition = $this->fillDefinition;
          if ($fillDefinition) $fillDefinition($this, $entity, $row);
          $entity->save();
        }
        return $entity;
      } catch (Throwable $e) {
        if (is_callable($this->catch)) {
          return ($this->catch)($e, $this);
        }
        if ($e instanceof ZImportSkipException) return NULL;
        throw $e;
      }
    });
    if ($this->multiple) {
      return $results;
    } else if (count($results)) {
      return array_shift($results);
    } else {
      return NULL;
    }
  }

}

<?php

namespace Drupal\zero_importer\Base\Importer;

use Drupal;
use Drupal\zero_importer\Base\Row\ZImportRowInterface;
use Drupal\zero_importer\Exception\ZImportPrepareException;
use Drupal\zero_util\Data\DataArray;

/*
 * return $value->child('paragraph', 'image')->find(['field_source_id' => 'id'])->create(function($entity, $row) {
        $entity->set('field_src', $row->get('src')->value());
      })->execute();
 */

class ZImporterChild {

  private ZImportRowInterface $row;
  private string $entityType;
  private string $entityBundle;
  private array $options;
  private $findDefinition;
  private $fillDefinition;
  private bool $alwaysFill = FALSE;

  public function __construct(ZImportRowInterface $row, string $entity_type, string $entity_bundle, array $options = []) {
    $this->row = $row;
    $this->entityType = $entity_type;
    $this->entityBundle = $entity_bundle;
    $this->options = $options;
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
  public function create(callable $fillDefinition): self {
    $this->fillDefinition = $fillDefinition;
    return $this;
  }

  public function fill(callable $fillDefinition): self {
    $this->fillDefinition = $fillDefinition;
    $this->alwaysFill = TRUE;
    return $this;
  }

  /**
   * @param $value
   */
  public function replace($value) {
    if ($value === NULL) return NULL;
    return DataArray::replaceAll($value, function(string $value, string $match, string $root): string {
      $parts = explode('.', $match);
      if ($parts[0] === '@DEF') {
        switch ($parts[1]) {
          case 'keys':
            $definition = Drupal::entityTypeManager()->getDefinition(($parts[2] === '_' ? $this->entityType : $parts[2]));
            return $definition->getKey($parts[3]);
          case 'bundle':
            return $this->replace($this->entityBundle);
        }
      }
      return $this->row->replace($value, $match, $root);
    });
  }

  public function execute() {
    $findDefinition = $this->findDefinition;
    if (is_callable($findDefinition)) {
      $findDefinition = $findDefinition($this, $this->row);
    }
    $findDefinition = $this->replace($findDefinition);

    $storage = Drupal::entityTypeManager()->getStorage($this->entityType);
    $entities = $storage->loadByProperties($findDefinition);

    $entity = NULL;
    if (count($entities) > 1) {
      throw new ZImportPrepareException('Loading resulted in more than 1 entry.');
    } else if (count($entities) > 0) {
      $entity = array_shift($entities);
    }

    if ($entity === NULL || $this->alwaysFill) {
      if ($entity === NULL) {
        $findDefinition ??= [];
        $findDefinition[Drupal::entityTypeManager()->getDefinition($this->entityType)->getKey('bundle')] = $this->entityBundle;
        $entity = $storage->create($findDefinition);
      }
      $fillDefinition = $this->fillDefinition;
      $fillDefinition($entity, $this->row);
      $entity->save();
    }
    return $entity;
  }

}

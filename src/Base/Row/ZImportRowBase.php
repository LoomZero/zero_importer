<?php

namespace Drupal\zero_importer\Base\Row;

use Drupal;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\user\RoleInterface;
use Drupal\zero_importer\Base\Importer\ZImporterChild;
use Drupal\zero_importer\Base\Importer\ZImporterInterface;
use Drupal\zero_importer\Base\Source\ZImporterSourceInterface;
use Drupal\zero_importer\Exception\ZImportSkipException;
use Drupal\zero_importer\Import\Row\D7ImportRow;
use Drupal\zero_importer\Import\Source\D7ImportSource;
use Drupal\zero_util\Data\DataArray;

class ZImportRowBase implements ZImportRowInterface {

  private ZImporterInterface $importer;
  protected $data;

  public function __construct(ZImporterInterface $importer, $data) {
    $this->importer = $importer;
    $this->data = $data;
  }

  public function getImporter(): ZImporterInterface {
    return $this->importer;
  }

  public function getData() {
    return $this->data;
  }

  public function replace(string $value, string $match, string $root): string {
    if ($match === '@') return $this->getData();
    return $this->raw($match) ?? '';
  }

  public function get($key = NULL, $fallback = NULL, array $context = []): static {
    return new static($this->getImporter(), $this->raw($key, $fallback, $context));
  }

  public function raw($key = NULL, $fallback = NULL, array $context = []) {
    if ($key === NULL || $key === '@') return $this->getData();
    return DataArray::getNested($this->getData(), $key, $fallback);
  }

  public function has($key, array $context = []): bool {
    return DataArray::hasNested($this->getData(), $key, TRUE);
  }

  public function set($key, $value, array $context = []): self {
    $this->data = DataArray::setNested($this->getData(), $key, $value);
    return $this;
  }

  public function call(callable $call = NULL): static {
    if ($call === NULL) {
      return $this;
    } else {
      $result = $call($this);
      if ($result instanceof ZImportRowInterface) {
        return $result;
      } else {
        return $this->getImporter()->createRow($result);
      }
    }
  }

  public function map(callable $mapper = NULL): static {
    if ($mapper === NULL) return $this;
    $results = [];
    foreach ($this->array() as $index => $value) {
      $result = $mapper($index, $this->getImporter()->createRow($value));
      if ($result instanceof ZImportRowInterface) {
        $results[$index] = $result->value();
      } else {
        $results[$index] = $result;
      }
    }
    return $this->getImporter()->createRow($results);
  }

  public function each(callable $callback, $none_value = NULL): array {
    $results = [];
    foreach ($this->array() as $index => $value) {
      $result = $callback($index, $this->getImporter()->createRow($value));
      if ($result !== $none_value) {
        $results[] = $result;
      }
    }
    return $results;
  }

  public function filter(callable $filter): static {
    $results = [];
    foreach ($this->array() as $index => $value) {
      if ($filter($index, $value)) {
        $results[$index] = $value;
      }
    }
    return $this->getImporter()->createRow($results);
  }

  public function value(callable $getter = NULL) {
    if ($getter === NULL) {
      return $this->getData();
    }
    return $getter($this->getData(), $this);
  }

  public function array(): array {
    if (empty($this->getData())) return [];
    if (is_array($this->getData())) {
      return $this->getData();
    } else {
      return [$this->getData()];
    }
  }

  public function string(string $key = NULL, array $context = []): ?string {
    $value = $this->raw($key, NULL, $context);
    if ($value === NULL) return NULL;
    return (string)$value;
  }

  public function int(string $key = NULL, array $context = []): ?int {
    $value = $this->raw($key, NULL, $context);
    if ($value === NULL) return NULL;
    return (int)$value;
  }

  public function bool(string $key = NULL, array $context = []): ?bool {
    $value = $this->raw($key, NULL, $context);
    if ($value === NULL) return NULL;
    return (bool)$value;
  }

  public function fromImport(string $importer, callable $mapper = NULL): ?ContentEntityBase {
    /** @var \Drupal\zero_importer\Service\ZeroImporterPluginManager $manager */
    $manager = Drupal::service('plugin.manager.zero_importer');

    $mapping = $manager->getImporter($importer)->getMapper();
    return $mapping->find($this->map($mapper));
  }

  public function child(string $entity_type, string $entity_bundle = NULL): ZImporterChild {
    return new ZImporterChild($this, $entity_type, $entity_bundle);
  }

  public function toKey(string $key = NULL, array $context = []): string {
    $string = $this->string($key, $context);
    return strtolower(str_replace(' ', '_', $string));
  }

  public function toEntity(string $entity_type, array $options = []): ZImporterChild {
    $options += [
      'source_field' => '{{ @DEF.keys.' . $entity_type . '.id }}',
      'label_field' => '{{ @DEF.keys.' . $entity_type . '.label }}',
      'label_key' => FALSE,
      'create_entity' => FALSE,
      'id_key' => 'id',
    ];
    $child = $this->child($entity_type, $options['bundle'] ?? NULL);
    $child->find([
      $options['source_field'] => '{{ ' . $options['id_key'] . ' }}',
    ]);
    if ($options['create_entity']) {
      $child->create(function(ZImporterChild $child, $entity, ZImportRowInterface $row) use ($options) {
        $replaced = $child->replace($options, $row);
        $entity->set($replaced['source_field'], $row->raw($replaced['id_key']));
        if ($options['label_key']) $entity->set($replaced['label_field'], $row->raw($replaced['label_key']));
      });
    }
    return $child;
  }

  /**
   * @inheritDoc
   */
  public function toRoles(array $options = []): ZImporterChild {
    $options += [
      'id_key' => 'id',
      'create_entity' => FALSE,
      'label_key' => 'name',
    ];
    return $this->toEntity('user_role', $options)->multiple();
    /*
    return $this->each(function($index, $value) use ($options) {
      $child = $value->child('user_role')
        ->find([
          $options['source_field'] => '{{ ' . $options['id_field'] . ' }}',
        ]);
      if ($options['create_entity']) {
        $child->create(function(ZImporterChild $child, RoleInterface $role, ZImportRowInterface $row) use ($options) {
          $role->set('id', $row->raw($options['id_field']));
          $role->set('label', $row->raw($options['label_field']));
        });
      }
      return $child;
    });
    */
  }

  public function toTerms(string $category, array $options = []): ZImporterChild {
    $options += [
      'id_key' => '@',
      'label_key' => '@',
      'source_field' => 'name',
    ];
    return $this->toTargetTerms($category, $options);
  }

  public function toTargetTerms(string $category, array $options = []): ZImporterChild {
    $options += [
      'id_key' => 'tid',
      'label_key' => 'name',
      'create_entity' => FALSE,
      'source_field' => 'field_source_id',
      'bundle' => $category,
    ];
    return $this->toEntity('taxonomy_term', $options)->multiple();
  }

}

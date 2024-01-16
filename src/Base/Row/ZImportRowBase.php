<?php

namespace Drupal\zero_importer\Base\Row;

use Drupal;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\zero_importer\Base\Importer\ZImporterChild;
use Drupal\zero_importer\Base\Importer\ZImporterInterface;
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
    return $this->raw($match) ?? '';
  }

  public function get($key, $fallback = NULL, array $context = []): static {
    return new static($this->getImporter(), $this->raw($key, $fallback, $context));
  }

  public function raw($key, $fallback = NULL, array $context = []) {
    return DataArray::getNested($this->getData(), $key, $fallback);
  }

  public function has($key, array $context = []): bool {
    return DataArray::hasNested($this->getData(), $key, TRUE);
  }

  public function set($key, $value, array $context = []): self {
    $this->data = DataArray::setNested($this->getData(), $key, $value);
    return $this;
  }

  public function map(callable $mapper = NULL): static {
    if ($mapper === NULL) {
      return $this;
    } else {
      $result = $mapper($this);
      if ($result instanceof ZImportRowInterface) {
        return $result;
      } else {
        return new static($this->getImporter(), $result);
      }
    }
  }

  public function each(callable $callback, $none_value = NULL): array {
    $results = [];
    foreach ($this->getData() as $index => $value) {
      $result = $callback($index, $this->get($index));
      if ($result !== $none_value) {
        $results[] = $result;
      }
    }
    return $results;
  }

  public function value() {
    return $this->getData();
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

  public function child(string $entity_type, string $entity_bundle): ZImporterChild {
    return new ZImporterChild($this, $entity_type, $entity_bundle);
  }

}

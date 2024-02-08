<?php

namespace Drupal\zero_importer\Base\Mapper;

use Drupal\zero_importer\Base\Row\ZImportRowInterface;
use Drupal\zero_importer\Exception\ZImportPrepareException;
use Drupal\zero_importer\Info\ZImportEntity;
use Drupal\zero_importer\Info\ZImportPlaceholder;

class ZImportFieldsMapper extends ZImportMapperBase {

  private string $concat = ':';
  private string $source_field;
  private array $mapping = [];
  private bool $no_register = FALSE;

  public function setSourceField(string $field): self {
    $this->source_field = $field;
    return $this;
  }

  public function getSourceField(): string {
    return $this->source_field;
  }

  public function setConcat(string $concat): self {
    $this->concat = $concat;
    return $this;
  }

  public function getConcat(): string {
    return $this->concat;
  }

  /**
   * @param string|callable|ZImportPlaceholder $rowField
   *
   * @return $this
   */
  public function addKey($rowField): self {
    $this->mapping[] = $rowField;
    return $this;
  }

  public function setNoRegister($no_register = TRUE): self {
    $this->no_register = $no_register;
    return $this;
  }

  public function getMapping(ZImportRowInterface $row): array {
    $mapping = [];
    foreach ($this->mapping as $index => $value) {
      if (is_callable($value)) {
        $mapping[$index] = $value($row);
      } else if ($value instanceof ZImportPlaceholder) {
        $mapping[$index] = $value->toPlaceholder();
      } else {
        $mapping[$index] = $value;
      }
    }
    return $mapping;
  }

  public function getKey(ZImportRowInterface $row): string {
    $props = $this->getImporter()->replacer($this->getMapping($row), $row);
    return implode($this->getConcat(), $props);
  }

  /**
   * @inheritDoc
   */
  public function find(ZImportRowInterface $row) {
    $key = $this->getKey($row);
    $entities = $this->getImporter()->getEntityStorage()->loadByProperties([
      $this->getSourceField() => $key,
    ]);
    if (count($entities) > 1) {
      throw new ZImportPrepareException('The loading of key "' . $key . '" resulted in more than 1 entry.');
    } else if (count($entities) > 0) {
      return array_shift($entities);
    }
    return NULL;
  }

  /**
   * @inheritDoc
   */
  public function register(ZImportEntity $entity, ZImportRowInterface $row) {
    if ($this->no_register) return;
    $entity->set($this->getSourceField(), $this->getKey($row));
  }

  public function info(): array {
    return [
      'Source Field' => $this->getSourceField(),
      'Keys' => implode(', ', $this->mapping),
      'Concat' => $this->getConcat(),
    ];
  }

}

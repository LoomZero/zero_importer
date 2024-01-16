<?php

namespace Drupal\zero_importer\Base\Mapper;

use Drupal\zero_importer\Base\Row\ZImportRowInterface;
use Drupal\zero_importer\Info\ZImportEntity;

class ZimportFieldMapper extends ZImportMapperBase {

  private array $mapping = [];
  private bool $no_register = FALSE;

  /**
   * @param string $field
   * @param string|callable $rowField
   * @return $this
   */
  public function addKey(string $field, $rowField): self {
    $this->mapping[$field] = $rowField;
    return $this;
  }

  public function setNoRegister($no_register = TRUE): self {
    $this->no_register = $no_register;
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function find(ZImportRowInterface $row) {
    $props = $this->getImporter()->replacer($this->mapping, $row);
    $entities = $this->getImporter()->getEntityStorage()->loadByProperties($props);
    if (count($entities) > 0) {
      return array_shift($entities);
    }
    return NULL;
  }

  /**
   * @inheritDoc
   */
  public function register(ZImportEntity $entity, ZImportRowInterface $row) {
    if ($this->no_register) return;
    $props = $this->getImporter()->replacer($this->mapping);
    foreach ($props as $key => $value) {
      $entity->set($key, $value);
    }
  }

}

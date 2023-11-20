<?php

namespace Drupal\zero_importer\Info;

use Drupal\Core\Entity\ContentEntityBase;

/**
 * @template T_Entity of ContentEntityBase
 */
class ZImportEntity {

  private ContentEntityBase $entity;

  /**
   * @param T_Entity $entity
   */
  public function __construct($entity) {
    $this->entity = $entity;
  }

  /**
   * @return T_Entity
   */
  public function entity() {
    return $this->entity;
  }

  public function set(string $field, mixed $value): self {
    $this->entity->set($field, $value);
    return $this;
  }

}

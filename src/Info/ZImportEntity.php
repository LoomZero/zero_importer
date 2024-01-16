<?php

namespace Drupal\zero_importer\Info;

use Drupal\Core\Entity\ContentEntityBase;

/**
 * @template TEntity of ContentEntityBase
 */
class ZImportEntity {

  private ContentEntityBase $entity;

  /**
   * @param TEntity $entity
   * @return ZImportEntity<TEntity>
   */
  public static function create($entity): ZImportEntity {
    if ($entity instanceof self) return $entity;
    return new ZImportEntity($entity);
  }

  /**
   * @param TEntity $entity
   */
  public function __construct($entity) {
    $this->entity = $entity;
  }

  /**
   * @return TEntity
   */
  public function entity() {
    return $this->entity;
  }

  public function set(string $field, mixed $value): self {
    $this->entity->set($field, $value);
    return $this;
  }

}

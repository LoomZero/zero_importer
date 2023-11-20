<?php

class ImportEntity {

  private ContentEntityBase $entity;

  public function __construct(ContentEntityBase $entity) {
    $this->entity = $entity;
  }

  public function set(string $field, $value): self {
    $this->entity->set($field, $value);
    return $this;
  }

}
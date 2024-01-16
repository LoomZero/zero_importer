<?php

namespace Drupal\zero_importer\Info;

class ZImportPlaceholder {

  private array $fields = [];

  public static function get(string $field = NULL): ZImportPlaceholder {
    return new ZImportPlaceholder($field);
  }

  public function __construct(string $field = NULL) {
    $this->setFields($field);
  }

  public function def(string $field): self {
    return $this->setFields('@DEF.' . $field);
  }

  public function opts(string $field): self {
    return $this->setFields('@OPTS.' . $field);
  }

  public function bundle(): self {
    return $this->def('bundle');
  }

  public function setFields(string $field = NULL): self {
    if ($field === NULL) {
      $this->fields = [];
    } else {
      $this->fields = explode('.', $field);
    }
    return $this;
  }

  public function addFields(string $field): self {
    foreach (explode('.', $field) as $item) {
      $this->fields[] = $item;
    }
    return $this;
  }

  public function toPlaceholder(): string {
    return '{{ ' . implode('.', $this->fields) . ' }}';
  }

}

<?php

namespace Drupal\zero_importer\Import\Row;

use Drupal\user\RoleInterface;
use Drupal\zero_importer\Base\Row\ZImportRowBase;
use Drupal\zero_importer\Base\Row\ZImportRowInterface;

class D7ImportRow extends ZImportRowBase {

  public function get($key = NULL, $fallback = NULL, array $context = []): static {
    if (parent::has($key . '.und', $context)) {
      return parent::get($key . '.und', $fallback, $context);
    } else {
      return parent::get($key, $fallback, $context);
    }
  }

  /**
   * Get roles and create them if in standard array from zero_exporter.
   *
   * @return RoleInterface[]
   */
  public function getRoles(bool $create = FALSE): array {
    return $this->get('roles')
      ->filter(function($index, $value) {
        return !in_array($index, [0, 1, 2]);
      })
      ->map(function($index, ZImportRowInterface $value) {
        return [
          'id' => $index,
          'role' => $value->toKey(),
          'label' => $value->value(),
        ];
      })
      ->toRoles([
        'id_key' => 'role',
        'label_key' => 'label',
        'create_entity' => $create,
      ])->execute();
  }

  public function attr(string $attr, int $index = NULL) {
    if ($index === NULL) {
      $items = [];
      foreach ($this->array() as $value) {
        $items[] = $value[$attr] ?? NULL;
      }
      return $items;
    } else {
      return $this->array()[$index][$attr] ?? NULL;
    }
  }

  public function textfield(int $index = NULL) {
    return $this->attr('safe_value', $index);
  }

  public function email(int $index = NULL) {
    return $this->attr('email', $index);
  }

}

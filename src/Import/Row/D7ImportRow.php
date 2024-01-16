<?php

namespace Drupal\zero_importer\Import\Row;

use Drupal\zero_importer\Base\Row\ZImportRowBase;

class D7ImportRow extends ZImportRowBase {

  public function get($key, $fallback = NULL, array $context = []): static {
    if (parent::has($key . '.und', $context)) {
      return parent::get($key . '.und', $fallback, $context);
    } else {
      return parent::get($key, $fallback, $context);
    }
  }

}

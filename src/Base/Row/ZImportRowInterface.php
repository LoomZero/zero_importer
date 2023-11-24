<?php

namespace Drupal\zero_importer\Base\Row;

interface ZImportRowInterface {

  public function replace(string $value, string $match, string $root): string;

  public function get($key, $fallback = NULL, array $context = []);

}

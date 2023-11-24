<?php

namespace Drupal\zero_importer\Base\Row;

use Drupal\zero_importer\Base\Source\ZImporterSourceInterface;
use Drupal\zero_util\Data\DataArray;

class ZImportRowBase implements ZImportRowInterface {

  protected ZImporterSourceInterface $source;
  protected $value;

  public function __construct(ZImporterSourceInterface $source, $value) {
    $this->source = $source;
    $this->value = $value;
  }

  public function replace(string $value, string $match, string $root): string {
    return $this->get($match) ?? '';
  }

  public function get($key, $fallback = NULL, array $context = []) {
    return DataArray::getNested($this->value, $key, $fallback);
  }

  public function set($key, $value, array $context = []): self {
    $this->value = DataArray::setNested($this->value, $key, $value);
    return $this;
  }

}

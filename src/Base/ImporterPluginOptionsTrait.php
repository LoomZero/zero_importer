<?php

namespace Drupal\zero_importer\Base;

use Drupal\zero_util\Data\DataArray;

trait ImporterPluginOptionsTrait {

  protected array $options = [];

  public function setOptions(array $options = []): self {
    $this->options = $options;
    return $this;
  }

  public function option(string $key) {
    return DataArray::getNested($this->options, $key);
  }

  public function hasOption(string $key, bool $allowNULL = FALSE): bool {
    return DataArray::hasNested($this->options, $key, $allowNULL);
  }

  public function getOptions(): array {
    return $this->options;
  }

}

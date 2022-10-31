<?php

namespace Drupal\zero_importer\Base;

interface ImporterPluginOptionsInterface {

  public function setOptions(array $options = []): self;

  public function option(string $key);

  public function hasOption(string $key, bool $allowNULL = FALSE): bool;

  public function getOptions(): array;

}

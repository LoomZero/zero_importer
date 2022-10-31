<?php

namespace Drupal\zero_importer\Base;

interface ImporterPluginLoggerInterface {

  /**
   * @param string $key
   * @param array $options = [
   *     'placeholders' => ['placeholder_1' => 'value'],
   *     'prompts' => '[IMPORTER] ',
   *     'prefix' => '',
   *     'suffix' => '',
   * ]
   *
   * @return void
   */
  public function log(string $key, array $options = []): void;

}

<?php

namespace Drupal\zero_importer\Base;

use Drupal\zero_importer\Base\Importer\ZeroImporterInterface;

trait ImporterPluginLoggerTrait {

  /**
   * @param string $key
   * @param array $options = [
   *     'type' => 'log|note|warning|error',
   *     'message' => 'The log message',
   *     'placeholders' => ['placeholder_1' => 'value'],
   *     'prompts' => '[IMPORTER] ',
   *     'prefix' => '',
   *     'suffix' => '',
   * ]
   *
   * @return void
   */
  public function log(string $key, array $options = []): void {
    if ($this instanceof ZeroImporterInterface) {
      $logger = $this->annotation()['logger'] ?? [];
    } else {
      $logger = $this->option('logger');
    }
    if (isset($logger[$key]) && $logger[$key] === FALSE) return;

    $logger = $logger[$key] ?? [];
    if (is_string($logger)) {
      $logger = [
        'message' => $logger,
      ];
    }
    $logger = array_replace_recursive($options, $logger);
    if (isset($logger['message'])) {
      if ($this instanceof ZeroImporterInterface) {
        $logger = $this->getLookup()->replace($logger, NULL, NULL, FALSE);
        $this->logger()->{$logger['type'] ?? 'log'}($logger['message'], $logger);
      } else {
        $logger = $this->importer()->getLookup()->replace($logger, NULL, NULL, FALSE);
        $this->importer()->logger()->{$logger['type'] ?? 'log'}($logger['message'], $logger);
      }
    }
  }

}

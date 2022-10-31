<?php

namespace Drupal\zero_importer\Base\Remote;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\zero_importer\Base\ImporterPluginInterface;
use Drupal\zero_importer\Base\ImporterPluginLoggerInterface;
use Drupal\zero_importer\Base\ImporterPluginOptionsInterface;
use GuzzleHttp\Psr7\Uri;

interface ZeroImporterRemoteInterface extends PluginInspectionInterface, ImporterPluginInterface, ImporterPluginOptionsInterface, ImporterPluginLoggerInterface {

  public function request(string|Uri $path = NULL, array $options = [], string $method = 'get');

  public function getJSON(string|Uri $path = NULL, array $options = [], string $method = 'get');

}

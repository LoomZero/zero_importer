<?php

namespace Drupal\zero_importer\Base\Source;

use GuzzleHttp\Psr7\Uri;

abstract class ZeroImporterRemoteSourceBase extends ZeroImporterSourceBase {

  public function request(string $key, string|Uri $path = NULL, array $options = [], string $method = 'get') {
    $ops = $this->getOptions();
    $uri = $path ?? $ops[$key]['remote']['url'] ?? $ops[$key]['remote'] ?? NULL;
    $options = array_replace_recursive($ops[$key]['remote']['options'] ?? [], $options);
    return $this->importer()->remote()->request($uri, $options, $method);
  }

  public function getJSON(string $key, string|Uri $path = NULL, array $options = [], string $method = 'get') {
    $ops = $this->getOptions();
    $uri = $path ?? $ops[$key]['remote']['url'] ?? $ops[$key]['remote'] ?? NULL;
    $options = array_replace_recursive($ops[$key]['remote']['options'] ?? [], $options);
    return $this->importer()->remote()->getJSON($uri, $options, $method);
  }

}

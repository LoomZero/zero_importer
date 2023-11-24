<?php

namespace Drupal\zero_importer\Base\Source;

use Drupal\zero_importer\Exception\ZImportRemoteException;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Uri;

abstract class ZImporterRemoteSourceBase extends ZImporterSourceBase {

  protected ?Client $client = NULL;
  protected array $client_options = [];
  protected array $options = [
    'batch_size' => 1000,
  ];

  public function isRemoteSource(): bool {
    return TRUE;
  }

  public function setRemoteOption(string $key, $value): self {
    $this->options[$key] = $value;
    return $this;
  }

  public function setBatchSize(int $batch_size = 1000): self {
    return $this->setRemoteOption('batch_size', $batch_size);
  }

  protected function prepareRemoteOptions(array $options): array {
    return $options;
  }

  public function getClient(): Client {
    if ($this->client === NULL) {
      $this->client = new Client($this->client_options);
    }
    return $this->client;
  }

  public function setBaseOption(string $key, $value): self {
    $this->client = NULL;
    $this->client_options[$key] = $value;
    return $this;
  }

  public function setBaseUrl(string|Uri $path): self {
    return $this->setBaseOption('base_uri', $this->getURI($path));
  }

  public function getURI(string|Uri $path): Uri {
    if ($path instanceof Uri) return $path;
    return new Uri($path);
  }

  public function request(string|Uri $path = NULL, array $options = [], string $method = 'get') {
    return $this->getClient()->request($method, $this->getURI($path), $options);
  }

  public function getJSON(string|Uri $path = NULL, array $options = [], string $method = 'get') {
    $response = $this->request($path, $options, $method);

    if ($response->getStatusCode() !== 200) {
      throw new ZImportRemoteException('Invalid response status code: ' . $response->getStatusCode());
    } else if (!in_array('application/json', $response->getHeader('Content-Type'))) {
      throw new ZImportRemoteException('Invalid response header Content-Type: ' . implode(', ', $response->getHeader('Content-Type')));
    }
    return json_decode($response->getBody()->getContents(), TRUE);
  }

}

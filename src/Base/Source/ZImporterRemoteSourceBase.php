<?php

namespace Drupal\zero_importer\Base\Source;

use Drupal\zero_importer\Base\Auth\ZImportAuthInterface;
use Drupal\zero_importer\Exception\ZImportRemoteException;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Uri;

abstract class ZImporterRemoteSourceBase extends ZImporterSourceBase {

  protected ?Client $client = NULL;
  protected array $client_options = [];
  protected array $options = [
    'batch_size' => 1000,
  ];
  protected ZImportAuthInterface $auth;

  public function isRemoteSource(): bool {
    return TRUE;
  }

  public function setRemoteOption(string $key, $value): self {
    $this->options[$key] = $value;
    return $this;
  }

  public function setBatchSize($batch_size = 1000): self {
    return $this->setRemoteOption('batch_size', $batch_size);
  }

  public function getClientOptions(): array {
    return $this->client_options;
  }

  public function setClientOptions(array $options): self {
    $this->client = NULL;
    $this->client_options = $options;
    return $this;
  }

  protected function prepareRemoteOptions(array $options): array {
    return $options;
  }

  public function setAuth(ZImportAuthInterface $auth): self {
    $this->auth = $auth;
    $auth->setSource($this);
    return $this;
  }

  public function setClient(Client $client): self {
    $this->client = $client;
    return $this;
  }

  public function getClient(): Client {
    if ($this->client === NULL) {
      if ($this->auth) $this->auth->onInit();
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

  public function getRequestOptions(array $options = []): array {
    return $options;
  }

  public function request(string|Uri $path = NULL, array $options = [], string $method = 'get') {
    if ($this->auth) $this->auth->onRequest($path, $options, $method);
    return $this->getClient()->request($method, $this->getURI($path), $this->getRequestOptions($options));
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

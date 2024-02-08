<?php

namespace Drupal\zero_importer\Base\Source;

use Drupal\zero_importer\Base\Auth\ZImportAuthInterface;
use Drupal\zero_importer\Exception\ZImportRemoteException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Client\ClientExceptionInterface;

abstract class ZImporterRemoteSourceBase extends ZImporterSourceBase {

  protected ?Client $client = NULL;
  protected array $client_options = [];
  protected array $options = [
    'batch_size' => 1000,
  ];
  protected ?ZImportAuthInterface $auth = NULL;

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
    $options = $this->client_options;
    $options['base_uri'] = $this->getBaseUrl();
    return $options;
  }

  public function setClientOptions(array $options): self {
    $this->client = NULL;
    $this->client_options = $options;
    return $this;
  }

  public function setAuth(ZImportAuthInterface $auth = NULL): self {
    $this->auth = $auth;
    $auth->setSource($this);
    return $this;
  }

  public function getAuth(): ?ZImportAuthInterface {
    return $this->auth;
  }

  public function setClient(Client $client): self {
    $this->client = $client;
    return $this;
  }

  public function getClient(): Client {
    if ($this->client === NULL) {
      if ($this->auth) $this->auth->onInit();
      $this->client = new Client($this->getClientOptions());
    }
    return $this->client;
  }

  public function setClientOption(string $key, $value): self {
    $this->client = NULL;
    $this->client_options[$key] = $value;
    return $this;
  }

  public function getClientOption(string $key) {
    return $this->getImporter()->replacer($this->client_options[$key] ?? NULL);
  }

  public function setBaseUrl(string|Uri $path): self {
    return $this->setClientOption('base_uri', $path);
  }

  public function getBaseUrl(): Uri {
    return $this->getURI($this->getClientOption('base_uri'));
  }

  public function getURI(string|Uri $path = NULL): ?Uri {
    if ($path === NULL) return NULL;
    if ($path instanceof Uri) return $path;
    return new Uri($path);
  }

  public function getRequestOptions(array $options = []): array {
    return $this->getImporter()->replacer($options);
  }

  public function request(string|Uri $path = NULL, array $options = [], string $method = 'get') {
    if ($this->auth) $this->auth->onRequest($path, $options, $method);
    try {
      return $this->getClient()->request($method, $this->getURI($path), $this->getRequestOptions($options));
    } catch (ClientExceptionInterface $exception) {
      throw new ZImportRemoteException('Guzzle exception: ' . $exception->getMessage(), $exception->getCode(), $exception);
    }
  }

  public function getHttp(string|Uri $path = NULL, array $options = [], string $method = 'get') {
    $response = $this->request($path, $options, $method);

    if ($response->getStatusCode() !== 200) {
      throw new ZImportRemoteException('Invalid response status code: ' . $response->getStatusCode());
    }
    return $response;
  }

  public function getJSON(string|Uri $path = NULL, array $options = [], string $method = 'get') {
    $response = $this->getHttp($path, $options, $method);

    if (!in_array('application/json', $response->getHeader('Content-Type'))) {
      throw new ZImportRemoteException('Invalid response header Content-Type: ' . implode(', ', $response->getHeader('Content-Type')));
    }
    return json_decode($response->getBody()->getContents(), TRUE);
  }

  public function info(): array {
    $info = [
      'Base Url' => [$this->client_options['base_uri'], (string)$this->getBaseUrl()],
      'Batch Size' => $this->options['batch_size'],
    ];

    return $info;
  }

}

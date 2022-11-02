<?php

namespace Drupal\zero_importer\Plugin\Zero\Importer\Remote;

use Drupal\zero_importer\Annotation\ZeroImporterRemote;
use Drupal\zero_importer\Base\Remote\ZeroImporterRemoteBase;
use Drupal\zero_importer\Exception\ImporterRemoteException;
use Drupal\zero_importer\Exception\ImporterRemoteThrowable;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;

/**
 * @ZeroImporterRemote(
 *   id = "default.remote",
 * )
 */
class DefaultImporterRemote extends ZeroImporterRemoteBase {

  protected ?Client $client = NULL;

  public function getClient(): Client {
    if ($this->client === NULL) {
      $this->client = new Client($this->getURIOptions([
        'timeout' => 60,
        'verify' => FALSE,
        'base_uri' => $this->getURI(),
      ]));
    }
    return $this->client;
  }

  public function resolveURI(Uri $uri, array $options = []): Uri {
    $uri = UriResolver::resolve($this->getClient()->getConfig('base_uri'), $uri);
    if (isset($options['query'])) {
      // copy from \GuzzleHttp\Client::applyOptions():443
      $value = $options['query'];
      if (is_array($value)) {
        $value = http_build_query($value, null, '&', PHP_QUERY_RFC3986);
      }
      if (!is_string($value)) {
        throw new InvalidArgumentException('query must be a string or array');
      }
      $uri = $uri->withQuery($value);
    }
    if (isset($options['fragment'])) {
      $uri = $uri->withFragment($options['fragment']);
    }
    return $uri;
  }

  public function getURI(string $url = NULL): Uri {
    $url = $this->importer()->getLookup()->replace($url ?? $this->option('url'), $this->importer()->getCurrent());
    return new Uri($url);
  }

  public function getURIOptions(array $merge = []) {
    $options = array_replace_recursive($this->option('options') ?? [], $merge);
    return $this->importer()->getLookup()->replace($options, $this->importer()->getCurrent());
  }

  public function request(string|Uri $path = NULL, array $options = [], string $method = 'get'): ResponseInterface {
    $uri = $this->getURI($path);
    $options = $this->getURIOptions($options);
    $this->log('request', [
      'message' => '[REQUEST] {{ _method }} {{ url }}',
      'placeholders' => ['_method' => strtoupper($method), 'method' => $method, 'url' => $this->resolveURI($uri, $options), 'options' => $options]
    ]);
    try {
      $response = $this->getClient()->request($method, $uri, $options);
    } catch (ClientException $exception) {
      $response = $exception->getResponse();
      $message = [];
      $message[] = 'The response is not valid.';
      $data = json_decode($response->getBody()->getContents(), TRUE);
      if (isset($data['message'])) {
        $message[] = $response->getStatusCode() . ' ' . $response->getReasonPhrase() . ' with message: ' . $data['message'];
      }
      if (isset($options['importer']['exception']) && in_array(ImporterRemoteThrowable::class, class_implements($options['importer']['exception']))) {
        throw (new $options['importer']['exception'](implode(' ', $message), 0, $exception))->setRequestInfo($uri, $options, $method);
      } else {
        throw (new ImporterRemoteException(implode(' ', $message), 0, $exception))->setRequestInfo($uri, $options, $method);
      }
    }
    $this->log('response', [
      'placeholders' => ['_method' => strtoupper($method), 'method' => $method, 'url' => $this->resolveURI($uri, $options), 'options' => $options, 'status' => $response->getStatusCode()],
    ]);
    return $response;
  }

  public function getJSON(string|Uri $path = NULL, array $options = [], string $method = 'get') {
    $response = $this->request($path, $options, $method);

    if ($response->getStatusCode() !== 200) {
      throw new ImporterRemoteException('Invalid response status code: ' . $response->getStatusCode());
    } else if (!in_array('application/json', $response->getHeader('Content-Type'))) {
      throw new ImporterRemoteException('Invalid response header Content-Type: ' . implode(', ', $response->getHeader('Content-Type')));
    }
    return json_decode($response->getBody()->getContents(), TRUE);
  }

}

<?php

namespace Drupal\zero_importer\Import\Auth;

use Drupal\zero_importer\Base\Auth\ZImportAuthBase;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Psr7\Uri;

class D7CookieAuth extends ZImportAuthBase {

  public function setCredentials(string $username, string $password): self {
    $this->data['credentials'] = [
      'name' => $username,
      'pass' => $password,
    ];
    return $this;
  }

  public function getStatus(): string {
    return $this->data()['status'] ?? 'UNAUTHORIZED';
  }

  public function onRequest(Uri|string &$path = NULL, array &$options = [], string $method = 'get') {
    if ($this->getStatus() === 'UNAUTHORIZED') {
      $this->data['status'] = 'PENDING';
      $cookies = new CookieJar();
      $login_options = [
        'cookies' => $cookies,
        'json' => $this->data['credentials'],
        'query' => [
          '_format' => 'json',
        ],
      ];
      $response = $this->getSource()->request('/user/login', $login_options, 'post');
      $body = $response->getBody()->getContents();
      $a = 0;
    }
  }

}

<?php

namespace Drupal\zero_importer\Import\Auth;

use Drupal\zero_importer\Base\Auth\ZImportAuthBase;
use GuzzleHttp\Psr7\Uri;

class D7SimpleAuth extends ZImportAuthBase {

  public function setCredentials(string $username, string $password): self {
    $this->data['credentials'] = [
      'name' => $username,
      'pass' => $password,
    ];
    return $this;
  }

  public function onRequest(Uri|string &$path = NULL, array &$options = [], string $method = 'get') {
    $options['query']['_user'] = $this->data()['credentials']['name'];
    $options['query']['_pass'] = $this->data()['credentials']['pass'];
  }

}

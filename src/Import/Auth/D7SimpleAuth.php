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

  public function info(): array {
    $info = [
      'User' => [$this->data()['credentials']['name'], $this->getSource()->getImporter()->replacer($this->data()['credentials']['name'])],
    ];
    $pass = $this->data()['credentials']['pass'];
    if (str_starts_with($pass, '{{') && str_ends_with($pass, '}}')) {
      $info['Pass'] = $this->data()['credentials']['pass'];
    } else {
      $info['Pass'] = str_repeat('*', strlen($this->data()['credentials']['pass']));
    }
    return $info;
  }

}

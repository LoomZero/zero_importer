<?php

namespace Drupal\zero_importer\Base\Auth;

use Drupal\zero_importer\Base\Source\ZImporterRemoteSourceBase;
use GuzzleHttp\Psr7\Uri;

abstract class ZImportAuthBase implements ZImportAuthInterface {

  protected $source = NULL;
  protected $data = [];

  public static function create(): static {
    return new static();
  }

  public function setSource(ZImporterRemoteSourceBase $source): self {
    $this->source = $source;
    return $this;
  }

  public function getSource(): ?ZImporterRemoteSourceBase {
    return $this->source;
  }

  public function data() {
    return $this->data;
  }

  public function onInit() {}

  public function onRequest(Uri|string &$path = NULL, array &$options = [], string $method = 'get') {}

}

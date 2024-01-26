<?php

namespace Drupal\zero_importer\Base\Auth;

use Drupal\zero_importer\Base\Source\ZImporterRemoteSourceBase;
use GuzzleHttp\Psr7\Uri;

interface ZImportAuthInterface {

  public function setSource(ZImporterRemoteSourceBase $source): self;

  public function getSource(): ?ZImporterRemoteSourceBase;

  public function data();

  public function onInit();

  public function onRequest(string|Uri &$path = NULL, array &$options = [], string $method = 'get');

}

<?php

namespace Drupal\zero_importer\Import\Source;

use Drupal\zero_importer\Base\Row\ZImportRowBase;
use Drupal\zero_importer\Base\Row\ZImportRowInterface;
use Drupal\zero_importer\Base\Source\ZImporterRemoteSourceBase;
use GuzzleHttp\Psr7\Uri;

class D7ImportSource extends ZImporterRemoteSourceBase {

  protected function prepareRemoteOptions(array $options): array {
    $options = parent::prepareRemoteOptions($options);
    $options['query']['range'] = $this->options['batch_size'];
    return $options;
  }

  public function createRow($data, array $context = []): ZImportRowInterface {
    return new ZImportRowBase($this, $data);
  }

  public function getIndex($asBatch = TRUE, string|Uri $path = NULL, array $options = [], string $method = 'get') {
    $path ??= '/api/json/zero/exporter/index';
    $options = $this->prepareRemoteOptions($options);
    if ($asBatch) {
      $options['query']['batch'] = TRUE;
    }
    return $this->getJSON($path, $options, $method);
  }

  public function getItem($id, string $entity_type = NULL, string|Uri $path = NULL, array $options = [], string $method = 'get') {
    $path ??= '/api/json/zero/exporter/data';
    $options = $this->prepareRemoteOptions($options);
    $options['query']['entity'] = $entity_type ?? $this->getImporter()->getEntityType();
    $options['query']['id'] = $id;
    $response = $this->getJSON($path, $options, $method);
    return $response['entity'];
  }

}

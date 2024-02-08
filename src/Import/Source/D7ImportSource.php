<?php

namespace Drupal\zero_importer\Import\Source;

use Drupal\zero_importer\Base\Row\ZImportRowBase;
use Drupal\zero_importer\Base\Row\ZImportRowInterface;
use Drupal\zero_importer\Base\Source\ZImporterRemoteSourceBase;
use GuzzleHttp\Psr7\Uri;

class D7ImportSource extends ZImporterRemoteSourceBase {

  public const BATCH_ALL = 'all';

  public function getRequestOptions(array $options = []): array {
    if ($options['key'] === 'index') {
      $options['query']['range'] = $this->options['batch_size'];
      if (!empty($this->options['index_bundles'])) {
        $options['query']['conditions'][] = ['[bundle]', $this->options['index_bundles'], 'IN'];
      }
      if (!empty($this->options['index_id'])) {
        $options['query']['conditions'][] = ['[id]', $this->options['index_id']];
      }
    }
    return parent::getRequestOptions($options);
  }

  public function setIndexBundles(array $bundles): self {
    return $this->setRemoteOption('index_bundles', $bundles);
  }

  public function setIndexID($id): self {
    return $this->setRemoteOption('index_id', $id);
  }

  public function createRow($data, array $context = []): ZImportRowInterface {
    return new ZImportRowBase($this->getImporter(), $data);
  }

  public function getIndex($asBatch = TRUE, string|Uri $path = NULL, array $options = [], string $method = 'get') {
    $path ??= '/api/json/zero/exporter/index';
    $options['key'] = 'index';
    if ($asBatch) {
      $options['query']['batch'] = TRUE;
    }
    $options['query']['entity'] = $this->getImporter()->getEntityType();
    foreach ($options['query'] as $index => $value) {
      if ($value === NULL) {
        $options['query'][$index] = 'null';
      }
    }
    $index = $this->getJSON($path, $options, $method);
    $index['batch'] = [$index['items']];
    return $index;
  }

  public function getItem($id, string $entity_type = NULL, string|Uri $path = NULL, array $options = [], string $method = 'get') {
    $path ??= '/api/json/zero/exporter/data';
    $options['key'] = 'item';
    $options['query']['entity'] = $entity_type ?? $this->getImporter()->getEntityType();
    $options['query']['id'] = $id;
    $response = $this->getJSON($path, $options, $method);
    return $response['entity'];
  }

  public function getFile($id, string|Uri $path = NULL, array $options = [], string $method = 'get') {
    $path ??= '/api/json/zero/exporter/file';
    $options['key'] = 'file';
    $options['query']['file'] = $id;
    return $this->getHttp($path, $options, $method);
  }

  public function info(): array {
    $info = parent::info();
    if (!empty($this->options['index_bundles'])) {
      $info['Index Bundles'] = implode(', ', $this->options['index_bundles']);
    }
    return $info;
  }

}

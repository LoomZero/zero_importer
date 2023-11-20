<?php

namespace Drupal\zero_importer\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

class ZeroImporterPluginManager extends DefaultPluginManager {

  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/Zero/Importer', $namespaces, $module_handler, 'Drupal\zero_importer\Base\Importer\ZImporterInterface',
      'Drupal\zero_importer\Annotation\ZeroImporter');

    $this->alterInfo('zero_importer_info');
    $this->setCacheBackend($cache_backend, 'zero_importer_info');
  }

}

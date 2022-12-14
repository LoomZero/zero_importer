<?php

namespace Drupal\zero_importer\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

class ZeroImporterSourcePluginManager extends DefaultPluginManager {

  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/Zero/Importer/Source', $namespaces, $module_handler, 'Drupal\zero_importer\Base\Source\ZeroImporterSourceInterface',
      'Drupal\zero_importer\Annotation\ZeroImporterSource');

    $this->alterInfo('zero_importer_source_info');
    $this->setCacheBackend($cache_backend, 'zero_importer_source_info');
  }

}

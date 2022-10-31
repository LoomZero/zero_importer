<?php

namespace Drupal\zero_importer\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

class ZeroImporterRemotePluginManager extends DefaultPluginManager {

  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/Zero/Importer/Remote', $namespaces, $module_handler, 'Drupal\zero_importer\Base\Remote\ZeroImporterRemoteInterface',
      'Drupal\zero_importer\Annotation\ZeroImporterRemote');

    $this->alterInfo('zero_importer_remote_info');
    $this->setCacheBackend($cache_backend, 'zero_importer_remote_info');
  }

}

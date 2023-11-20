<?php

namespace Drupal\zero_importer\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * @see \Drupal\zero_importer\Service\ZeroImporterPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class ZeroImporter extends Plugin {

  /** @var string */
  public $id;

}

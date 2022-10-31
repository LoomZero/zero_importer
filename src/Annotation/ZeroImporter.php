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

  /** @var string */
  public $entity_type;

  /** @var int */
  public $user;

  /** @var array */
  public $prevent;

  /** @var array */
  public $options;

  /** @var array */
  public $logger;

  /** @var array */
  public $remote;

  /** @var array */
  public $load;

  /** @var array */
  public $source;

  /** @var array */
  public $action;

  /** @var array */
  public $info;

}

<?php

namespace Drupal\zero_importer\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * @Annotation
 */
class ZeroImporterAction extends Plugin {

  /** @var string */
  public $id;

  /** @var array */
  public $types;

  /** @var array */
  public $attributes;

}

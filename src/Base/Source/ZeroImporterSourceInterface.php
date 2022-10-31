<?php

namespace Drupal\zero_importer\Base\Source;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\zero_importer\Base\ImporterPluginInterface;
use Drupal\zero_importer\Base\ImporterPluginLoggerInterface;
use Drupal\zero_importer\Base\ImporterPluginOptionsInterface;
use Drupal\zero_importer\Info\ImporterEntry;

interface ZeroImporterSourceInterface extends PluginInspectionInterface, ImporterPluginInterface, ImporterPluginOptionsInterface , ImporterPluginLoggerInterface {

  public function index();

  public function prepare(ImporterEntry $entry, ContentEntityBase $entity): ?ImporterEntry;

}

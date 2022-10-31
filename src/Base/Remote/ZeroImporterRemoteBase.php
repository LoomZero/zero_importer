<?php

namespace Drupal\zero_importer\Base\Remote;

use Drupal\Core\Plugin\PluginBase;
use Drupal\zero_importer\Base\ImporterPluginLoggerTrait;
use Drupal\zero_importer\Base\ImporterPluginOptionsTrait;
use Drupal\zero_importer\Base\ImporterPluginTrait;

abstract class ZeroImporterRemoteBase extends PluginBase implements ZeroImporterRemoteInterface {
  use ImporterPluginTrait;
  use ImporterPluginOptionsTrait;
  use ImporterPluginLoggerTrait;
}

<?php

namespace Drupal\zero_importer\Base\Source;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Plugin\PluginBase;
use Drupal\zero_importer\Base\ImporterPluginLoggerTrait;
use Drupal\zero_importer\Base\ImporterPluginOptionsTrait;
use Drupal\zero_importer\Base\ImporterPluginTrait;
use Drupal\zero_importer\Info\ImporterEntry;

abstract class ZeroImporterSourceBase extends PluginBase implements ZeroImporterSourceInterface {
  use ImporterPluginTrait;
  use ImporterPluginOptionsTrait;
  use ImporterPluginLoggerTrait;

  public function prepare(ImporterEntry $entry, ContentEntityBase $entity): ?ImporterEntry {
    $info = [
      'entry' => $entry,
      'entity' => $entity,
    ];
    foreach ($this->importer()->getActions('prepare') as $action) {
      $info['entry'] = $action->consume('prepare', $info);
      if ($info['entry'] === NULL) return NULL;
    }
    return $info['entry'];
  }

}

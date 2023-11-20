<?php

namespace Drupal\zero_importer\Exception;

use Drupal\zero_importer\Base\Importer\ZImporterInterface;

class ImporterSkipException extends ImporterEntryException {

  public function onHandle(ZImporterInterface $importer) {
    if ($this->entity !== NULL) {
      $importer->result()->addEntity($this->entity, ['skip' => TRUE]);
    }
    $importer->logger()->note('[SKIP]: ' . $this->getMessage());
  }

}

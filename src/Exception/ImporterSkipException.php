<?php

namespace Drupal\zero_importer\Exception;

use Drupal\zero_importer\Base\Importer\ZeroImporterInterface;

class ImporterSkipException extends ImporterEntryException {

  public function onHandle(ZeroImporterInterface $importer) {
    if ($this->entity !== NULL) {
      $importer->result()->addEntity($this->entity, ['skip' => TRUE]);
    }
    $importer->logger()->note('[SKIP]: ' . $this->getMessage());
  }

}

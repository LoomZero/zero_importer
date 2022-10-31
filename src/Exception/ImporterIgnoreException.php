<?php

namespace Drupal\zero_importer\Exception;

use Drupal\zero_importer\Base\Importer\ZeroImporterInterface;

class ImporterIgnoreException extends ImporterEntryException {

  public function onHandle(ZeroImporterInterface $importer) {
    if ($this->entity !== NULL) {
      $importer->result()->removeEntity($this->entity);
    }
  }

}

<?php

namespace Drupal\zero_importer\Exception;

use Drupal\zero_importer\Base\Importer\ZImporterInterface;

class ImporterIgnoreException extends ImporterEntryException {

  public function onHandle(ZImporterInterface $importer) {
    if ($this->entity !== NULL) {
      $importer->result()->removeEntity($this->entity);
    }
  }

}

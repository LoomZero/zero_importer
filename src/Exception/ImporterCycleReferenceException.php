<?php

namespace Drupal\zero_importer\Exception;

use Drupal\zero_importer\Base\Importer\ZImporterInterface;

class ImporterCycleReferenceException extends ImporterSkipException {

  public function onHandle(ZImporterInterface $importer) {
    parent::onHandle($importer);
    $importer->logger()->log($this->getMessage());
  }

}

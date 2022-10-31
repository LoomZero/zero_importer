<?php

namespace Drupal\zero_importer\Exception;

use Drupal\zero_importer\Base\Importer\ZeroImporterInterface;

class ImporterCycleReferenceException extends ImporterSkipException {

  public function onHandle(ZeroImporterInterface $importer) {
    parent::onHandle($importer);
    $importer->logger()->log($this->getMessage());
  }

}

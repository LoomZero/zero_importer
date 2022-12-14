<?php

namespace Drupal\zero_importer\Exception;

use Drupal\zero_importer\Base\Importer\ZeroImporterInterface;
use Exception;

class ImporterException extends Exception {

  public function onHandle(ZeroImporterInterface $importer) { }

}

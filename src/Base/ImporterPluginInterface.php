<?php

namespace Drupal\zero_importer\Base;

use Drupal\zero_importer\Base\Importer\ZeroImporterInterface;

interface ImporterPluginInterface {

  public function setImporter(ZeroImporterInterface $importer): self;

  public function importer(): ZeroImporterInterface;

}

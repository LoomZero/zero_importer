<?php

namespace Drupal\zero_importer\Base;

use Drupal\zero_importer\Base\Importer\ZeroImporterInterface;

trait ImporterPluginTrait {

  protected ZeroImporterInterface $importer;

  public function setImporter(ZeroImporterInterface $importer): self {
    $this->importer = $importer;
    return $this;
  }

  public function importer(): ZeroImporterInterface {
    return $this->importer;
  }

}

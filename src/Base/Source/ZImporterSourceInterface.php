<?php

namespace Drupal\zero_importer\Base\Source;

use Drupal\zero_importer\Base\Importer\ZImporterInterface;
use Drupal\zero_importer\Base\Row\ZImportRowInterface;

interface ZImporterSourceInterface {

  public function isRemoteSource(): bool;

  public function createRow($data, array $context = []): ZImportRowInterface;

  public function setImporter(ZImporterInterface $importer): self;

  public function getImporter(): ZImporterInterface;

}

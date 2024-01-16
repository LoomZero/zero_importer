<?php

namespace Drupal\zero_importer\Base\Mapper;

use Drupal\zero_importer\Base\Importer\ZImporterInterface;

abstract class ZImportMapperBase implements ZImportMapperInterface {

  private ?ZImporterInterface $importer;

  public static function create(): static {
    return new static();
  }

  public function setImporter(ZImporterInterface $importer): self {
    $this->importer = $importer;
    return $this;
  }

  public function getImporter(): ZImporterInterface {
    return $this->importer;
  }

}

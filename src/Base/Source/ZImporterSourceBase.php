<?php

namespace Drupal\zero_importer\Base\Source;

use Drupal\zero_importer\Base\Importer\ZImporterInterface;
use Drupal\zero_importer\Base\Row\ZImportRowBase;
use Drupal\zero_importer\Base\Row\ZImportRowInterface;

abstract class ZImporterSourceBase implements ZImporterSourceInterface {

  public static function create(): static {
    return new static();
  }

  private ?ZImporterInterface $importer;

  public function isRemoteSource(): bool {
    return FALSE;
  }

  public function createRow($data, array $context = []): ZImportRowInterface {
    if ($data instanceof ZImportRowInterface) return $data;
    return new ZImportRowBase($this, $data);
  }

  public function setImporter(ZImporterInterface $importer): self {
    $this->importer = $importer;
    return $this;
  }

  public function getImporter(): ZImporterInterface {
    return $this->importer;
  }

}

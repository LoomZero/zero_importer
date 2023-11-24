<?php

namespace Drupal\zero_importer\Plugin\Zero\Importer;

use Drupal\zero_importer\Base\Importer\ZImporterBase;
use Drupal\zero_importer\Base\Row\ZImportRowInterface;
use Drupal\zero_importer\Import\Row\D7ImportRow;
use Drupal\zero_importer\Import\Source\D7ImportSource;
use Drupal\zero_importer\Info\ZImportEntity;

/**
 * @extends ZImporterBase<D7ImportSource, D7ImportRow>
 */
abstract class D7ImporterBase extends ZImporterBase {

  public function index(): array {
    return $this->getSource()->getIndex();
  }

  public function prepare($row): ZImportRowInterface {
    $data = $this->getSource()->getItem($row['id']);
    return $this->createRow($data);
  }

}

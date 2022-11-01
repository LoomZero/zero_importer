<?php

namespace Drupal\zero_importer\Info;

use Drupal\zero_util\Data\DataArray;

class ImporterEntry extends DataArray {

  public function getEntry(string $key): ImporterEntry {
    return new ImporterEntry($this->get($key));
  }

}

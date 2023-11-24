<?php

namespace Drupal\zero_importer\Base\Storage;

interface ZImportStorageInterface {

  public function saveIndexMap(int $batch, array $options);

  public function loadIndexMap();

  public function clearIndexMap();

}

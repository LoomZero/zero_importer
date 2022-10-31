<?php

namespace Drupal\zero_importer\Exception;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\zero_importer\Info\ImporterEntry;

class ImporterEntryException extends ImporterException {

  protected $entry;
  protected $entity;

  public function setInfo(ImporterEntry $entry, ContentEntityBase $entity = NULL): self {
    $this->entry = $entry;
    $this->entity = $entity;
    return $this;
  }

}

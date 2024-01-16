<?php

namespace Drupal\zero_importer\Exception;

use Throwable;

class ZImportSkipException extends ZImportException {

  public function __construct(string $cause, int $code = 0, ?Throwable $previous = null) {
    parent::__construct($cause, $code, $previous);
  }

}

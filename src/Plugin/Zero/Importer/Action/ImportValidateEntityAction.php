<?php

namespace Drupal\zero_importer\Plugin\Zero\Importer\Action;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\zero_importer\Annotation\ZeroImporterAction;
use Drupal\zero_importer\Base\Action\ZeroImporterActionBase;
use Drupal\zero_importer\Exception\ImporterSkipException;

/**
 * @ZeroImporterAction(
 *   id = "import.validate.entity",
 *   types = {"import"},
 *   attributes = {
 *     "singleton" = TRUE,
 *   },
 * )
 */
class ImportValidateEntityAction extends ZeroImporterActionBase {

  public function consume(string $action, array &$info) {
    /** @var ContentEntityBase $entity */
    [ 'entity' => $entity ] = $info;

    $abort = FALSE;
    $messages = [];
    foreach ($entity->validate() as $violation) {
      $abort = TRUE;
      $this->log('warning', [
        'type' => 'warning',
        'message' => 'The validation for "{{ path }}" failed, cause by: {{ message }} Value: {{ value }}',
        'placeholders' => [
          'path' => $violation->getPropertyPath(),
          'message' => $violation->getMessage(),
          'value' => $violation->getInvalidValue(),
        ],
      ]);
      $messages[] = 'The validation for "' . $violation->getPropertyPath() . '" failed, cause by: ' . $violation->getMessage() . ' Value: ' . $violation->getInvalidValue();
    }
    if ($abort) throw new ImporterSkipException(implode(', ', $messages));
  }

}

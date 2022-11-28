<?php

namespace Drupal\zero_importer\Plugin\Zero\Importer\Action;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\zero_importer\Annotation\ZeroImporterAction;
use Drupal\zero_importer\Base\Action\ZeroImporterActionBase;
use Drupal\zero_importer\Exception\ImporterSkipException;
use Drupal\zero_importer\Info\ImporterEntry;

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
    /** @var ImporterEntry $entry */
    [ 'entity' => $entity, 'entry' => $entry ] = $info;

    $abort = FALSE;
    $messages = [];
    foreach ($entity->validate() as $violation) {
      $abort = TRUE;
      $value = $violation->getInvalidValue();
      if (method_exists($value, '__toString')) {
        $value = $value->__toString();
      } else if (!is_scalar($value) && $entry->has($violation->getPropertyPath())) {
        // try to recieve original data
        $data = $entry->get($violation->getPropertyPath());
        $value = 'original{' . json_encode($data) . '}';
      }
      $this->log('warning', [
        'type' => 'warning',
        'message' => 'The validation for "{{ path }}" failed, cause by: {{ message }} Value: {{ value }}',
        'placeholders' => [
          'path' => $violation->getPropertyPath(),
          'message' => $violation->getMessage(),
          'value' => $value,
        ],
      ]);
      $messages[] = 'The validation for "' . $violation->getPropertyPath() . '" failed, cause by: ' . $violation->getMessage() . ' Value: ' . $value;
    }
    if ($abort) throw new ImporterSkipException(implode(', ', $messages));
  }

}

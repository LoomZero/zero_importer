<?php

namespace Drupal\zero_importer\Plugin\Zero\Importer\Action;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\zero_importer\Annotation\ZeroImporterAction;
use Drupal\zero_importer\Base\Action\ZeroImporterActionBase;
use Drupal\zero_importer\Base\Importer\ZeroImporterInterface;
use Drupal\zero_importer\Helper\ImporterHelper;
use Drupal\zero_importer\Info\ImporterEntry;

/**
 * @ZeroImporterAction(
 *   id = "import.fields.mapper",
 *   types = {"import"},
 *   attributes = {
 *     "singleton" = TRUE,
 *   },
 * )
 */
class ImporterFieldsMapperAction extends ZeroImporterActionBase {

  public function consume(string $action, array &$info) {
    /** @var ContentEntityBase $entity */
    /** @var ImporterEntry $entry */
    [ 'entity' => $entity, 'entry' => $entry ] = $info;

    $fields = ImporterHelper::getRelevantFields($entity, $this->option('fields'));

    foreach ($fields as $field) {
      $handlers = ['mapping.name.' . $field];
      $type = $entity->get($field)->getFieldDefinition()->getType();
      if ($type === 'entity_reference' || $type === 'entity_reference_revisions') {
        $handlers[] = 'mapping.type.' . $type . '.' . $entity->get($field)->getFieldDefinition()->getSettings()['target_type'];
      }
      $handlers[] = 'mapping.type.' . $type;

      $this->importer()->execHandler($handlers, function(ZeroImporterInterface $importer, ContentEntityBase $entity, ImporterEntry $entry, string $field) {
        $value = $entry->get($field);
        if (!empty($value)) {
          $entity->set($field, $value);
        }
      }, $this->importer(), $entity, $entry, $field);
    }
  }

}

<?php

namespace Drupal\zero_importer\Plugin\Zero\Importer\Action;

use Drupal\zero_importer\Annotation\ZeroImporterAction;
use Drupal\zero_importer\Base\Action\ZeroImporterActionBase;
use Drupal\zero_importer\Info\ImporterResult;

/**
 * @ZeroImporterAction(
 *   id = "after.unpublish.other",
 *   types = {"after"},
 *   attributes = {
 *     "singleton" = "{{ importer_id }}:{{ plugin_id }}",
 *   },
 * )
 */
class ImporterUnpublishAction extends ZeroImporterActionBase {

  public function consume(string $action, array &$info) {
    /** @var ImporterResult $result */
    [ 'result' => $result ] = $info;

    $exists = $this->option('exists');
    $lookup = $this->importer()->getLookup();

    $query = $lookup->getStorage()->getQuery();
    foreach ($exists as $field) {
      $query->exists($field);
    }

    $conditions = $this->option('conditions');
    if ($conditions !== NULL) {
      $conditions = $lookup->replace($conditions);
      foreach ($conditions as $key => $value) {
        $query->condition($key, $value);
      }
    }

    $ids = $result->ids($this->importer()->annotation()['entity_type']);
    if (count($ids)) {
      $query->condition($lookup->getEntityDefinition()->getKey('id'), $ids, 'NOT IN');
    }

    $notImported = $query->execute();

    if (count($notImported)) {
      $this->log('status', [
        'type' => 'note',
        'message' => '[AFTER] Unpublish {{ count }} content: {{ ids }}',
      ]);
    } else {
      $this->log('nothing', [
        'type' => 'note',
        'message' => '[AFTER] Nothing to unpublish',
      ]);
    }

    foreach ($notImported as $id) {
      $entity = $lookup->getStorage()->load($id);
      $entity->set('status', 0);
      $entity->save();
    }
  }

}

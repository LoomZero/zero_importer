<?php

namespace Drupal\zero_importer\Plugin\Zero\Importer\Action;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\zero_importer\Annotation\ZeroImporterAction;
use Drupal\zero_importer\Base\Action\ZeroImporterActionBase;
use Drupal\zero_importer\Info\ImporterEntry;

/**
 * @ZeroImporterAction(
 *   id = "prepare.hash.field",
 *   types = {"prepare"},
 *   attributes = {
 *     "singleton" = TRUE,
 *   },
 * )
 */
class PrepareHashFieldAction extends ZeroImporterActionBase {

  public function consume(string $action, array &$info) {
    /** @var ContentEntityBase $entity */
    /** @var ImporterEntry $entry */
    [ 'entity' => $entity, 'entry' => $entry ] = $info;

    if (!empty($this->option('force_option'))) {
      if ($this->importer()->option($this->option('force_option'))) return $entry;
    }
    if (!empty($this->option('field'))) {
      $entry->set('_hash', md5(serialize($entry->value())));
      if (!$entity->get($this->option('field'))->isEmpty() && $entry->get('_hash') === $entity->get($this->option('field'))->get(0)->get('value')->getValue()) {
        return NULL;
      }
    }
    return $entry;
  }

}

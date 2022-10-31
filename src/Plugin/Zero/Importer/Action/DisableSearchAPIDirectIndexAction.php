<?php

namespace Drupal\zero_importer\Plugin\Zero\Importer\Action;

use Drupal\Core\Config\StorageInterface;
use Drupal\zero_importer\Annotation\ZeroImporterAction;
use Drupal\zero_importer\Base\Action\ZeroImporterActionBase;

/**
 * @ZeroImporterAction(
 *   id = "command.disable.direct.search.index",
 *   types = {"command"},
 *   attributes = {
 *     "singleton" = TRUE,
 *   },
 * )
 */
class DisableSearchAPIDirectIndexAction extends ZeroImporterActionBase {

  public function consume(string $action, array &$info) {
    /** @var StorageInterface $config_storage */
    $config_storage = \Drupal::service('config.storage');
    $name = 'search_api.index.' . $this->option('index');

    switch ($action) {
      case 'command':
        $this->pushAction('destroy');

        if ($config_storage->exists($name)) {
          $config = $config_storage->read($name);
          if (isset($config['options']['index_directly']) && $config['options']['index_directly'] === TRUE) {
            $config['options']['index_directly'] = FALSE;
            $config_storage->write($name, $config);
            $this->log('disable', [
              'type' => 'note',
              'message' => 'Disable "index_directly" for search api index "{{ index }}"',
              'placeholders' => ['index' => $this->option('index')],
            ]);
          }
        }
        break;
      case 'destroy':
        if ($config_storage->exists($name)) {
          $config = $config_storage->read($name);
          if (isset($config['options']['index_directly']) && $config['options']['index_directly'] === FALSE) {
            $config['options']['index_directly'] = TRUE;
            $config_storage->write($name, $config);
            $this->log('enable', [
              'type' => 'note',
              'message' => 'Enable "index_directly" for search api index "{{ index }}"',
              'placeholders' => ['index' => $this->option('index')],
            ]);
          }
        }
        break;
    }
  }

}

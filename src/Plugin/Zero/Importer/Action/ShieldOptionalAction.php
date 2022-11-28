<?php

namespace Drupal\zero_importer\Plugin\Zero\Importer\Action;

use Drupal\Core\Site\Settings;
use Drupal\zero_importer\Annotation\ZeroImporterAction;
use Drupal\zero_importer\Base\Action\ZeroImporterActionBase;
use Drupal\zero_importer\Exception\ImporterException;

/**
 * @ZeroImporterAction(
 *   id = "command.shield.auth",
 *   types = {"command"},
 *   attributes = {
 *     "singleton" = TRUE,
 *   },
 * )
 */
class ShieldOptionalAction extends ZeroImporterActionBase {

  public function consume(string $action, array &$info) {
    if ($action === 'command') {
      switch ($this->option('source') ?? 'settings') {
        case 'settings':
          $key = $this->option('key') ?? $this->importer()->getPluginId();
          $settings = Settings::get($key);
          if (empty($settings['shield'])) {
            if ($this->option('strict') ?? TRUE) throw new ImporterException('Please set the shield parameter in the settings.local.php $settings[\'' . $key . '\'][\'shield\'] = [\'usernmae\', \'password\'];');
            $this->log('enable', [
              'type' => 'note',
              'message' => 'Continue without shield settings.',
            ]);
          } else {
            $this->log('enable', [
              'type' => 'note',
              'message' => 'Add shield options to remote plugin.',
            ]);
            $annotation = $this->importer()->annotation();
            $annotation['remote']['options']['auth'] = $settings['shield'];
            $this->importer()->setAnnotation($annotation);
          }
          break;
        case 'annotation':
          throw new ImporterException('Not implemented.');
          break;
      }
    }
  }

}

<?php

namespace Drupal\zero_importer\Base\Action;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\zero_importer\Base\ImporterPluginInterface;
use Drupal\zero_importer\Base\ImporterPluginLoggerInterface;
use Drupal\zero_importer\Base\ImporterPluginOptionsInterface;
use Drupal\zero_importer\Info\ImporterEntry;
use Drupal\zero_importer\Info\ImporterResult;

interface ZeroImporterActionInterface extends PluginInspectionInterface, ImporterPluginInterface, ImporterPluginOptionsInterface, ImporterPluginLoggerInterface {

  /**
   * ACTION: `command` - PARAMETERS: array $options;<br/>
   * ACTION: `init` - PARAMETERS: array $definition;<br/>
   * ACTION: `before`;<br/>
   * ACTION: `prepare` - PARAMETERS: ContentEntityBase $entity, ImporterEntry $entry - RETURN: ImporterEntry;<br/>
   * ACTION: `import` - PARAMETERS: ContentEntityBase $entity, ImporterEntry $entry;<br/>
   * ACTION: `after` - PARAMETERS: ImporterResult $result;<br/>
   * ACTION: `destroy` - PARAMETERS: array $options;<br/>
   *
   * ## Example for action `import`:
   * <code>
   *   public function consume(string $action, array &$info) {
   *     [ 'entity' => $entity, 'entry' => $entry ] = $info;
   *     // your code here
   *   }
   * </code>
   * ## Example for multi action:
   * <code>
   *   public function consume(string $action, array &$info) {
   *     switch ($action) {
   *       case 'import':
   *         [ 'entity' => $entity, 'entry' => $entry ] = $info;
   *         // your code for action `import` here
   *         break;
   *       case 'after':
   *         [ 'result' => $result ] = $info;
   *         // your code for action `after` here
   *         break;
   *     }
   *   }
   * </code>
   *
   * @param string $action
   * @param array $info
   *
   * @return mixed
   */
  public function consume(string $action, array &$info);

}

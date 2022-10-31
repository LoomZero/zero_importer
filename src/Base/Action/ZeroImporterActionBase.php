<?php

namespace Drupal\zero_importer\Base\Action;

use Drupal\Core\Plugin\PluginBase;
use Drupal\zero_importer\Base\ImporterPluginLoggerTrait;
use Drupal\zero_importer\Base\ImporterPluginOptionsTrait;
use Drupal\zero_importer\Base\ImporterPluginTrait;

abstract class ZeroImporterActionBase extends PluginBase implements ZeroImporterActionInterface {
  use ImporterPluginTrait;
  use ImporterPluginOptionsTrait;
  use ImporterPluginLoggerTrait;

  public function pushAction(string $action, array $options = NULL): self {
    $definition = $this->importer()->annotation();
    $plugin_definition = array_merge([
      'id' => $this->getPluginId(),
    ], $options ?? $this->getOptions());

    if (isset($definition['action'][$action])) {
      if (isset($definition['action'][$action]['id'])) {
        $definition['action'][$action] = [$definition['action'][$action]];
      }
      $definition['action'][$action][] = $plugin_definition;
    } else {
      $definition['action'][$action] = $plugin_definition;
    }
    $this->importer()->setAnnotation($definition);

    return $this;
  }

  public function consume(string $action, array &$info) { }

}

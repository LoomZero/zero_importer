<?php

namespace Drupal\zero_importer\Trait;

use Drupal\zero_importer\Base\Action\ZeroImporterActionInterface;
use Drupal\zero_util\Data\DataArray;

trait ImporterReactiveActionsTrait {

  /**
   * @param string $action
   *
   * @return ZeroImporterActionInterface[]
   */
  public function getActions(string $action, bool $update_options = TRUE): array {
    if (!array_key_exists($action, $this->actions)) {
      $this->actions[$action] = [];
    }
    $actions = $this->annotation()['action'][$action] ?? [];
    if (isset($actions['id'])) {
      $actions = [$actions];
    }
    $importer_id = $this->getPluginId();
    foreach ($actions as $index => $options) {
      $plugin = $this->actions[$action][$index] ?? NULL;
      if ($plugin === NULL) {
        $singleton = FALSE;
        $definition = $this->manager->getPluginDefinition('action', $options['id']);
        if (isset($options['_definition'])) {
          $definition = array_replace_recursive($definition, $options['_definition']);
        }
        if (isset($definition['attributes']['singleton']) && $definition['attributes']['singleton'] !== FALSE) {
          $singleton = $definition['attributes']['singleton'] === TRUE ? '{{ importer_id }}:{{ id }}' : $definition['attributes']['singleton'];
          $singleton = DataArray::replace($singleton, function(string $value, string $match, string $root) use ($options, $importer_id) {
            return DataArray::getNested(array_merge([
              'importer_id' => $importer_id,
            ], $options), $match);
          });
          if (self::hasRegistry('action', $singleton)) {
            $plugin = self::getRegistry('action', $singleton);
          }
        }
        if ($plugin === NULL) {
          $plugin = $this->manager->getPlugin('action', $options['id']);
          $plugin->setImporter($this);
          $plugin->setOptions($options);
        }
        if (is_string($singleton)) {
          self::setRegistry('action', $singleton, $plugin);
        }
        $this->actions[$action][] = $plugin;
      } else if ($this->isReactiveAction($plugin, $options)) {
        $this->logger()->note('[REACTIVE]: Recreate ' . $action . ' action "' . $options['id'] . '" because the options have changed.');
        $plugin = $this->manager->getPlugin('action', $options['id']);
        $plugin->setImporter($this);
        $plugin->setOptions($options);
        $this->actions[$action][$index] = $plugin;
      }
    }
    return $this->actions[$action];
  }

  public function isReactiveAction(ZeroImporterActionInterface $oldPlugin, $options): bool {
    return !DataArray::arrayEqual($oldPlugin->getOptions(), $options);
  }

}

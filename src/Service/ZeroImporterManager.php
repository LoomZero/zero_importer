<?php

namespace Drupal\zero_importer\Service;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\zero_importer\Base\Action\ZeroImporterActionInterface;
use Drupal\zero_importer\Base\Importer\ZeroImporterInterface;
use Drupal\zero_importer\Base\Remote\ZeroImporterRemoteInterface;
use Drupal\zero_importer\Base\Source\ZeroImporterSourceInterface;
use Drupal\zero_importer\Exception\ImporterException;

class ZeroImporterManager {

  private ZeroImporterPluginManager $importer;
  private ZeroImporterSourcePluginManager $source;
  private ?ZeroImporterInterface $current = NULL;

  public function __construct(ZeroImporterPluginManager $importer, ZeroImporterSourcePluginManager $source, ZeroImporterActionPluginManager $action, ZeroImporterRemotePluginManager $remote) {
    $this->importer = $importer;
    $this->source = $source;
  }

  public function getPluginManager(string $type): DefaultPluginManager {
    switch ($type) {
      case 'importer':
        return $this->importer;
      case 'source':
        return $this->source;
    }
    throw new ImporterException('No plugin manager with this type.');
  }

  /**
   * @param string $type
   * @param string $id
   * @param array $override_definition
   *
   * @return ZeroImporterSourceInterface|ZeroImporterInterface|ZeroImporterActionInterface|ZeroImporterRemoteInterface
   */
  public function getPlugin(string $type, string $id, array $override_definition = []): ZeroImporterSourceInterface|ZeroImporterInterface|ZeroImporterActionInterface|ZeroImporterRemoteInterface {
    $manager = $this->getPluginManager($type);
    $definition = $manager->getDefinition($id);
    foreach ($override_definition as $key => $value) {
      $definition[$key] = $value;
    }
    /** @noinspection PhpIncompatibleReturnTypeInspection */
    return $manager->createInstance($id, $definition);
  }

  public function getPluginDefinition(string $type, string $id): ?array {
    $manager = $this->getPluginManager($type);
    return $manager->getDefinition($id);
  }

  public function getImporter(string $id, array $override_definition = []): ZeroImporterInterface {
    return $this->getPlugin('importer', $id, $override_definition);
  }

  public function getSource(string $id, array $override_definition = []): ZeroImporterSourceInterface {
    return $this->getPlugin('source', $id, $override_definition);
  }

  public function setCurrentImporter(ZeroImporterInterface $importer): self {
    $this->current = $importer;
    return $this;
  }

  public function getCurrentImporter(): ?ZeroImporterInterface {
    return $this->current;
  }

}

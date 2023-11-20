<?php

namespace Drupal\zero_importer\Base\Importer;

use Drupal\Core\Plugin\PluginBase;
use Drupal\zero_importer\Info\ZImportEntity;
use Drupal\zero_importer\Info\ZImportRow;

abstract class ZImporterBase extends PluginBase implements ZImporterInterface {

  private string $entity_type;
  private array $load_definition;

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->doDefine();
  }

  public function doDefine() {
    $this->define();
  }

  public function setEntityType(string $entity_type): self {
    $this->entity_type = $entity_type;
    return $this;
  }

  public function setLoad(array $loadDefinition): self {
    $this->load_definition = $loadDefinition;
    return $this;
  }

  public function doImport() {

  }

  public abstract function define();

  public abstract function import(ZImportEntity $entity, ZImportRow $row);

}

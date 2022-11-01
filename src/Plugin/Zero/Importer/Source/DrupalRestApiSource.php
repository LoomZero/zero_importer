<?php

namespace Drupal\zero_importer\Plugin\Zero\Importer\Source;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\zero_importer\Annotation\ZeroImporterSource;
use Drupal\zero_importer\Base\Importer\ZeroImporterInterface;
use Drupal\zero_importer\Base\Source\ZeroImporterRemoteSourceBase;
use Drupal\zero_importer\Info\ImporterEntry;
use Drupal\zero_util\Data\DataArray;

/**
 * @ZeroImporterSource(
 *   id = "drupal.rest.api",
 * )
 */
class DrupalRestApiSource extends ZeroImporterRemoteSourceBase {

  public static function entityReferenceLookupHandler(array $props): callable {
    return function(ZeroImporterInterface $importer, ContentEntityBase $entity, ImporterEntry $entry, string $field) use ($props) {
      $definition = $entity->get($field);
      $entity_type = $definition->getFieldDefinition()->getSetting('target_type');
      $bundles = $definition->getItemDefinition()->getSetting('handler_settings')['target_bundles'];
      $lookup = $importer->getLookup($entity_type);

      if (count($bundles) === 1) {
        $props['{{ _self.type.bundle }}'] = reset($bundles);
      }

      $values = $entry->get($field);
      $references = [];
      foreach ($values as $value) {
        $target = $lookup->loadFirst($lookup->replace($props, $importer->createEntry($value)));
        if ($target !== NULL) {
          $references[] = [
            'target_id' => $target->id(),
          ];
        }
      }
      $entity->set($field, $references);
    };
  }

  public function index() {
    $index = $this->getJSON('index');
    if ($this->hasOption('index.items')) {
      $index = DataArray::getNested($index, $this->option('index.items'));
    }
    return $index;
  }

  public function prepare(ImporterEntry $entry, ContentEntityBase $entity): ?ImporterEntry {
    if ($this->option('prepare.remote') !== NULL) {
      if (!$this->hasOption('prepare.remote.required') || $entry->hasAll($this->option('prepare.remote.required'))) {
        $entry = $this->importer()->createEntry($this->getJSON('prepare'));
      }
    }
    return parent::prepare($entry, $entity);
  }

}

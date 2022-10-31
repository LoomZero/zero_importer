<?php

namespace Drupal\zero_importer\Trait;

use Drupal\zero_importer\Base\Source\ZeroImporterSourceInterface;
use Drupal\zero_util\Data\DataArray;

trait ImporterReactiveSourceTrait {

  /**
   * Get the source object of this import process.
   *
   * @return ZeroImporterSourceInterface|null
   */
  public function source(bool $update_options = TRUE): ?ZeroImporterSourceInterface {
    if (isset($this->annotation()['source']['id'])) {
      if ($this->source === NULL || $this->isReactiveSource($this->source, $this->annotation()['source'])) {
        if ($this->source !== NULL) {
          $this->logger()->note('[REACTIVE]: Recreate source because the options have changed.');
        }
        $this->source = $this->manager->getSource($this->annotation()['source']['id']);
        $this->source->setImporter($this);
        $this->source->setOptions($this->annotation()['source']);
      }
    }
    return $this->source;
  }

  public function isReactiveSource(ZeroImporterSourceInterface $oldPlugin, array $options): bool {
    return !DataArray::arrayEqual($oldPlugin->getOptions(), $options);
  }

}

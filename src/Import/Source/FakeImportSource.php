<?php

namespace Drupal\zero_importer\Import\Source;

use Drupal\zero_importer\Base\Source\ZImporterSourceBase;

class FakeImportSource extends ZImporterSourceBase {

  private ?array $index = NULL;
  private int $batch_size = 3;

  /** @var null|callable|array  */
  private $item_definition = NULL;

  public function setIndexDefinition(int $items = 3, callable $item_callback = NULL): self {
    $this->index = [];
    for ($i = 0; $i < $items; $i++) {
      $this->index[] = $item_callback($i);
    }
    return $this;
  }

  public function setBatchSize(int $batch_size = 3): self {
    $this->batch_size = $batch_size;
    return $this;
  }

  public function getIndex($asBatch = TRUE) {
    $index = $this->index;
    if ($index === NULL) {
      $index = [
        ['id' => 1],
        ['id' => 2],
        ['id' => 3],
        ['id' => 4],
        ['id' => 5],
        ['id' => 6],
        ['id' => 7],
        ['id' => 8],
        ['id' => 9],
      ];
    }
    if ($asBatch) {
      return ['batch' => array_chunk($index, $this->batch_size)];
    } else {
      return ['index' => $index];
    }
  }

  /**
   * @param null|callable|array $item_definition
   * @return $this
   */
  public function setItemDefinition($item_definition): self {
    $this->item_definition = $item_definition;
    return $this;
  }

  public function getItem($data) {
    if (is_array($this->item_definition)) return $this->item_definition;
    if (is_callable($this->item_definition)) return ($this->item_definition)($data);
    return $data;
  }

}

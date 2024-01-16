<?php

namespace Drupal\zero_importer\Info;

use Drupal\Core\Entity\EntityInterface;
use Drupal\zero_importer\Base\Importer\ZImporterInterface;

/**
 * @template TImporter of ZImporterInterface
 * @template TEntity of EntityInterface
 */
class ZImportResult {

  /** @var TImporter */
  private ZImporterInterface $importer;
  /** @var array */
  private $items = [];

  private $current = NULL;

  /**
   * @param TImporter $importer
   */
  public function __construct(ZImporterInterface $importer) {
    $this->importer = $importer;
  }

  public function getImporter(): ZImporterInterface {
    return $this->importer;
  }

  public function reset(): self {
    $this->current = [];
    return $this;
  }

  public function info(string $key, $value): self {
    $this->current['info'][$key] = $value;
    return $this;
  }

  public function commit(): self {
    $this->current['entity_type'] = $this->getImporter()->getCurrentEntity()->entity()->getEntityTypeId();
    $this->current['id'] = $this->getImporter()->getCurrentEntity()->entity()->id();
    $this->current['revision_id'] = $this->getImporter()->getCurrentEntity()->entity()->getRevisionId();
    $this->items[] = $this->current;
    return $this;
  }

  /**
   * @return array = [
   *     0 => [
   *        'entity_type' => 'node',
   *        'id' => 1,
   *        'revision_id' => 1,
   *        'info' => [],
   *     ]
   * ]
   */
  public function getItems(): array {
    return $this->items;
  }

  public function each(callable $callback): array {
    $mapped = [];
    foreach ($this->getItems() as $index => $item) {
      $mapped[] = $callback($item, $index, $this);
    }
    return $mapped;
  }

  public function eachFilter(callable $callback): array {
    $mapped = [];
    foreach ($this->getItems() as $index => $item) {
      $result = $callback($item, $index, $this);
      if ($result !== NULL) $mapped[] = $result;
    }
    return $mapped;
  }

  /**
   * @param callable(TEntity, int|string, mixed): mixed $callback
   * @param $none_value
   *
   * @return array
   */
  public function eachEntity(callable $callback, $none_value = NULL): array {
    $mapped = [];
    foreach ($this->getItems() as $index => $item) {
      $entity = $this->getImporter()->getEntityStorage($item['entity_type'])->load($item['id']);
      if ($entity !== NULL) {
        $result = $callback($entity, $index, $item);
        if ($result !== $none_value) {
          $mapped[] = $result;
        }
      }
    }
    return $mapped;
  }

  /**
   * Get the ids of the result as array
   *
   * @param string|NULL $entity_type
   *
   * @return int[]
   */
  public function ids(string $entity_type = NULL): array {
    return $this->eachFilter(function($item) use ($entity_type) {
      if ($entity_type === NULL || $item['entity_type'] === $entity_type) return $item['id'];
      return NULL;
    });
  }

  /**
   * Get the ids of the result as target array
   *
   * @param string|NULL $entity_type
   *
   * @return array = [
   *     0 => [
   *       'target_id' => 5,
   *     ],
   * ]
   */
  public function targetIDs(string $entity_type = NULL): array {
    return $this->eachFilter(function($item) use ($entity_type) {
      if ($entity_type === NULL || $item['entity_type'] === $entity_type) {
        return [
          'target_id' => $item['id'],
        ];
      }
      return NULL;
    });
  }

  /**
   * Get the ids of the result as revisionable target array
   *
   * @param string|NULL $entity_type
   *
   * @return array = [
   *     0 => [
   *       'target_id' => 5,
   *       'target_revision_id' => 8,
   *     ],
   * ]
   */
  public function targetRevisions(string $entity_type = NULL): array {
    return $this->eachFilter(function ($item) use ($entity_type) {
      if (isset($item['revision_id']) && ($entity_type === NULL || $item['entity_type'] === $entity_type)) {
        return [
          'target_id' => $item['id'],
          'target_revision_id' => $item['revision_id'],
        ];
      }
      return NULL;
    });
  }

}

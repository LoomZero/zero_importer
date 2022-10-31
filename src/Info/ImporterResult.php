<?php

namespace Drupal\zero_importer\Info;

use Drupal;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;

class ImporterResult {

  /** @var array */
  private $items = [];
  /** @var EntityStorageInterface[] */
  private $storages = [];

  public function getStorage(string $entity_type): EntityStorageInterface {
    if (empty($this->storages[$entity_type])) {
      $this->storages[$entity_type] = Drupal::entityTypeManager()->getStorage($entity_type);
    }
    return $this->storages[$entity_type];
  }

  public function addItem($entity_type, $id, array $data = []): self {
    if (isset($this->items[$entity_type . ':' . $id])) {
      $this->items[$entity_type . ':' . $id]['data'] += $data;
    } else {
      $this->items[$entity_type . ':' . $id] = [
        'entity_type' => $entity_type,
        'id' => $id,
        'data' => $data,
      ];
    }
    return $this;
  }

  public function addEntity(EntityInterface $entity, array $data = []): self {
    return $this->addItem($entity->getEntityTypeId(), $entity->id(), $data);
  }

  public function removeItem($entity_type, $id): self {
    unset($this->items[$entity_type . ':' . $id]);
    return $this;
  }

  public function removeEntity(EntityInterface $entity): self {
    return $this->removeItem($entity->getEntityTypeId(), $entity->id());
  }

  /**
   * @return array = [
   *     0 => [
   *        'entity_type' => 'node',
   *        'id' => 1,
   *        'data' => [],
   *     ]
   * ]
   */
  public function getItems(): array {
    return $this->items;
  }

  public function each(callable $callback): self {
    foreach ($this->getItems() as $index => $item) {
      $callback($item, $index, $this);
    }
    return $this;
  }

  public function map(callable $callback): array {
    $mapped = [];
    foreach ($this->getItems() as $index => $item) {
      $mapped[] = $callback($item, $index, $this);
    }
    return $mapped;
  }

  public function mapFilter(callable $callback): array {
    $mapped = [];
    foreach ($this->getItems() as $index => $item) {
      $result = $callback($item, $index, $this);
      if ($result !== NULL) $mapped[] = $result;
    }
    return $mapped;
  }

  public function ids(string $entity_type = NULL): array {
    return $this->mapFilter(function($item) use ($entity_type) {
      if ($entity_type === NULL || $item['entity_type'] === $entity_type) return $item['id'];
      return NULL;
    });
  }

  /**
   * @param array $item = [
   *     'entity_type' => 'node',
   *     'id' => 1,
   * ]
   * @returns EntityInterface
   */
  public function load(array $item): EntityInterface {
    return $this->getStorage($item['entity_type'])->load($item['id']);
  }

}

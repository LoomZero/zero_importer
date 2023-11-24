<?php

namespace Drupal\zero_importer\Base\Importer;

use Drupal;
use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\zero_importer\Base\Row\ZImportRowInterface;
use Drupal\zero_importer\Base\Source\ZImporterSourceInterface;
use Drupal\zero_importer\Exception\ZImportPrepareException;
use Drupal\zero_importer\Info\ZImportEntity;
use Drupal\zero_util\Data\DataArray;

/**
 * @template TSource of ZImporterSourceInterface
 * @template TRow of ZImportRowInterface
 * @extends ZImporterInterface<TSource, TRow>
 */
abstract class ZImporterBase extends PluginBase implements ZImporterInterface {

  private string $entity_type;
  /** @var TSource */
  private ZImporterSourceInterface $source;
  private array $options = [];
  private array $storages = [];
  /** @var TRow */
  private ?ZImportRowInterface $current_row;
  private bool $is_init = FALSE;

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->doDefine();
  }

  public function getEntityStorage(string $entity_type = NULL): ContentEntityStorageInterface {
    $entity_type ??= $this->getEntityType();
    if (empty($this->storages[$entity_type])) {
      $this->storages[$entity_type] = Drupal::entityTypeManager()->getStorage($entity_type);
    }
    return $this->storages[$entity_type];
  }

  /**
   * @param $value
   * @param TRow|NULL $row
   */
  public function replacer($value, ZImportRowInterface $row = NULL) {
    if ($row === NULL) $row = $this->getCurrentRow();
    return DataArray::replaceAll($value, function(string $value, string $match, string $root) use ($row): string {
      $parts = explode('.', $match);
      if ($parts[0] === '_def') {
        switch ($parts[1]) {
          case 'prop':
            $definition = Drupal::entityTypeManager()->getDefinition(($parts[2] === '_' ? $this->getEntityType() : $parts[2]));
            return $definition->getKey($parts[3]);
        }
      } else if ($parts[0] === '_opts') {
        return $this->getOption(implode('.', array_slice($parts, 1)));
      }
      return $row->replace($value, $match, $root);
    });
  }

  /**
   * @return TRow|null
   */
  public function getCurrentRow(): ?ZImportRowInterface {
    return $this->current_row;
  }

  public function doDefine() {
    $this->define();
  }

  public function setEntityType(string $entity_type): self {
    $this->entity_type = $entity_type;
    return $this;
  }

  public function getEntityType(): string {
    return $this->entity_type;
  }

  public function setOptions(array $options): self {
    $this->options = $options;
    return $this;
  }

  public function getOptions(): array {
    return $this->options;
  }

  public function setOption(string $key, $value): self {
    $this->options = DataArray::setNested($this->options, $key, $value);
    return $this;
  }

  public function getOption(string $key) {
    return DataArray::getNested($this->options, $key);
  }

  public function setLoadDefinition(array $loadDefinition = NULL): self {
    $this->setOption('importer.load_definition', $loadDefinition);
    return $this;
  }

  public function getLoadDefinition(): ?array {
    return $this->getOption('importer.load_definition');
  }

  /**
   * Set the maximum execution of batch in one import call. Only if root importer. Will be overwritten by cmd option '--max-batch-execute'.
   *
   * @param int $max
   *
   * @return $this
   */
  public function setMaxBatchExecute(int $max): self {
    return $this->setOption('importer.max_batch_execute', $max);
  }

  public function getMaxBatchExecute(): ?int {
    return $this->getOption('importer.max_batch_execute');
  }

  /**
   * @param TSource $source
   * @return $this
   */
  public function setSource(ZImporterSourceInterface $source): self {
    $source->setImporter($this);
    $this->source = $source;
    return $this;
  }

  /**
   * @return TSource|null
   */
  public function getSource(): ?ZImporterSourceInterface {
    return $this->source;
  }

  /**
   * @param $data
   * @param array|NULL $context
   * @return TRow
   */
  public function createRow($data, array $context = NULL): ZImportRowInterface {
    if ($data instanceof ZImportRowInterface) return $data;
    return $this->getSource()->createRow($data, $context);
  }

  public function doExecute() {
    $this->doInit();
    $index = $this->doIndex();

    if (isset($index['index'])) {
      $a = 0;
    }

    $max = $this->getMaxBatchExecute();
    $count = 0;
    foreach ($index['batch'] as $batch) {
      foreach ($batch as $item) {
        $this->current_row = $this->doPrepare($item);
        $entity = $this->doLoad($this->current_row);
        if ($entity === NULL) {
          $entity = $this->doCreate($this->current_row);
        }
        $this->doImport($entity, $this->current_row);
        $this->doSave($entity);
        $this->current_row = NULL;
      }
      $count++;
      if ($max !== NULL && $count >= $max) break;
    }
    $this->doExit();
  }

  public function doInit(): void {
    if (!$this->is_init) {
      $this->init();
      $this->is_init = TRUE;
    }
  }

  public function doIndex(): array {
    return $this->index();
  }

  /**
   * @param $row
   * @return TRow
   */
  public function doPrepare($row): ZImportRowInterface {
    return $this->prepare($row);
  }

  /**
   * @param TRow $row
   * @return ZImportEntity|null
   */
  public function doLoad(ZImportRowInterface $row): ?ZImportEntity {
    $entity = $this->load($row);
    if ($entity === NULL) {
      $loadDefinition = $this->getLoadDefinition();
      if ($loadDefinition) {
        $props = $this->replacer($loadDefinition, $row);
        $entity = $this->getEntityStorage()->loadByProperties($props);
        if (count($entity) > 0) {
          $entity = array_shift($entity);
          return new ZImportEntity($entity);
        }
      }
    }
    return $entity;
  }

  /**
   * @param TRow $row
   * @return ZImportEntity
   */
  public function doCreate(ZImportRowInterface $row): ZImportEntity {
    return $this->create($row);
  }

  /**
   * @param ZImportEntity $entity
   * @param TRow $row
   */
  public function doImport(ZImportEntity $entity, ZImportRowInterface $row) {
    $this->import($entity, $row);
  }

  /**
   * @param ZImportEntity $entity
   */
  public function doSave(ZImportEntity $entity) {
    $this->save($entity);
  }

  public function doExit(): void {
    $this->exit();
  }

  public abstract function define(): void;

  public function init(): void { }

  public abstract function index(): array;

  /**
   * @param $row
   * @return TRow
   */
  public abstract function prepare($row): ZImportRowInterface;

  /**
   * @param TRow $row
   * @return ZImportEntity|null
   */
  public abstract function load(ZImportRowInterface $row): ?ZImportEntity;

  /**
   * @param TRow $row
   * @return ZImportEntity
   */
  public function create(ZImportRowInterface $row): ZImportEntity {
    $loadDefinition = $this->getLoadDefinition();
    if ($loadDefinition) {
      $props = $this->replacer($loadDefinition, $row);
      $entity = $this->getEntityStorage()->create($props);
      if ($entity !== NULL) {
        return new ZImportEntity($entity);
      }
      throw new ZImportPrepareException('Could not create a new entity. Please change the load definition or overwrite the `create(ZImportRowInterface $row): ZImportEntity` method.');
    }
    throw new ZImportPrepareException('No load definition is found. Please add a load definition or overwrite the `create(ZImportRowInterface $row): ZImportEntity` method.');
  }

  /**
   * @param ZImportEntity $entity
   * @param TRow $row
   */
  public abstract function import(ZImportEntity $entity, ZImportRowInterface $row);

  public function save(ZImportEntity $entity) {
    $entity->entity()->save();
  }

  public function exit(): void { }

}

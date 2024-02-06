<?php

namespace Drupal\zero_importer\Base\Importer;

use Drupal;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Site\Settings;
use Drupal\zero_importer\Base\Mapper\ZImportMapperInterface;
use Drupal\zero_importer\Base\Row\ZImportRowBase;
use Drupal\zero_importer\Base\Row\ZImportRowInterface;
use Drupal\zero_importer\Base\Source\ZImporterSourceInterface;
use Drupal\zero_importer\Exception\ZImportPrepareException;
use Drupal\zero_importer\Exception\ZImportSkipException;
use Drupal\zero_importer\Info\ZImportEntity;
use Drupal\zero_importer\Info\ZImportPlaceholder;
use Drupal\zero_importer\Info\ZImportResult;
use Drupal\zero_util\Data\DataArray;
use Throwable;

/**
 * @template TSource of ZImporterSourceInterface
 * @template TRow of ZImportRowInterface
 * @template TEntity of ContentEntityBase
 * @extends ZImporterInterface<TSource, TRow, TEntity>
 */
abstract class ZImporterBase extends PluginBase implements ZImporterInterface {

  private static $settings = NULL;

  private string $entity_type;
  private $bundle_definition;
  /** @var TSource */
  private ZImporterSourceInterface $source;
  private ZImportMapperInterface $mapper;
  private array $options = [];
  private array $storages = [];
  private $current_index = NULL;
  /** @var TRow|NULL */
  private ?ZImportRowInterface $current_row;
  /** @var ZImportEntity<TEntity>|NULL */
  private ?ZImportEntity $current_entity;
  private $row_class = ZImportRowBase::class;
  private bool $is_init = FALSE;
  private ?ZImportResult $results = NULL;

  public static function getSettings(string $importer, string $key = NULL) {
    if (self::$settings === NULL) {
      self::$settings = Settings::get('zero_importer');
    }
    return DataArray::getNested(self::$settings[$importer] ?? NULL, $key);
  }

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

  public function setting(string $key = NULL) {
    return self::getSettings($this->getPluginId(), $key);
  }

  public function getEntityKey(string $key, string $entity_type = NULL): ?string {
    if ($entity_type === NULL) $entity_type = $this->getEntityType();
    return Drupal::entityTypeManager()->getDefinition($entity_type)->getKey($key) ?? NULL;
  }

  /**
   * @param $value
   * @param TRow|NULL $row
   */
  public function replacer($value, ZImportRowInterface $row = NULL) {
    if ($row === NULL) $row = $this->getCurrentRow();
    return DataArray::replaceAll($value, function(string $value, string $match, string $root) use ($row): string {
      $parts = explode('.', $match);
      if ($parts[0] === '@DEF') {
        switch ($parts[1]) {
          case 'keys':
            $definition = Drupal::entityTypeManager()->getDefinition(($parts[2] === '_' ? $this->getEntityType() : $parts[2]));
            return $definition->getKey($parts[3]);
          case 'bundle':
            return $this->getBundle($row);
        }
      } else if ($parts[0] === '@OPTS') {
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

  /**
   * @inheritDoc
   */
  public function getCurrentEntity(): ?ZImportEntity {
    return $this->current_entity;
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

  /**
   * @inheritDoc
   */
  public function setBundle($bundle_definition): self {
    $this->bundle_definition = $bundle_definition;
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getBundle(ZImportRowInterface $row = NULL): string {
    if (is_string($this->bundle_definition)) {
      return $this->replacer($this->bundle_definition, $row);
    }
    return ($this->bundle_definition)($row ?? $this->getCurrentRow());
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

  public function setPreventOverwrite(bool $prevent_overwrite = TRUE): self {
    $this->setOption('importer.entity.prevent_overwrite', $prevent_overwrite);
    return $this;
  }

  public function isPreventOverwrite(): bool {
    return $this->getOption('importer.entity.prevent_overwrite') ?? FALSE;
  }

  public function setLoadDefinition(array $loadDefinition = NULL): self {
    $this->setOption('importer.load_definition', $loadDefinition);
    return $this;
  }

  public function getLoadDefinition(): ?array {
    return $this->getOption('importer.load_definition');
  }

  public function placeholder(string $field = ''): ZImportPlaceholder {
    return ZImportPlaceholder::get($field);
  }

  /**
   * Set the maximum execution of batch in one import call. Only if root
   * importer. Will be overwritten by cmd option '--max-batch-execute'.
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

  public function setMapper(ZImportMapperInterface $mapper): self {
    $this->mapper = $mapper;
    $mapper->setImporter($this);
    return $this;
  }

  public function getMapper(): ?ZImportMapperInterface {
    return $this->mapper;
  }

  /**
   * @return ZImportResult<TEntity>
   */
  public function results(): ZImportResult {
    return $this->results;
  }

  /**
   * @param $data
   * @param array|NULL $context
   * @return TRow
   */
  public function createRow($data, array $context = []): ZImportRowInterface {
    if ($data instanceof ZImportRowInterface) return $data;
    if (is_callable($this->getRowClass())) {
      return ($this->getRowClass())($this, $data);
    } else {
      return new ($this->row_class)($this, $data);
    }
  }

  public function doExecute(): self {
    try {
      $this->doInit();
      $index = $this->doIndex();

      if (isset($index['index'])) {
        $a = 0;
      }

      $this->results = new ZImportResult($this);
      $max = $this->getMaxBatchExecute();
      $batchCount = 0;
      foreach ($index['batch'] as $batch) {
        foreach ($batch as $item) {
          $this->results()->reset();
          $this->current_index = $item;

          try {
            $this->current_row = $this->doPrepare($item);
            $entity = $this->doLoad($this->current_row);
            if ($entity === NULL) {
              $entity = $this->doCreate($this->current_row);
            }
            $this->current_entity = $entity;
            $this->doImport($entity, $this->current_row);
            $this->doSave($entity);
            $this->results()->commit();
          } catch (Throwable $importer_exception) {
            if (!$importer_exception instanceof ZImportSkipException) {
              $this->importerCatch($importer_exception);
            }
          }

          $this->current_index = NULL;
          $this->current_row = NULL;
          $this->current_entity = NULL;
        }
        $batchCount++;
        if ($max !== NULL && $batchCount >= $max) break;
      }
      $this->doAfter();
      $this->doExit();
    } catch (Throwable $e) {
      $this->catch($e);
    }
    return $this;
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
   * @return ZImportEntity<TEntity>|null
   */
  public function doLoad(ZImportRowInterface $row): ?ZImportEntity {
    $entity = $this->load($row);
    if ($entity === NULL) {
      $entity = $this->getMapper()?->find($row);
      if ($entity !== NULL) {
        return ZImportEntity::create($entity, $this);
      }
    }
    return $entity;
  }

  /**
   * @param TRow $row
   * @return ZImportEntity<TEntity>
   */
  public function doCreate(ZImportRowInterface $row): ZImportEntity {
    $entity = $this->create($row);
    if ($entity === NULL) {
      $entity = $this->getEntityStorage()->create([
        $this->replacer('{{ @DEF.keys._.bundle }}') => $this->getBundle($row),
      ]);
      $entity = ZImportEntity::create($entity, $this);
    }
    if ($entity === NULL) throw new ZImportPrepareException('Could not create a new entity. Please change the load definition or overwrite the `create(ZImportRowInterface $row): ZImportEntity` method.');
    $this->getMapper()?->register($entity, $row);
    return $entity;
  }

  /**
   * @param ZImportEntity<TEntity> $entity
   * @param TRow $row
   */
  public function doImport(ZImportEntity $entity, ZImportRowInterface $row) {
    $this->import($entity, $row);
  }

  /**
   * @param ZImportEntity<TEntity> $entity
   */
  public function doSave(ZImportEntity $entity) {
    $this->save($entity);
  }

  public function doAfter(): void {
    $this->results()->each(function($item, $index, $result) {
      $entity = NULL;
      foreach (($item['info']['_references'] ?? []) as $reference) {
        $storage = Drupal::entityTypeManager()->getStorage($reference['entity_type']);
        $items = $this->createRow($reference['values'])->each(function($index, ZImportRowInterface $value) use ($reference, $storage) {
          $findDefinition = $this->replacer($reference['findDefinition'], $value);
          $entities = $storage->loadByProperties($findDefinition);
          if (count($entities)) {
            return array_shift($entities);
          } else {
            return NULL;
          }
        });
        if (count($items)) {
          if ($entity === NULL) {
            $entity = $result->loadEntity($item);
          }
          $entity->set($reference['field'], $items);
        }
      }
      if ($entity !== NULL) {
        $entity->save();
      }
    });
    $this->after();
  }

  public function doExit(): void {
    $this->exit($this->results());
  }

  /**
   * @inheritDoc
   */
  public function setRowClass($row_class): ZImporterInterface {
    $this->row_class = $row_class;
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getRowClass() {
    return $this->row_class;
  }

  public abstract function define(): void;

  public function init(): void { }

  public abstract function index(): array;

  /**
   * @param $row
   * @return TRow
   */
  public function prepare($row): ZImportRowInterface {
    return $this->createRow($row);
  }

  /**
   * @param TRow $row
   * @return ZImportEntity<TEntity>|null
   */
  public function load(ZImportRowInterface $row): ?ZImportEntity {
    return NULL;
  }

  /**
   * @param TRow $row
   * @return ZImportEntity<TEntity>
   */
  public function create(ZImportRowInterface $row): ?ZImportEntity {
    return NULL;
  }

  /**
   * @param ZImportEntity<TEntity> $entity
   * @param TRow $row
   */
  public abstract function import(ZImportEntity $entity, ZImportRowInterface $row);

  /**
   * @param ZImportEntity<TEntity> $entity
   */
  public function save(ZImportEntity $entity) {
    $entity->entity()->save();
  }

  public function after(): void { }

  public function exit(ZImportResult $result): void { }

  public function importerCatch(Throwable $e) {
    throw $e;
  }

  public function catch(Throwable $e) {
    throw $e;
  }

}

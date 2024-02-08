<?php

namespace Drupal\zero_importer\Base\Importer;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\zero_importer\Base\Mapper\ZImportMapperInterface;
use Drupal\zero_importer\Base\Row\ZImportRowInterface;
use Drupal\zero_importer\Base\Source\ZImporterSourceInterface;
use Drupal\zero_importer\Info\ZImportEntity;
use Drupal\zero_importer\Info\ZImportPlaceholder;
use Drupal\zero_importer\Info\ZImportResult;
use Throwable;

/**
 * @template TSource of ZImporterSourceInterface
 * @template TRow of ZImportRowInterface
 * @template TEntity of ContentEntityBase
 */
interface ZImporterInterface extends PluginInspectionInterface {

  public function getEntityStorage(string $entity_type = NULL): ContentEntityStorageInterface;

  public function setting(string $key = NULL);

  public function getEntityKey(string $key, string $entity_type = NULL): ?string;

  /**
   * @param $value
   * @param TRow|NULL $row
   */
  public function replacer($value, ZImportRowInterface $row = NULL);

  /**
   * @return TRow|null
   */
  public function getCurrentRow(): ?ZImportRowInterface;

  /**
   * @return ZImportEntity<TEntity>|null
   */
  public function getCurrentEntity(): ?ZImportEntity;

  public function getBundleDefinition();

  /**
   * @param string|callable $bundle_definition
   * @return $this
   */
  public function setBundle($bundle_definition): self;

  /**
   * @param TRow|NULL $row
   * @return string
   */
  public function getBundle(ZImportRowInterface $row = NULL): string;

  public function doDefine();

  public function setEntityType(string $entity_type): self;

  public function getEntityType(): string;

  public function setOptions(array $options): self;

  public function getOptions(): array;

  public function setOption(string $key, $value): self;

  public function getOption(string $key);

  public function setPreventOverwrite(bool $prevent_overwrite = TRUE): self;

  public function isPreventOverwrite(): bool;

  public function setLoadDefinition(array $loadDefinition = NULL): self;

  public function getLoadDefinition(): ?array;

  public function placeholder(string $field = ''): ZImportPlaceholder;

  /**
   * Set the maximum execution of batch in one import call. Only if root importer. Will be overwritten by cmd option '--max-batch-execute'.
   *
   * @param int $max
   *
   * @return $this
   */
  public function setMaxBatchExecute(int $max): self;

  public function getMaxBatchExecute(): ?int;

  /**
   * @param TSource $source
   * @return $this
   */
  public function setSource(ZImporterSourceInterface $source): self;

  /**
   * @return TSource|null
   */
  public function getSource(): ?ZImporterSourceInterface;

  public function setMapper(ZImportMapperInterface $mapper): self;

  public function getMapper(): ?ZImportMapperInterface;

  /**
   * @param $data
   * @param array|NULL $context
   * @return TRow
   */
  public function createRow($data, array $context = []): ZImportRowInterface;

  public function results(): ZImportResult;

  public function doExecute(): self;

  public function doInit(): void;

  public function doIndex(): array;

  /**
   * @param $row
   * @return TRow
   */
  public function doPrepare($row): ZImportRowInterface;

  /**
   * @param TRow $row
   * @return ZImportEntity<TEntity>|null
   */
  public function doLoad(ZImportRowInterface $row): ?ZImportEntity;

  /**
   * @param TRow $row
   * @return ZImportEntity<TEntity>
   */
  public function doCreate(ZImportRowInterface $row): ZImportEntity;

  /**
   * @param ZImportEntity<TEntity> $entity
   * @param TRow $row
   */
  public function doImport(ZImportEntity $entity, ZImportRowInterface $row);

  /**
   * @param ZImportEntity<TEntity> $entity
   */
  public function doSave(ZImportEntity $entity);

  public function doAfter(): void;

  public function doExit(): void;

  /**
   * @param string|callable $row_class
   * @return ZImporterInterface
   */
  public function setRowClass($row_class): self;

  /**
   * @return string|callable
   */
  public function getRowClass();

  public function importerCatch(Throwable $e);

  public function catch(Throwable $e);

}

<?php

namespace Drupal\zero_importer\Base\Importer;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\zero_importer\Base\Row\ZImportRowInterface;
use Drupal\zero_importer\Base\Source\ZImporterSourceInterface;
use Drupal\zero_importer\Info\ZImportEntity;

/**
 * @template TSource of ZImporterSourceInterface
 * @template TRow of ZImportRowInterface
 */
interface ZImporterInterface extends PluginInspectionInterface {

  public function getEntityStorage(string $entity_type = NULL): ContentEntityStorageInterface;

  /**
   * @param $value
   * @param TRow|NULL $row
   */
  public function replacer($value, ZImportRowInterface $row = NULL);

  /**
   * @return TRow|null
   */
  public function getCurrentRow(): ?ZImportRowInterface;

  public function doDefine();

  public function setEntityType(string $entity_type): self;

  public function getEntityType(): string;

  public function setOptions(array $options): self;

  public function getOptions(): array;

  public function setOption(string $key, $value): self;

  public function getOption(string $key);

  public function setLoadDefinition(array $loadDefinition = NULL): self;

  public function getLoadDefinition(): ?array;

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

  /**
   * @param $data
   * @param array|NULL $context
   * @return TRow
   */
  public function createRow($data, array $context = NULL): ZImportRowInterface;

  public function doExecute();

  public function doInit(): void;

  public function doIndex(): array;

  /**
   * @param $row
   * @return TRow
   */
  public function doPrepare($row): ZImportRowInterface;

  /**
   * @param TRow $row
   * @return ZImportEntity|null
   */
  public function doLoad(ZImportRowInterface $row): ?ZImportEntity;

  /**
   * @param TRow $row
   * @return ZImportEntity
   */
  public function doCreate(ZImportRowInterface $row): ZImportEntity;

  /**
   * @param ZImportEntity $entity
   * @param TRow $row
   */
  public function doImport(ZImportEntity $entity, ZImportRowInterface $row);

  /**
   * @param ZImportEntity $entity
   */
  public function doSave(ZImportEntity $entity);

  public function doExit(): void;

}

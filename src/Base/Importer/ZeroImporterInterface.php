<?php

namespace Drupal\zero_importer\Base\Importer;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\zero_importer\Base\Action\ZeroImporterActionInterface;
use Drupal\zero_importer\Base\ImporterPluginLoggerInterface;
use Drupal\zero_importer\Base\ImporterPluginOptionsInterface;
use Drupal\zero_importer\Base\Remote\ZeroImporterRemoteInterface;
use Drupal\zero_importer\Base\Source\ZeroImporterSourceInterface;
use Drupal\zero_importer\Info\ImporterEntry;
use Drupal\zero_importer\Info\ImporterLookup;
use Drupal\zero_importer\Info\ImporterResult;
use Drupal\zero_logger\Base\ZeroLoggerHandlingInterface;

interface ZeroImporterInterface extends PluginInspectionInterface, ZeroLoggerHandlingInterface, ImporterPluginOptionsInterface, ImporterPluginLoggerInterface {

  public function getParent(): ?ZeroImporterInterface;

  public function setParent(ZeroImporterInterface $parent): self;

  public function getRoot(): ZeroImporterInterface;

  public function isPrevented(string $key): bool;

  public function getCurrent(): ?ImporterEntry;

  public function createEntry(array|ImporterEntry $data): ImporterEntry;

  public function execute(array $options = []);

  public function annotation(): array;

  public function setAnnotation(array $annotation): self;

  public function remote(bool $update_options = TRUE): ZeroImporterRemoteInterface;

  public function setHandler(string $key, callable $callback): self;

  public function hasHandler(string $key): bool;

  public function doHandler(string $key, ...$parameters);

  public function execHandler(array $keys, callable $fallback, ...$parameters);

  public function doCommand(array &$options);

  public function doDestroy(array &$options);

  public function getLookup(string $entity_type = NULL): ImporterLookup;

  public function source(bool $update_options = TRUE): ?ZeroImporterSourceInterface;

  /**
   * @param string $action
   * @param bool $update_options
   *
   * @return ZeroImporterActionInterface[]
   */
  public function getActions(string $action, bool $update_options = TRUE): array;

  /**
   * @return array|ImporterEntry[]
   */
  public function index(): array;

  public function load(ImporterEntry $entry): ?ContentEntityBase;

  public function create(ImporterEntry $entry): ContentEntityBase;

  public function prepare(ImporterEntry $entry, ContentEntityBase $entity): ?ImporterEntry;

  public function import(ContentEntityBase $entity, ImporterEntry $entry);

  public function doSave(ContentEntityBase $entity, ImporterEntry $entry);

  public function after(ImporterResult $result);

  public function result(): ImporterResult;

}

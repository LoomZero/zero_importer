<?php

namespace Drupal\zero_importer\Base\Row;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\user\RoleInterface;
use Drupal\zero_importer\Base\Importer\ZImporterChild;
use Drupal\zero_importer\Base\Importer\ZImporterInterface;

interface ZImportRowInterface {

  public function getImporter(): ZImporterInterface;

  public function getData();

  public function replace(string $value, string $match, string $root): string;

  public function get($key = NULL, $fallback = NULL, array $context = []): static;

  public function raw($key = NULL, $fallback = NULL, array $context = []);

  public function has($key, array $context = []): bool;

  public function set($key, $value, array $context = []): self;

  public function call(callable $call = NULL): static;

  public function map(callable $mapper = NULL): static;

  public function each(callable $callback, $none_value = NULL): array;

  public function filter(callable $filter): static;

  public function value(callable $getter = NULL);

  public function array(): array;

  public function string(string $key = NULL, array $context = []): ?string;

  public function int(string $key = NULL, array $context = []): ?int;

  public function bool(string $key = NULL, array $context = []): ?bool;

  public function fromImport(string $importer, callable $mapper = NULL): ?ContentEntityBase;

  public function child(string $entity_type, string $entity_bundle = NULL): ZImporterChild;

  public function toKey(string $key = NULL, array $context = []): string;

  /**
   * @param array $options = [
   *   'id_field' => 'id',
   *   'label_field' => 'label',
   *   'create_entity' => FALSE,
   * ]
   *
   * @return ZImporterChild
   */
  public function toRoles(array $options): ZImporterChild;

  /**
   * @param string $category
   * @param array $options = [
   *   'id_field' => 'tid',
   *   'label_field' => 'name',
   *   'create_entity' => FALSE,
   *   'source_field' => 'field_source_id',
   * ]
   *
   * @return ZImporterChild
   */
  public function toTargetTerms(string $category, array $options): ZImporterChild;

}

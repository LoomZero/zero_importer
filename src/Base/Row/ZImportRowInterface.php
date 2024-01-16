<?php

namespace Drupal\zero_importer\Base\Row;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\zero_importer\Base\Importer\ZImporterChild;
use Drupal\zero_importer\Base\Importer\ZImporterInterface;

interface ZImportRowInterface {

  public function getImporter(): ZImporterInterface;

  public function getData();

  public function replace(string $value, string $match, string $root): string;

  public function get($key, $fallback = NULL, array $context = []): static;

  public function raw($key, $fallback = NULL, array $context = []);

  public function has($key, array $context = []): bool;

  public function set($key, $value, array $context = []): self;

  public function map(callable $mapper = NULL): static;

  public function each(callable $callback, $none_value = NULL): array;

  public function value();

  public function string(string $key = NULL, array $context = []): ?string;

  public function int(string $key = NULL, array $context = []): ?int;

  public function bool(string $key = NULL, array $context = []): ?bool;

  public function fromImport(string $importer, callable $mapper = NULL): ?ContentEntityBase;

  public function child(string $entity_type, string $entity_bundle): ZImporterChild;

}

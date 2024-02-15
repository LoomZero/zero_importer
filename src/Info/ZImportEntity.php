<?php

namespace Drupal\zero_importer\Info;

use Drupal;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\zero_importer\Base\Importer\ZImporterInterface;
use Drupal\zero_importer\Base\Row\ZImportRowInterface;
use Drupal\zero_importer\Exception\ZImportException;

/**
 * @template TEntity of ContentEntityBase
 */
class ZImportEntity {

  private ContentEntityBase $entity;
  private ZImporterInterface $importer;
  private bool $prevent_overwrite = FALSE;

  /**
   * @param TEntity $entity
   * @return ZImportEntity<TEntity>
   */
  public static function create($entity, ZImporterInterface $importer): ZImportEntity {
    if ($entity instanceof self) return $entity;
    return new ZImportEntity($entity, $importer);
  }

  /**
   * @param TEntity $entity
   */
  public function __construct($entity, ZImporterInterface $importer) {
    $this->entity = $entity;
    $this->importer = $importer;
    $this->setPreventOverwrite($importer->isPreventOverwrite());
  }

  /**
   * @return TEntity
   */
  public function entity() {
    return $this->entity;
  }

  public function getImporter(): ZImporterInterface {
    return $this->importer;
  }

  public function set(string $field, mixed $value): self {
    if (!$this->prevent_overwrite || $this->entity()->get($field)->isEmpty()) {
      $this->entity()->set($field, $value);
    }
    return $this;
  }

  public function isNew(): bool {
    return $this->entity()->isNew();
  }

  public function isPreventOverwrite(): bool {
    return $this->prevent_overwrite;
  }

  public function setPreventOverwrite(bool $prevent_overwrite = TRUE): self {
    $this->prevent_overwrite = $prevent_overwrite;
    return $this;
  }

  public function setAfterReferences(string $field, $values, $findDefinition): self {
    if ($values instanceof ZImportRowInterface) {
      $values = $values->raw();
    }
    $this->getImporter()->results()->addInfo('_references', [
      'entity_type' => $this->entity()->get($field)->getFieldDefinition()->getFieldStorageDefinition()->getSetting('target_type'),
      'field' => $field,
      'values' => $values,
      'findDefinition' => $findDefinition,
    ]);
    return $this;
  }

  public function getFieldType(string $field): string {
    return $this->entity()->get($field)->getFieldDefinition()->getType();
  }

  public function getFieldReferenceType(string $field): ?string {
    return $this->entity()->get($field)->getFieldDefinition()->getSettings()['target_type'] ?? NULL;
  }

  public function getFieldListOptions(string $field): array {
    return $this->entity()->get($field)->getFieldDefinition()->getSettings()['allowed_values'] ?? [];
  }

  public function setPublish(bool $published): self {
    if ($this->entity() instanceof EntityPublishedInterface) {
      if ($published) {
        $this->entity()->setPublished();
      } else {
        $this->entity()->setUnpublished();
      }
    }
    return $this;
  }

  public function setAlias(string $alias): self {
    if ($this->isNew()) throw new ZImportException('Please use "setAlias(string, bool)" only in "imported(ImportContext, EntityInterface)" state or with saved entities.');

    $storage = $this->getImporter()->getEntityStorage('path_alias');

    if ($this->entity()->getEntityTypeId() === 'node') {
      $this->entity()->set('path', ['alias' => $alias]);
      $this->entity()->save();
    } else {
      $path_alias = $storage->create([
        'path' => $this->entity()->toUrl()->toString(),
        'alias' => $alias,
      ]);
      $path_alias->save();
    }
    return $this;
  }

  public function setLanguage(string $langcode): self {
    $this->entity()->set('langcode', $langcode);
    return $this;
  }

}

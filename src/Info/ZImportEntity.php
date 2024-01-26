<?php

namespace Drupal\zero_importer\Info;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\zero_importer\Base\Importer\ZImporterInterface;
use Drupal\zero_importer\Base\Row\ZImportRowInterface;

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

}

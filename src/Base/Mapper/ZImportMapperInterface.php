<?php

namespace Drupal\zero_importer\Base\Mapper;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\zero_importer\Base\Importer\ZImporterInterface;
use Drupal\zero_importer\Base\Row\ZImportRowInterface;
use Drupal\zero_importer\Info\ZImportEntity;

interface ZImportMapperInterface {

  public function setImporter(ZImporterInterface $importer): self;

  public function getImporter(): ZImporterInterface;

  /**
   * @param ZImportRowInterface $row
   * @return ContentEntityBase|ZImportEntity|NULL
   */
  public function find(ZImportRowInterface $row);

  public function register(ZImportEntity $entity, ZImportRowInterface $row);

}

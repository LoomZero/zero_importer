<?php

namespace Drupal\zero_importer\Helper;

use Drupal;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\file\FileInterface;
use Drupal\media\MediaInterface;
use Drupal\media\MediaTypeInterface;
use Drupal\zero_importer\Base\Importer\ZeroImporterInterface;
use Drupal\zero_importer\Exception\ImporterException;
use Drupal\zero_importer\Exception\ImporterRemoteException;
use Drupal\zero_importer\Exception\ImporterRemoteThrowable;
use Drupal\zero_importer\Info\ImporterEntry;
use Drupal\zero_importer\Info\ImporterLookup;
use Drupal\zero_util\Helper\FileHelper;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class ImporterHelper {

  /**
   * @param ContentEntityBase $entity
   * @param array $options = [
   *     'include' = [0 => 'field_one', 1 => 'title'],
   *     'include_pattern' = 'field_.*',
   *     'include_type' = ['entity_reference', 'entity_reference:node', 'list_string'],
   *     'exclude' = [0 => 'field_source_id', 1 => 'field_source_hash'],
   *     'exclude_pattern' = 'field_source_.*',
   *     'exclude_type' = ['entity_reference', 'entity_reference:node', 'list_string'],
   * ]
   * @return string[]
   */
  public static function getRelevantFields(ContentEntityBase $entity, array $options): array {
    $includes = [];
    if (!empty($options['include'])) {
      $includes = $options['include'];
    }
    $excludes = [];
    if (!empty($options['exclude'])) {
      $excludes = $options['exclude'];
    }
    foreach ($entity->getFields() as $field => $definition) {
      if (!empty($options['include_pattern'])) {
        $matches = [];
        preg_match('/' . $options['include_pattern'] . '/', $field, $matches);
        if (count($matches) > 0) $includes[] = $field;
      }
      if (!empty($options['include_type'])) {
        foreach ($options['include_type'] as $type) {
          $info = explode(':', $type);
          $info[] = NULL; // add an item to emulate none bundle

          if ($definition->getFieldDefinition()->getType() === $info[0]) {
            if ($info[1] === NULL || $info[1] === $definition->getFieldDefinition()->getSettings()['target_type']) {
              $includes[] = $field;
            }
          }
        }
      }
      if (!empty($options['exclude_pattern'])) {
        $matches = [];
        preg_match('/' . $options['exclude_pattern'] . '/', $field, $matches);
        if (count($matches) > 0) $excludes[] = $field;
      }
      if (!empty($options['exclude_type'])) {
        foreach ($options['exclude_type'] as $type) {
          $info = explode(':', $type);
          $info[] = NULL; // add an item to emulate none bundle

          if ($definition->getFieldDefinition()->getType() === $info[0]) {
            if ($info[1] === NULL || $info[1] === $definition->getFieldDefinition()->getSettings()['target_type']) {
              $excludes[] = $field;
            }
          }
        }
      }
    }
    $fields = [];
    foreach ($includes as $include) {
      if (in_array($include, $excludes)) continue;
      $fields[] = $include;
    }
    return array_unique($fields);
  }

  public static function createEntity(ZeroImporterInterface $importer, string $entity_type, array $props = [], array $data = []): EntityInterface {
    $lookup = $importer->getLookup($entity_type);
    if (!isset($props['{{ _self.type.bundle }}']) && !isset($props[$lookup->getEntityDefinition()->getKey('bundle')])) {
      throw new ImporterException('Please give a {{ _self.type.bundle }} or the bundle direct item to determine the bundle for creation.');
    }
    $props = $lookup->replace($props, $importer->createEntry($data));
    $entity = $lookup->loadFirst($props);
    if ($entity === NULL) {
      $entity = $lookup->getStorage()->create($props);
    }
    foreach ($data as $field => $value) {
      $entity->set($field, $value);
    }
    if ($entity->isNew()) $lookup->getStorage()->save($entity);
    return $entity;
  }

  /**
   * @param ZeroImporterInterface $importer
   * @param string $url
   * @param array $options = [
   *     'path' => 'public://importer/',
   *     'remote' => [],
   *     'method' => 'get',
   * ]
   *
   * @return FileInterface|null
   */
  public static function createFile(ZeroImporterInterface $importer, string $url, array $options = []): ?FileInterface {
    $options = array_merge([
      'path' => 'public://importer/',
      'remote' => [],
      'method' => 'get',
    ], $options);

    /** @var Drupal\Core\File\FileSystemInterface $fs */
    $fs = Drupal::service('file_system');
    /** @var \Drupal\file\FileRepositoryInterface $fileRepository */
    $fileRepository = Drupal::service('file.repository');

    try {
      $response = $importer->remote()->request($url, $options['remote'], $options['method']);
    } catch (ImporterRemoteThrowable $exception) {
      return NULL;
    }

    $fs->prepareDirectory($options['path'], $fs::CREATE_DIRECTORY);
    $destination = $options['path'] . basename($url);

    return $fileRepository->writeData($response->getBody()->getContents(), $destination, $fs::EXISTS_REPLACE);
  }

  public static function createMedia(ZeroImporterInterface $importer, ImporterEntry $entry, array $props, string $url, array $options = []): ?MediaInterface {
    $lookup = $importer->getLookup('media');
    $props = $lookup->replace($props, $entry);

    /** @var MediaInterface $media */
    $media = $lookup->loadFirst($props);
    if ($media !== NULL) return $media;

    $url = $lookup->replace($url, $entry);
    $options = $lookup->replace($options, $entry);
    $file = self::createFile($importer, $url, $options);
    if ($file === NULL) return NULL;

    /** @var MediaInterface $media */
    $media = self::createEntity($importer, 'media', $props);
    $media->set(FileHelper::getMediaSourceField($media), $file);
    $media->save();
    return $media;
  }

}

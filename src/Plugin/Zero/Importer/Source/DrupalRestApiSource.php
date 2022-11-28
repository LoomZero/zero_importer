<?php

namespace Drupal\zero_importer\Plugin\Zero\Importer\Source;

use Drupal;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\media\Entity\Media;
use Drupal\zero_importer\Annotation\ZeroImporterSource;
use Drupal\zero_importer\Base\Importer\ZeroImporterInterface;
use Drupal\zero_importer\Base\Source\ZeroImporterRemoteSourceBase;
use Drupal\zero_importer\Exception\ImporterRemoteException;
use Drupal\zero_importer\Helper\ImporterHelper;
use Drupal\zero_importer\Info\ImporterEntry;
use Drupal\zero_util\Data\DataArray;
use Drupal\zero_util\Helper\FileHelper;

/**
 * @ZeroImporterSource(
 *   id = "drupal.rest.api",
 * )
 */
class DrupalRestApiSource extends ZeroImporterRemoteSourceBase {

  public static function entityReferenceLookupHandler(array $props): callable {
    return function(ZeroImporterInterface $importer, ContentEntityBase $entity, ImporterEntry $entry, string $field) use ($props) {
      $definition = $entity->get($field);
      $entity_type = $definition->getFieldDefinition()->getSetting('target_type');
      $bundles = $definition->getItemDefinition()->getSetting('handler_settings')['target_bundles'];
      $lookup = $importer->getLookup($entity_type);

      if (count($bundles) === 1) {
        $props['{{ _self.type.bundle }}'] = reset($bundles);
      }

      $values = $entry->get($field);
      $references = [];
      foreach ($values as $value) {
        $target = $lookup->loadFirst($lookup->replace($props, $importer->createEntry($value)));
        if ($target !== NULL) {
          $references[] = [
            'target_id' => $target->id(),
          ];
        }
      }
      $entity->set($field, $references);
    };
  }

  /**
   * @param array $options
   *
   * @return callable
   */
  public static function mediaMapper(array $options = []): callable {
    /** @var Drupal\Core\File\FileSystemInterface $fs */
    $fs = Drupal::service('file_system');
    $options = array_replace_recursive([
      'default' => [
        'update' => TRUE,
        'path' => 'public://importer/{{ fullpath }}',
        'fields' => [
          'include_pattern' => 'field_.+',
        ],
      ],
      'image' => [
        'placeholder' => 'public://placeholder.png',
      ],
    ], $options);
    return function (ZeroImporterInterface $importer, ContentEntityBase $entity, ImporterEntry $entry, string $field) use ($options, $fs) {
      $items = [];
      foreach ($entry->get($field) as $target) {
        $data = $importer->source()->getJSON('media', $target['url'], ['query' => ['_format' => 'json']]);
        $media_entry = $importer->createEntry($data);
        $bundle_options = array_replace_recursive($options['default'], ($options[$data['bundle'][0]['target_id']] ?? []));
        $source_field = FileHelper::getMediaSourceField($data['bundle'][0]['target_id']);
        $fileTarget = $data[$source_field][0];
        $size = FileHelper::getRemoteFileSize($fileTarget['url']);
        $is_placeholder = FALSE;
        $writeModifier = $fs::EXISTS_REPLACE;

        if ($size === -1) {
          // add placeholder as file to media
          if (isset($bundle_options['placeholder']) && is_string($bundle_options['placeholder'])) {
            $fileTarget['url'] = $fs->realpath($bundle_options['placeholder']);
            $size = filesize($fileTarget['url']);
            $is_placeholder = TRUE;
          } else {
            continue;
          }
        }

        // create url
        $uri = DataArray::replace($bundle_options['path'], FileHelper::parseDrupalPath($fileTarget['url']));

        // try to find file in local system
        $file = FileHelper::findFile(['uri' => $uri]);
        $media = FileHelper::loadMediaFromFile($file, $data['bundle'][0]['target_id']);

        // if no media find in local system
        if ($media === NULL) {
          if (!$is_placeholder && $file !== NULL) {
            $writeModifier = $fs::EXISTS_RENAME;
            $file = NULL;
          }
          $media = Media::create([
            'name' => $data['name'],
            'bundle' => $data['bundle'][0]['target_id'],
          ]);
        }

        // write fields from server
        $relevantFields = ImporterHelper::getRelevantFields($media, $bundle_options['fields']);
        foreach ($relevantFields as $relevantField) {
          if ($relevantField === $source_field) continue;
          if ($media_entry->has($relevantField)) {
            $media->set($relevantField, $media_entry->get($relevantField));
          }
        }

        // update file reference
        $newFileTarget = [];
        foreach ($fileTarget as $k => $v) {
          if (in_array($k, ['target_id', 'target_type', 'target_uuid', 'url'])) continue;
          $newFileTarget[$k] = $v;
        }

        // create new file if no local file is found or if file size has changed
        if (($media->get($source_field)->isEmpty() || filesize($file->getFileUri()) !== $size) && $file === NULL) {
          $fileContent = NULL;
          if ($is_placeholder) {
            $fileContent = file_get_contents($fileTarget['url']);
          } else {
            $fileContent = $importer->source()->request('file', $fileTarget['url'])->getBody()->getContents();
          }
          $file = FileHelper::createFile($fileContent, [
            'path' => $uri,
          ], $writeModifier);
        }

        // add target to file reference, save media and add it to entity field
        $newFileTarget['target_id'] = $file->id();
        $media->set($source_field, $newFileTarget);
        $media->save();
        $items[] = [
          'target_id' => $media->id(),
        ];
      }
      $entity->set($field, $items);
    };
  }

  public function index() {
    $index = $this->getJSON('index');
    if ($this->hasOption('index.items')) {
      $index = DataArray::getNested($index, $this->option('index.items'));
    }
    return $index;
  }

  public function prepare(ImporterEntry $entry, ContentEntityBase $entity): ?ImporterEntry {
    if ($this->option('prepare.remote') !== NULL) {
      if (!$this->hasOption('prepare.remote.required') || $entry->hasAll($this->option('prepare.remote.required'))) {
        $entry = $this->importer()->createEntry($this->getJSON('prepare'));
      }
    }
    return parent::prepare($entry, $entity);
  }

  /**
   * @param string $url
   *
   * @return array|null = [
   *     'source_field' => 'field_media_image',
   *     'data' => [],
   *     'file' => 'response',
   *     'size' => 0,
   * ]
   */
  public function getMediaData(string $url): ?array {
    try {
      $data = $this->getJSON('media', $url, ['query' => ['_format' => 'json']]);

      $source_field = FileHelper::getMediaSourceField($data['bundle'][0]['target_id']);
      FileHelper::getRemoteFileSize($data[$source_field][0]['url']);
      $response = $this->request('file', $data[$source_field][0]['url']);
      return [
        'source_field' => $source_field,
        'data' => $data,
        'file' => $response,
        'size' => $response->getHeader('Content-Length')[0],
      ];
    } catch (ImporterRemoteException $e) {
      return NULL;
    }
  }

}

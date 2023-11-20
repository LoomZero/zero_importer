<?php

namespace Drupal\zero_importer\Info;

use Drupal;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\zero_importer\Base\Importer\ZImporterInterface;
use Drupal\zero_importer\Exception\NoHandlerException;
use Drupal\zero_importer\Exception\NoPlaceholderException;
use Drupal\zero_util\Data\DataArray;
use Drupal\zero_util\Helper\FileHelper;

class ImporterLookup {

  private ZImporterInterface $importer;
  private string $entity_type;
  private ?ContentEntityStorageInterface $storage = NULL;
  private ?ContentEntityTypeInterface $definition = NULL;

  public function __construct(ZImporterInterface $importer, string $entity_type) {
    $this->importer = $importer;
    $this->entity_type = $entity_type;
  }

  public function getStorage(): EntityStorageInterface {
    if ($this->storage === NULL) {
      /** @noinspection PhpFieldAssignmentTypeMismatchInspection */
      $this->storage = Drupal::entityTypeManager()
        ->getStorage($this->entity_type);
    }
    return $this->storage;
  }

  public function getEntityDefinition(): EntityTypeInterface {
    if ($this->definition === NULL) {
      $this->definition = Drupal::entityTypeManager()
        ->getDefinition($this->entity_type);
    }
    return $this->definition;
  }

  public function loadFirst(array $props): ?ContentEntityBase {
    $loads = $this->getStorage()->loadByProperties($props);
    if (count($loads)) {
      /** @noinspection PhpIncompatibleReturnTypeInspection */
      return array_shift($loads);
    } else {
      return NULL;
    }
  }

  public function replace($data, array|ImporterEntry $entry = NULL, array $context = NULL, bool $replaceUnknown = TRUE) {
    if (is_array($entry)) $entry = $this->importer->createEntry($entry);
    if ($context === NULL) {
      $context = [
        'data' => &$data,
        'entry' => $entry,
        'path' => [],
      ];
    }
    if (is_array($data)) {
      $new_data = [];
      $path = $context['path'];
      foreach ($data as $key => $value) {
        $key = $this->doReplace($key, $entry, ['context' => $context, 'key' => $key, 'value' => $value, 'is_key' => TRUE], $replaceUnknown);
        if (is_array($value)) {
          $context['path'] = [...$path, $key];
          $value = $this->replace($value, $entry, $context, $replaceUnknown);
        } else if (is_string($value)) {
          $value = $this->doReplace($value, $entry, ['context' => $context, 'key' => $key, 'value' => $value, 'is_key' => FALSE], $replaceUnknown);
        }
        $new_data[$key] = $value;
      }
      return $new_data;
    } else if (is_string($data)) {
      $data = $this->doReplace($data, $entry, ['context' => $context, 'key' => NULL, 'value' => $data, 'is_key' => FALSE], $replaceUnknown);
    }
    return $data;
  }

  public function doReplace(string $value, ?ImporterEntry $entry, array $context, bool $replaceUnknown = TRUE): string {
    return DataArray::replace($value, function(string $value, string $match, string $root) use ($entry, $context) {
      $parts = explode('.', $match);
      if (str_starts_with($parts[0], '_self')) {
        return $this->doSelfReplace($entry, $value, $root, $match, $context);
      } else if (str_starts_with($parts[0], '_')) {
        return $this->doReplaceMatch(['placeholder.' . $parts[0], 'placeholder'], $entry, $value, $root, $match, $context);
      } else {
        return $this->doReplaceMatch(['placeholder'], $entry, $value, $root, $match, $context);
      }
    }, $replaceUnknown);
  }

  public function doSelfReplace(?ImporterEntry $entry, $value, $root, $match, array $context): string {
    $parts = explode('.', $match);
    array_shift($parts);
    $type = array_shift($parts);

    switch ($type) {
      case 'annotation':
        $value = DataArray::getNested($this->importer->annotation(), implode('.', $parts));
        return $value ?? '';
      case 'type':
        return $this->getEntityDefinition()->getKey($parts[0]);
      case 'option':
        return DataArray::getNested($this->importer->getOptions(), implode('.', $parts));
      case 'current':
        $current = $this->importer->getCurrent();
        if ($current === NULL) return '';
        return $current->get(implode('.', $parts));
      case 'media':
        return match ($parts[0]) {
          'source' => FileHelper::getMediaSourceField($parts[1]),
        };
      default:
        return '';
    }
  }

  public function doReplaceMatch(array $handlers, ?ImporterEntry $entry, $value, $root, $match, array $context) {
    foreach ($handlers as $handler) {
      try {
        if ($this->importer->hasHandler($handler)) {
          return $this->importer->doHandler($handler, $match, $context);
        }
      } catch (NoPlaceholderException|NoHandlerException $e) {}
    }
    return $entry?->get($match);
  }

}

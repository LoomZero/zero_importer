<?php

namespace Drupal\zero_importer\Base\Importer;

use Drupal;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Plugin\PluginBase;
use Drupal\user\Entity\User;
use Drupal\zero_importer\Base\Action\ZeroImporterActionInterface;
use Drupal\zero_importer\Base\ImporterPluginLoggerTrait;
use Drupal\zero_importer\Base\ImporterPluginOptionsTrait;
use Drupal\zero_importer\Base\Remote\ZeroImporterRemoteInterface;
use Drupal\zero_importer\Base\Source\ZeroImporterSourceInterface;
use Drupal\zero_importer\Command\ZeroImporterCommand;
use Drupal\zero_importer\Exception\ImporterCycleReferenceException;
use Drupal\zero_importer\Exception\ImporterEntryException;
use Drupal\zero_importer\Exception\ImporterException;
use Drupal\zero_importer\Exception\NoHandlerException;
use Drupal\zero_importer\Info\ImporterEntry;
use Drupal\zero_importer\Info\ImporterLookup;
use Drupal\zero_importer\Info\ImporterResult;
use Drupal\zero_importer\Service\ZeroImporterManager;
use Drupal\zero_logger\Handler\ZeroLoggerHandler;
use Drupal\zero_util\Data\DataArray;
use Drush\Commands\DrushCommands;
use Throwable;

abstract class ZeroImporterBase extends PluginBase implements ZeroImporterInterface {
  use ImporterPluginOptionsTrait;
  use ImporterPluginLoggerTrait;

  protected ?ZeroImporterInterface $parent = NULL;
  protected array $annotation = [];
  protected ?ZeroImporterRemoteInterface $remote = NULL;
  /** @var ImporterLookup[] */
  protected array $lookups = [];
  protected ?ImporterResult $result = NULL;
  protected ?ZeroImporterSourceInterface $source = NULL;
  protected ?ImporterEntry $current = NULL;
  /** @var callable[] */
  protected array $handlers = [];
  protected array $actions = [];
  protected ?ZeroLoggerHandler $logger = NULL;
  protected ZeroImporterManager $manager;
  protected ?ZeroImporterCommand $commandContext = NULL;

  public static array $registry = [];

  public static function hasRegistry(string $type, string $key): bool {
    return isset(self::$registry[$type][$key]);
  }

  public static function getRegistry(string $type, string $key) {
    if (self::hasRegistry($type, $key)) {
      return self::$registry[$type][$key];
    } else {
      return NULL;
    }
  }

  public static function setRegistry(string $type, string $key, $value) {
    self::$registry[$type][$key] = $value;
  }

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->annotation = $this->getPluginDefinition();
    $this->manager = Drupal::service('zero_importer.manager');

    $this->doInit();
  }

  public function doInit() {
    $this->init();
    $info = [ 'annotation' => $this->annotation() ];
    foreach ($this->getActions('init') as $action) {
      $action->consume('init', $info);
    }
    $this->setAnnotation($info['annotation']);
  }

  public function setCommandContext(ZeroImporterCommand $command): ZeroImporterInterface {
    $this->commandContext = $command;
    return $this;
  }

  public function getCommandContext(): ?ZeroImporterCommand {
    return $this->commandContext;
  }

  public function getParent(): ?ZeroImporterInterface {
    return $this->parent;
  }

  public function setParent(ZeroImporterInterface $parent): self {
    $this->parent = $parent;
    return $this;
  }

  public function getRoot(): ZeroImporterInterface {
    if ($this->parent !== NULL) {
      return $this->parent->getRoot();
    } else {
      return $this;
    }
  }

  public function isPrevented(string $key): bool {
    return $this->annotation()['prevent'][$key] ?? FALSE;
  }

  public function getCurrent(): ?ImporterEntry {
    return $this->current;
  }

  public function createEntry(array|ImporterEntry $data): ImporterEntry {
    if ($data instanceof ImporterEntry) return $data;
    return new ImporterEntry($data);
  }

  /**
   * This method executes the complete import process.
   * Overwrite this only when you want to change the execution process.
   *
   * @param array $options
   *
   * @return void
   */
  public function execute(array $options = []) {
    $this->result = new ImporterResult();
    $this->setOptions($options);

    try {
      $this->doBefore();

      $entries = $this->doIndex();
      $this->log('index', [
        'type' => 'note',
        'message' => 'Index {{ count }} items ...',
        'placeholders' => [ 'count' => count($entries) ],
      ]);
      foreach ($entries as $delta => $entry) {
        if ($delta !== 0) $this->logger()->setOption('prefix', '')->log('');
        if (!$entry instanceof ImporterEntry) {
          $entry = $this->createEntry($entry);
        }
        $this->current = $entry;

        $describe = $this->logIndex($delta, $entry);
        if ($describe === NULL) {
          $this->log('import', [
            'message' => 'Import item {{ delta }} ...',
            'placeholders' => ['delta' => ($delta + 1)],
          ]);
        } else {
          $this->logger()->log($describe);
        }
        $this->logger()->setOption('prefix', '- ');

        $entity = NULL;
        try {
          $entity = $this->load($entry);

          // check if we have cycle references
          if ($entity !== NULL) {
            $key = $entity->getEntityTypeId() . '::' . $entity->id();
            if (self::hasRegistry('cycle_reference', $key)) {
              throw new ImporterCycleReferenceException('The entity is already imported in this execution, key:  ' . $key);
            } else {
              self::setRegistry('cycle_reference', $key, TRUE);
            }
          }

          if ($entity === NULL) {
            $entity = $this->create($entry);
            $describe = $this->logItem($delta, $entry, $entity);
            if ($describe === NULL) {
              $this->log('create', [
                'message' => '[CREATE] {{ bundle }}',
                'placeholders' => [
                  'bundle' => $entity->bundle(),
                  'entry' => $entry->value(),
                ],
              ]);
            } else {
              $this->logger()->log($describe);
            }
          } else {
            $this->result()->addEntity($entity, ['load' => TRUE]);
            $describe = $this->logItem($delta, $entry, $entity);
            if ($describe === NULL) {
              $this->log('load', [
                'message' => '[LOAD] {{ bundle }}:{{ id }}',
                'placeholders' => [
                  'id' => $entity->id(),
                  'bundle' => $entity->bundle(),
                  'entry' => $entry->value(),
                ],
              ]);
            } else {
              $this->logger()->log($describe);
            }
          }

          $import_entry = $this->prepare($entry, $entity);
          if ($import_entry !== NULL) {
            $this->doImport($entity, $import_entry);
            $this->doSave($entity, $import_entry);
            $this->log('save', [
              'message' => '[SAVE] {{ bundle }}:{{ id }}',
              'placeholders' => [
                'id' => $entity->id(),
                'bundle' => $entity->bundle(),
                'entry' => $entry->value(),
              ],
            ]);
          } else {
            $this->result()->addEntity($entity, ['skip' => TRUE]);
            $this->log('skip', [
              'message' => '[SKIP] {{ bundle }}:{{ id }}',
              'placeholders' => [
                'id' => $entity->id(),
                'bundle' => $entity->bundle(),
                'entry' => $entry->value(),
              ],
            ]);
          }
        } catch (ImporterEntryException $entryException) {
          $entryException->setInfo($entry, $entity);
          $this->handleError($entryException);
        }
      }
      $this->logger()->setOption('prefix', '')->log('');

      $this->doAfter();
    } catch (Throwable $e) {
      $this->handleError(new ImporterException($e->getMessage(), $e->getCode(), $e));
    }
  }

  public function doExecuteClear(array $options = []) {
    try {
      $this->executeClear($options);
    } catch (Throwable $e) {
      $this->handleError(new ImporterException($e->getMessage(), $e->getCode(), $e));
    }
  }

  public function executeClear(array $options = []) {
    throw new ImporterException('The executeClear() method must be explicit defined by importer.');
  }

  /**
   * Handle error while import process.
   *
   * @param ImporterException $exception
   *
   * @return void
   */
  public function handleError(ImporterException $exception) {
    $exception->onHandle($this);
    $error = $exception;
    while (($previous = $error->getPrevious()) !== NULL) {
      $error = $previous;
    }
    $this->logger()->error([
      $error->getMessage(),
      ...array_map(function($trace) {
        $args = [];
        foreach ($trace['args'] ?? [] as $arg) {
          if (is_scalar($arg)) {
            if (is_string($arg)) {
              $args[] = 'string(' . strlen($arg) . ')';
            } else if (is_bool($arg)) {
              $args[] = $arg ? 'TRUE' : 'FALSE';
            } else {
              $args[] = $arg;
            }
          } else if (is_array($arg)) {
            $args[] = 'array(' . count($arg) . ')';
          } else {
            $args[] = get_class($arg);
          }
        }
        return ($trace['file'] ?? '<file>') . ':' . ($trace['line'] ?? '<line>') . ' ' . ($trace['class'] ?? '<class>') . ($trace['type'] ?? '<type>') . ($trace['function'] ?? '<function>') . '(' . implode(', ', $args) . ')';
      }, $error->getTrace()),
    ]);
    if (!$exception instanceof ImporterEntryException) {
      throw $exception;
    }
  }

  /**
   * Add a handler for a specific key.
   *
   * @param string $key
   * @param callable $callback
   *
   * @return $this
   */
  public function setHandler(string $key, callable $callback): self {
    $this->handlers[$key] = $callback;
    return $this;
  }

  /**
   * Check if handler exists.
   *
   * @param string $key
   *
   * @return bool
   */
  public function hasHandler(string $key): bool {
    return isset($this->handlers[$key]);
  }

  /**
   * Call handler if exists.
   *
   * @param string $key
   * @param ...$parameters
   *
   * @return mixed|null
   */
  public function doHandler(string $key, ...$parameters) {
    if ($this->hasHandler($key)) {
      return $this->handlers[$key](...$parameters);
    }
    return NULL;
  }

  public function execHandler(array $keys, callable $fallback, ...$parameters) {
    foreach ($keys as $key) {
      if ($this->hasHandler($key)) {
        try {
          return $this->doHandler($key, ...$parameters);
        } catch (NoHandlerException $e) {}
      }
    }
    try {
      return $fallback(...$parameters);
    } catch (NoHandlerException $e) {}
    return NULL;
  }

  /**
   * Get current annotation with dynamic changes.
   *
   * @return array
   */
  public function annotation(): array {
    return $this->annotation;
  }

  public function setAnnotation(array $annotation): self {
    $this->annotation = $annotation;
    return $this;
  }

  public function remote(bool $update_options = TRUE): ZeroImporterRemoteInterface {
    if ($this->remote === NULL) {
      $this->remote = $this->manager->getRemote($this->annotation()['remote']['id'] ?? 'default.remote');
      $this->remote->setImporter($this);
      $this->remote->setOptions($this->annotation()['remote'] ?? []);
    }
    if ($update_options) {
      $this->remote->setOptions($this->annotation()['remote'] ?? []);
    }
    return $this->remote;
  }

  /**
   * Get the logger.
   *
   * @return ZeroLoggerHandler
   */
  public function logger(): ZeroLoggerHandler {
    if ($this->logger === NULL) {
      $this->logger = new ZeroLoggerHandler();
    }
    return $this->logger;
  }

  public function setLogger(ZeroLoggerHandler $logger): self {
    $this->logger = $logger;
    return $this;
  }

  public function doCommand(array &$options) {
    if ($this->manager->getCurrentImporter() === NULL) {
      $this->manager->setCurrentImporter($this);
      if ($this->isPrevented('mail')) {
        $this->log('prevent.mail', [
          'type' => 'note',
          'message' => 'PREVENT [mail]: Try to prevent drupal from sending any mails caused by saving entities.',
        ]);
      }
      if ($this->isPrevented('changed_date')) {
        $this->log('prevent.changed_date', [
          'type' => 'note',
          'message' => 'PREVENT [changed_date]: Try to prevent to update the changed timestamp of entities by importer.',
        ]);
      }
    }

    if (isset($this->annotation()['user']) && is_int($this->annotation()['user'])) {
      $this->log('execute.as', [
        'type' => 'note',
        'message' => 'Execute importer as user "{{ _self.annotation.user }}"',
      ]);
      $user = User::load($this->annotation()['user']);
      user_login_finalize($user);
    }

    $info = [ 'options' => &$options ];
    foreach ($this->getActions('command') as $action) {
      $action->consume('command', $info);
    }
  }

  public function doDestroy(array &$options) {
    $info = [ 'options' => &$options ];
    foreach ($this->getActions('destroy') as $action) {
      $action->consume('destroy', $info);
    }
  }

  /**
   * Get a lookup. Lookup is a simple way to get info of an entity type.
   *
   * @param string|NULL $entity_type
   *
   * @return ImporterLookup
   */
  public function getLookup(string $entity_type = NULL): ImporterLookup {
    $entity_type = $entity_type ?? $this->annotation()['entity_type'];
    if (empty($this->lookups[$entity_type])) {
      $this->lookups[$entity_type] = new ImporterLookup($this, $entity_type);
    }
    return $this->lookups[$entity_type];
  }

  /**
   * Get the source object of this import process.
   *
   * @param bool $update_options
   * @return ZeroImporterSourceInterface|null
   */
  public function source(bool $update_options = TRUE): ?ZeroImporterSourceInterface {
    if ($this->source === NULL && isset($this->annotation()['source']['id'])) {
      $this->source = $this->manager->getSource($this->annotation()['source']['id']);
      $this->source->setImporter($this);
      $this->source->setOptions($this->annotation()['source']);
    }
    if ($update_options) {
      $this->source->setOptions($this->annotation()['source']);
    }
    return $this->source;
  }

  /**
   * @param string $action
   * @param bool $update_options
   *
   * @return ZeroImporterActionInterface[]
   */
  public function getActions(string $action, bool $update_options = TRUE): array {
    $actions = $this->annotation()['action'][$action] ?? [];
    if (isset($actions['id'])) $actions = [$actions];

    if (!array_key_exists($action, $this->actions)) {
      $this->actions[$action] = [];
      $importer_id = $this->getPluginId();
      foreach ($actions as $options) {
        $plugin = NULL;
        $singleton = FALSE;
        $definition = $this->manager->getPluginDefinition('action', $options['id']);
        if (isset($options['_definition'])) {
          $definition = array_replace_recursive($definition, $options['_definition']);
        }
        if (isset($definition['attributes']['singleton']) && $definition['attributes']['singleton'] !== FALSE) {
          $singleton = $definition['attributes']['singleton'] === TRUE ? '{{ importer_id }}:{{ id }}' : $definition['attributes']['singleton'];
          $singleton = DataArray::replace($singleton, function(string $value, string $match, string $root) use ($options, $importer_id) {
            return DataArray::getNested(array_merge([
              'importer_id' => $importer_id,
            ], $options), $match);
          });
          if (self::hasRegistry('action', $singleton)) {
            $plugin = self::getRegistry('action', $singleton);
          }
        }
        if ($plugin === NULL) {
          $plugin = $this->manager->getPlugin('action', $options['id']);
          $plugin->setImporter($this);
          $plugin->setOptions($options);
        }
        if (is_string($singleton)) {
          self::setRegistry('action', $singleton, $plugin);
        }
        $this->actions[$action][] = $plugin;
      }
    } else if ($update_options) {
      foreach ($actions as $index => $options) {
        $this->actions[$action][$index]->setOptions($options);
      }
    }
    return $this->actions[$action];
  }

  public function doBefore() {
    $this->logger()->note('Start importer "{{ importer }}"', ['placeholders' => [
      'importer' => $this->getPluginId(),
    ]]);
    $info = [];
    foreach ($this->getActions('before') as $action) {
      $action->consume('before', $info);
    }
    $this->before();
  }

  /**
   * Get the index.
   * Change this method only when you want to handle index for importChildren different.
   *
   * @return array|ImporterEntry[]
   */
  public function doIndex(): array {
    $indexdata = $this->option('indexdata');
    if (!empty($indexdata) && is_array($indexdata)) {
      return $indexdata;
    } else {
      return $this->index();
    }
  }

  /**
   * @param ContentEntityBase $entity
   * @param ImporterEntry $entry
   *
   * @return void
   */
  public function doImport(ContentEntityBase $entity, ImporterEntry $entry) {
    $info = [
      'entity' => $entity,
      'entry' => $entry,
    ];
    foreach ($this->getActions('import') as $action) {
      $action->consume('import', $info);
    }
    $this->import($entity, $entry);
  }

  /**
   * Save the content item after import.
   * Add record to the result as saved.
   *
   * @param ContentEntityBase $entity
   * @param ImporterEntry $entry
   *
   * @return void
   */
  public function doSave(ContentEntityBase $entity, ImporterEntry $entry) {
    $this->save($entity, $entry);
    $this->result()->addEntity($entity, ['save' => TRUE]);
  }

  public function doAfter() {
    $info = [ 'result' => $this->result() ];
    foreach ($this->getActions('after') as $action) {
      $action->consume('after', $info);
    }
    $this->after($this->result());
  }

  /**
   * Get the importer result.
   *
   * @return ImporterResult
   */
  public function result(): ImporterResult {
    return $this->result;
  }

  /**
   * Execute another importer for children entities.
   * *Use example*
   * <code>
   * $entity->field_items = $this->importChildren('another.id', ['ident' => 'subs'])->result()->ids();
   * </code>
   *
   * @param string $importer_id
   * @param array $options = [
   *     'indexdata' => [],
   *     'ident' => 'child item', // used to create log info, use FALSE for no prompt
   * ]
   * @param bool|array $extend = [
   *     'logger' => TRUE,
   *     'options' => TRUE,
   *     'remote' => FALSE,
   *     'source' => FALSE,
   * ]
   *
   * @return ZeroImporterInterface
   */
  public function importChildren(string $importer_id, array $options = [], bool|array $extend = TRUE): ZeroImporterInterface {
    if ($extend === TRUE) {
      $extend = [
        'logger' => TRUE,
        'options' => TRUE,
        'remote' => FALSE,
        'source' => FALSE,
      ];
    } else {
      $extend = array_merge([
        'logger' => TRUE,
        'options' => TRUE,
        'remote' => FALSE,
        'source' => FALSE,
      ], $extend);
    }
    /** @var \Drupal\zero_importer\Service\ZeroImporterManager $manager */
    $manager = Drupal::service('zero_importer.manager');

    $importer = $manager->getImporter($importer_id);
    $importer->setParent($this);

    if ($extend['logger']) {
      $importer->setLogger($this->logger()->createChild($options['ident'] ?? $importer_id));
    }
    if ($extend['options']) {
      $options = array_merge($this->options, $options);
    }
    if ($extend['remote']) {
      $importer->remote = $this->remote;
    }
    if ($extend['source']) {
      $importer->source = $this->source;
    }

    $importer->execute($options);
    return $importer;
  }

  public function logIndex(int $delta, ImporterEntry $index): ?string {
    return NULL;
  }

  public function logItem(int $delta, ImporterEntry $index, ContentEntityBase $entity): ?string {
    return NULL;
  }

  /**
   * Executed by creation of the importer (__construct).
   *
   * @return void
   */
  protected function init() { }

  /**
   * Execute once before the importer is running.
   * Change this method if you want to add dynamic annotation properties.
   *
   * @return void
   */
  protected function before() { }

  /**
   * Importer method;
   * Get the index for this importer.
   *
   * @return array|ImporterEntry[]
   */
  public function index(): array {
    if (isset($this->annotation()['source']['index'])) {
      return $this->source()->index();
    }
    throw new ImporterException('The index method must be implemented or the annotation key "source.index" must be defined.');
  }

  /**
   * Importer method;
   * Try to load the content connected to the index data.
   * If NULL is returned a new content will be created with `->create()`.
   *
   * @param ImporterEntry $entry
   *
   * @return ContentEntityBase|null
   */
  public function load(ImporterEntry $entry): ?ContentEntityBase {
    if (!empty($this->annotation()['load'])) {
      $lookup = $this->getLookup();
      $props = $lookup->replace($this->annotation()['load'], $entry);
      return $lookup->loadFirst($props);
    }
    return NULL;
  }

  /**
   * Importer method;
   * Try to create the content for the index data.
   * Only called when `->load()` returned NULL.
   *
   * @param ImporterEntry $entry
   *
   * @return ContentEntityBase
   */
  public function create(ImporterEntry $entry): ContentEntityBase {
    if (!empty($this->annotation()['load'])) {
      $lookup = $this->getLookup();
      $props = $lookup->replace($this->annotation()['load'], $entry);
      $entity = $lookup->getStorage()->create($props);
      if ($entity instanceof ContentEntityBase) {
        return $entity;
      }
      throw new ImporterException('Only ContentEntity is supported.');
    }
    throw new ImporterException('The create method must be implemented or the load key must be defined by annotation.');
  }

  /**
   * Importer method;
   * Load the content for one item import process after load/create.
   * Use this method to validate the import data.
   * If NULL is returned the item will be skipped.
   *
   * @param ImporterEntry $entry
   * @param ContentEntityBase $entity
   *
   * @return ImporterEntry|null
   */
  public function prepare(ImporterEntry $entry, ContentEntityBase $entity): ?ImporterEntry {
    if (isset($this->annotation()['source']['prepare'])) {
      return $this->source()->prepare($entry, $entity);
    }
    return $entry;
  }

  /**
   * Importer method;
   * The main method to import data into entity.
   *
   * @param ContentEntityBase $entity
   * @param ImporterEntry $entry
   *
   * @return void
   */
  public function import(ContentEntityBase $entity, ImporterEntry $entry) { }

  /**
   * Importer method;
   * Save the content item after import.
   *
   * @param ContentEntityBase $entity
   * @param ImporterEntry $entry
   *
   * @return void
   */
  public function save(ContentEntityBase $entity, ImporterEntry $entry) {
    $this->getLookup()->getStorage()->save($entity);
  }

  /**
   * Importer method;
   * Execute after the import process.
   * Use this method to unpublish nodes.
   *
   * *Unpublish all entities that are not imported.*
   * <code>
   * $lookup = $this->getLookup();
   * $query = $lookup->getStorage()->getQuery();
   * $ids = $this->result()->ids($this->annotation()['entity_type']);
   * $query->condition($lookup->getEntityDefinition()->getKey('id'), $ids, 'NOT IN');
   * </code>
   *
   * @return void
   */
  public function after(ImporterResult $result) { }

}

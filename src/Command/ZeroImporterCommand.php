<?php

namespace Drupal\zero_importer\Command;

use Consolidation\AnnotatedCommand\AnnotationData;
use Drupal;
use Drupal\zero_importer\Service\ZeroImporterPluginManager;
use Drupal\zero_logger\Base\ZeroLoggerInterface;
use Drush\Commands\DrushCommands;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputOption;

class ZeroImporterCommand extends DrushCommands {

  public function createProgressBar($steps = 100): ProgressBar {
    $progressBar = new ProgressBar($this->output(), $steps);
    $progressBar->setFormat("%current%/%max% [%bar%] %percent%%\n  %message%<error>%error%</error>");
    $progressBar->setMessage('');
    $progressBar->setMessage('', 'error');
    return $progressBar;
  }

  /**
   * @command zero_importer:list
   * @aliases importer-list
   * @usage drush importer-list
   */
  public function list() {
    /** @var ZeroImporterPluginManager $manager */
    $manager = Drupal::service('plugin.manager.zero_importer');

    foreach ($manager->getDefinitions() as $definition) {
      $this->output()->writeln('- ' . $definition['id']);
    }
  }

  /**
   * @command zero_importer:clear
   * @aliases importer-clear
   * @usage drush importer-clear my_importer
   */
  public function clear(string $importer_id, array $options = []) {
    /** @var \Drupal\zero_importer\Service\ZeroImporterManager $manager */
    $manager = Drupal::service('zero_importer.manager');

    $importer = $manager->getImporter($importer_id);
    $importer->setCommandContext($this);
    $definition = $importer->annotation();

    if (!empty($definition['options'])) {
      foreach ($definition['options'] as $option => $fallback) {
        if ($options[$option] === NULL) $options[$option] = $fallback;
      }
    }

    $importer->executeClear($options);
  }

  /**
   * @command zero_importer:execute
   * @aliases importer-execute
   * @option log [bool] print the progress of command into the console
   * @option log-level [int|string] the level for the logging
   * @option log-file [string] path to log file
   * @option log-file-date [string] date to use for the log file
   * @option log-file-channel [string] date to use for the log file
   * @option log-file-level [int|string] the level for the logging
   * @usage drush importer-execute my_importer
   */
  public function execute(string $importer_id, array $options = ['log' => FALSE, 'log-level' => '', 'log-file' => '', 'log-file-date' => '', 'log-file-channel' => '', 'log-file-level' => '']) {
    /** @var \Drupal\zero_importer\Service\ZeroImporterManager $manager */
    $manager = Drupal::service('zero_importer.manager');

    $importer = $manager->getImporter($importer_id);
    $importer->setCommandContext($this);
    $definition = $importer->annotation();
    if (!empty($definition['options'])) {
      foreach ($definition['options'] as $option => $fallback) {
        if ($options[$option] === NULL) $options[$option] = $fallback;
      }
    }

    if ($options['log']) {
      $importer->logger()->createLogger('drush', [
        'input' => $this->input(),
        'output' => $this->output(),
        'level' => $options['log-level'] ?: ZeroLoggerInterface::LOGGER_LEVEL_LOG,
      ]);
    } else if (isset($definition['logger']['options']['drush'])) {
      $importer->logger()->createLogger('drush', array_replace_recursive($definition['logger']['options']['drush'], [
        'input' => $this->input(),
        'output' => $this->output(),
      ]));
    }

    if (!empty($options['log-file'])) {
      $importer->logger()->createLogger('file', [
        'path' => $options['log-file'],
        'date' => $options['log-file-date'],
        'channel' => $options['log-file-channel'] ?: $importer_id,
        'level' => $options['log-file-level'] ?: ZeroLoggerInterface::LOGGER_LEVEL_LOG,
      ]);
    } else if (!empty($definition['logger']['options']['file'])) {
      if (empty($definition['logger']['options']['file']['channel'])) {
        $definition['logger']['options']['file']['channel'] = $importer_id;
      }
      $importer->logger()->createLogger('file', $definition['logger']['options']['file']);
    }

    $importer->doCommand($options);
    $importer->execute($options);
    $importer->doDestroy($options);
  }

  public function addDynamicOptions(Command $command, AnnotationData $annotationData) {
    /** @var ZeroImporterPluginManager $manager */
    $manager = Drupal::service('plugin.manager.zero_importer');

    $options = [];
    foreach ($manager->getDefinitions() as $definition) {
      if (empty($definition['options'])) break;
      foreach ($definition['options'] as $option => $fallback) {
        $options[$option] = $option;
      }
    }
    foreach ($options as $option) {
      $command->addOption(
        $option,
        '',
        InputOption::VALUE_OPTIONAL,
        'Dynamic option',
      );
    }
  }

  /**
   * @hook option zero_importer:execute
   */
  public function addDynamicExecuteOptions(Command $command, AnnotationData $annotationData) {
    $this->addDynamicOptions($command, $annotationData);
  }

  /**
   * @hook option zero_importer:clear
   */
  public function addDynamicClearOptions(Command $command, AnnotationData $annotationData) {
    $this->addDynamicOptions($command, $annotationData);
  }

}

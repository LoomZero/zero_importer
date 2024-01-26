<?php

namespace Drupal\zero_importer\Command;

use Consolidation\AnnotatedCommand\AnnotationData;
use Drupal;
use Drupal\zero_importer\Service\ZeroImporterPluginManager;
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
   * @aliases zi:list
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
   * @command zi:clear
   * @aliases importer-clear
   * @usage drush importer-clear my_importer
   */
  public function clear(array $options = []) {
    $users = Drupal::entityTypeManager()->getStorage('user')->loadMultiple();
    foreach ($users as $user) {
      if (!in_array($user->id(), [0, 1])) {
        $user->delete();
      }
    }
  }

  /**
   * @command zero_importer:execute
   * @aliases zi:exe
   * @usage drush importer-execute my_importer
   */
  public function execute(string $importer_id, array $options = ['log' => FALSE, 'log-level' => '', 'log-file' => '', 'log-file-date' => '', 'log-file-channel' => '', 'log-file-level' => '']) {
    /** @var ZeroImporterPluginManager $manager */
    $manager = Drupal::service('plugin.manager.zero_importer');

    $importer = $manager->getImporter($importer_id);

    $importer->doExecute();
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
   * @hook_back option zero_importer:execute
   */
  public function addDynamicExecuteOptions(Command $command, AnnotationData $annotationData) {
    $this->addDynamicOptions($command, $annotationData);
  }

  /**
   * @hook_back option zero_importer:clear
   */
  public function addDynamicClearOptions(Command $command, AnnotationData $annotationData) {
    $this->addDynamicOptions($command, $annotationData);
  }

}

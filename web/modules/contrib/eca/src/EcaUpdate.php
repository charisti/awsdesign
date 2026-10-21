<?php

declare(strict_types=1);

namespace Drupal\eca;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\eca\Service\Actions;
use Drupal\eca\Service\Conditions;
use Drupal\eca\Service\Modellers;

/**
 * Provides methods to update all existing ECA models and to output messages.
 */
final class EcaUpdate {

  /**
   * List of info messages.
   *
   * @var array
   */
  protected array $infos;

  /**
   * List of warning messages.
   *
   * @var array
   */
  protected array $warnings;

  /**
   * List of errors.
   *
   * @var array
   */
  protected array $errors;

  /**
   * Constructs an EcaUpdate object.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly Modellers $ecaServiceModeller,
    private readonly MessengerInterface $messenger,
    private readonly Modellers $modellerServices,
    private readonly Conditions $conditionServices,
    private readonly Actions $actionServices,
  ) {}

  /**
   * Updates all existing ECA entities calling ::updateModel in their modeller.
   */
  public function updateAllModels(): void {
    $this->errors = [];
    $this->warnings = [];
    $this->infos = [];
    /** @var \Drupal\eca\Entity\Eca $eca */
    foreach ($this->entityTypeManager->getStorage('eca')->loadMultiple() as $eca) {
      if ($eca->hasModel()) {
        $modeller = $this->ecaServiceModeller->getModeller($eca->get('modeller'));
        if ($modeller === NULL) {
          $this->errors[] = '[' . $eca->label() . '] This modeller plugin ' . $eca->get('modeller') . ' for this ECA model does not exist.';
          continue;
        }
        $model = $eca->getModel();
        $modeller->setConfigEntity($eca);
        if ($modeller->updateModel($model)) {
          $filename = $model->getFilename();
          if ($filename && file_exists($filename)) {
            file_put_contents($filename, $model->getModeldata());
          }
          try {
            $modeller->save($model->getModeldata(), $filename);
            $this->warnings[] = '[' . $eca->label() . '] Model has been updated.';
          }
          catch (\LogicException | EntityStorageException $e) {
            $this->errors[] = '[' . $eca->label() . '] Error while updating this model: ' . $e->getMessage();
          }
        }
        else {
          $this->infos[] = '[' . $eca->label() . '] Model does not require any updates.';
        }
      }
      else {
        $allPlugins = [
          'events' => $this->modellerServices->events(),
          'conditions' => $this->conditionServices->conditions(),
          'actions' => $this->actionServices->actions(),
        ];
        $changed = FALSE;
        foreach ($allPlugins as $type => $plugins) {
          $items = $eca->get($type) ?? [];
          foreach ($items as &$item) {
            $result = array_filter($plugins, function ($plugin) use ($item) {
              return $plugin->getPluginId() === $item['plugin'];
            });
            $plugin = reset($result);
            if ($plugin && method_exists($plugin, 'defaultConfiguration')) {
              foreach ($plugin->defaultConfiguration() as $key => $value) {
                if (!isset($item['configuration'][$key])) {
                  $item['configuration'][$key] = $value;
                  $changed = TRUE;
                }
              }
            }
          }
          $eca->set($type, $items);
        }
        if ($changed) {
          $eca->save();
          $this->warnings[] = '[' . $eca->label() . '] Model has been updated.';
        }
        else {
          $this->infos[] = '[' . $eca->label() . '] Model does not require any updates.';
        }
      }
    }
  }

  /**
   * Gets the list of all collected error messages.
   *
   * @return array
   *   The list of all collected error messages.
   */
  public function getErrors(): array {
    return $this->errors;
  }

  /**
   * Gets the list of all collected warning messages.
   *
   * @return array
   *   The list of all collected warning messages.
   */
  public function getWarnings(): array {
    return $this->warnings;
  }

  /**
   * Gets the list of all collected info messages.
   *
   * @return array
   *   The list of all collected info messages.
   */
  public function getInfos(): array {
    return $this->infos;
  }

  /**
   * Outputs all warning and error messages to user, plus a summary.
   */
  public function displayMessages(): void {
    foreach ($this->infos ?? [] as $info) {
      $this->messenger->addMessage($info);
    }
    foreach ($this->warnings ?? [] as $warning) {
      $this->messenger->addWarning($warning);
    }
    foreach ($this->errors ?? [] as $error) {
      $this->messenger->addError($error);
    }
    $this->messenger->addMessage("Models not requiring updates: " . count($this->infos ?? []));
    $this->messenger->addMessage("Models updated: " . count($this->warnings ?? []));
    $this->messenger->addMessage("Errors reported: " . count($this->errors ?? []));
  }

}

<?php

declare(strict_types=1);

namespace Drupal\layout_builder_restrictions\Plugin\ConfigAction;

use Drupal\Core\Config\Action\Attribute\ConfigAction;
use Drupal\Core\Config\Action\ConfigActionException;
use Drupal\Core\Config\Action\ConfigActionPluginInterface;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Appends one or more blocks to restricted blocks.
 *
 * @internal
 *   This API is experimental.
 */
#[ConfigAction(
  id: 'entityViewModeRestrictionAppendRestrictedBlocks',
  admin_label: new TranslatableMarkup('Appends one or more blocks to restricted blocks in layout builder restrictions.'),
  entity_types: ['entity_view_display'],
)]
final class EntityViewModeRestrictionRestrictBlocks implements ConfigActionPluginInterface, ContainerFactoryPluginInterface {

  public function __construct(
    private readonly ConfigManagerInterface $configManager,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get(ConfigManagerInterface::class),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function apply(string $configName, mixed $values): void {
    $entity = $this->configManager->loadConfigEntityByName($configName);
    assert($entity instanceof ConfigEntityInterface);
    assert(is_array($values));

    $third_party_settings = $entity->get('third_party_settings');
    if (!isset($third_party_settings["layout_builder"]["enabled"]) || !$third_party_settings["layout_builder"]["enabled"]) {
      // Skip if Layout Builder is not enabled for this viewmode/entity.
      return;
    }

    if (!in_array('blocks', array_keys($values))) {
      throw new ConfigActionException("You need to define at least one block to append.");
    }

    foreach ($values["blocks"] as $block_category => $block_ids) {
      foreach ($block_ids as $block_id) {
        if (!isset($third_party_settings["layout_builder_restrictions"]['entity_view_mode_restriction']["denylisted_blocks"][$block_category])) {
          $third_party_settings["layout_builder_restrictions"]['entity_view_mode_restriction']["denylisted_blocks"][$block_category] = [];
        }
        if (!in_array($block_id, $third_party_settings["layout_builder_restrictions"]['entity_view_mode_restriction']["denylisted_blocks"][$block_category])) {
          $third_party_settings["layout_builder_restrictions"]['entity_view_mode_restriction']["denylisted_blocks"][$block_category][] = $block_id;
        }
      }
    }

    $entity->set('third_party_settings', $third_party_settings);

    // Save the entity.
    $entity->save();
  }

}

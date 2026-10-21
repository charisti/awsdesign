<?php

declare(strict_types=1);

namespace Drupal\layout_builder_restrictions_by_region\Plugin\ConfigAction;

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
  id: 'entityViewModeRestrictionByRegionAppendRestrictedBlocks',
  admin_label: new TranslatableMarkup('Appends one or more blocks to restricted blocks in layout builder restrictions.'),
  entity_types: ['entity_view_display'],
)]
final class EntityViewModeRestrictionByRegionAppendRestrictedBlocks implements ConfigActionPluginInterface, ContainerFactoryPluginInterface {

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

    foreach ($values as $value) {
      if (!in_array('blocks', array_keys($value))) {
        throw new ConfigActionException("You need to define at least one block to append.");
      }
      if (!in_array('layouts', array_keys($value))) {
        throw new ConfigActionException("You need to define at least one layout to append.");
      }

      $regions = $value['regions'] ?? ['all_regions'];

      foreach ($value['layouts'] as $layout) {
        foreach ($regions as $region) {
          foreach ($value["blocks"] as $block_category => $block_ids) {
            foreach ($block_ids as $block_id) {
              if (!isset($third_party_settings["layout_builder_restrictions"]["entity_view_mode_restriction_by_region"]["denylisted_blocks"][$layout][$region][$block_category])) {
                $third_party_settings["layout_builder_restrictions"]["entity_view_mode_restriction_by_region"]["denylisted_blocks"][$layout][$region][$block_category] = [];
              }
              if (!in_array($block_id, $third_party_settings["layout_builder_restrictions"]["entity_view_mode_restriction_by_region"]["denylisted_blocks"][$layout][$region][$block_category])) {
                $third_party_settings["layout_builder_restrictions"]["entity_view_mode_restriction_by_region"]["denylisted_blocks"][$layout][$region][$block_category][] = $block_id;
              }
            }
          }
        }
      }
    }

    $entity->set('third_party_settings', $third_party_settings);

    // Save the entity.
    $entity->save();
  }

}

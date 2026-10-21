<?php

namespace Drupal\layout_builder_restrictions\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\layout_builder_restrictions\Form\FormAlter;

/**
 * Hook implementations for layout_builder_restrictions.
 */
class LayoutBuilderRestrictionsHooks {

  /**
   * Implements hook_plugin_filter_TYPE__CONSUMER_alter().
   *
   * Curate the blocks available in the Layout Builder "Add Block" UI.
   */
  #[Hook('plugin_filter_block__layout_builder_alter')]
  public static function pluginFilterBlockLayoutBuilderAlter(array &$definitions, array $extra) {
    if (isset($extra['list']) && !isset($extra['browse'])) {
      // Do not alter the block definitions when 'Add block' is clicked.
      return;
    }
    $layout_builder_restrictions_manager = \Drupal::service('plugin.manager.layout_builder_restriction');
    $restriction_plugins = $layout_builder_restrictions_manager->getSortedPlugins();
    foreach (array_keys($restriction_plugins) as $id) {
      $plugin = $layout_builder_restrictions_manager->createInstance($id);
      $definitions = $plugin->alterBlockDefinitions($definitions, $extra);
    }
  }

  /**
   * Implements hook_plugin_filter_TYPE__CONSUMER_alter().
   *
   * Curate the layouts available in the Layout Builder "Add Section" UI.
   */
  #[Hook('plugin_filter_layout__layout_builder_alter')]
  public static function pluginFilterLayoutLayoutBuilderAlter(array &$definitions, array $extra) {
    $layout_builder_restrictions_manager = \Drupal::service('plugin.manager.layout_builder_restriction');
    $restriction_plugins = $layout_builder_restrictions_manager->getSortedPlugins();
    foreach (array_keys($restriction_plugins) as $id) {
      $plugin = $layout_builder_restrictions_manager->createInstance($id);
      $definitions = $plugin->alterSectionDefinitions($definitions, $extra);
    }
  }

  /**
   * Implements hook_form_FORM_ID_alter() for the entity view display edit form.
   */
  #[Hook('form_entity_view_display_edit_form_alter')]
  public static function formEntityViewDisplayEditFormAlter(&$form, FormStateInterface $form_state, $form_id) {
    // Alter the entity view display form to set the allowed block categories.
    \Drupal::classResolver(FormAlter::class)->alterEntityViewDisplayFormAllowedBlockCategories($form, $form_state, $form_id);
    $entity_view_mode_restriction_active = TRUE;
    if ($config = \Drupal::config('layout_builder_restrictions.plugins')->get('plugin_config')) {
      // Provide the per view mode restriction UI *unless* plugin is disabled.
      if (isset($config['entity_view_mode_restriction']) && $config['entity_view_mode_restriction']['enabled'] == FALSE) {
        $entity_view_mode_restriction_active = FALSE;
      }
    }
    if ($entity_view_mode_restriction_active) {
      \Drupal::classResolver(FormAlter::class)->alterEntityViewDisplayForm($form, $form_state, $form_id);
    }
  }

}

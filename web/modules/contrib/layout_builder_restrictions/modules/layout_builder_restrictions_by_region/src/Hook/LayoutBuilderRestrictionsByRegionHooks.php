<?php

namespace Drupal\layout_builder_restrictions_by_region\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\layout_builder_restrictions_by_region\Form\FormAlter;
use Drupal\layout_builder_restrictions_by_region\Form\RestrictionPluginConfigAlter;

/**
 * Hook implementations for layout_builder_restrictions_by_region.
 */
class LayoutBuilderRestrictionsByRegionHooks {

  /**
   * Implements hook_form_FORM_ID_alter() for the entity view display edit form.
   */
  #[Hook('form_entity_view_display_edit_form_alter')]
  public static function formEntityViewDisplayEditFormAlter(&$form, FormStateInterface &$form_state, $form_id) {
    $entity_view_mode_restriction_active = TRUE;
    if ($config = \Drupal::config('layout_builder_restrictions.plugins')->get('plugin_config')) {
      // Provide the per view mode restriction UI *unless* plugin is disabled.
      if (isset($config['entity_view_mode_restriction_by_region']) && $config['entity_view_mode_restriction_by_region']['enabled'] == FALSE) {
        $entity_view_mode_restriction_active = FALSE;
      }
    }
    if ($entity_view_mode_restriction_active) {
      \Drupal::classResolver(FormAlter::class)->alterEntityViewDisplayForm($form, $form_state, $form_id);
    }
  }

  /**
   * Implements hook_form_FORM_ID_alter() for the general config form.
   */
  #[Hook('form_restriction_plugin_config_form_alter')]
  public static function formRestrictionPluginConfigFormAlter(&$form, FormStateInterface $form_state, $form_id) {
    // Alter the general config form to include the by-region configuration.
    \Drupal::classResolver(RestrictionPluginConfigAlter::class)->alterForm($form, $form_state, $form_id);
  }

}

<?php

namespace Drupal\radix\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for radix.
 */
class FieldHooks {
  /**
   * @file
   * Theme and preprocess functions for fields.
   */

  /**
   * Implements hook_preprocess_field().
   *
   * @phpstan-param array<string, mixed> $variables
   */
  #[Hook('preprocess_field')]
  public static function preprocessField(array &$variables): void {
    $element = $variables['element'];
    $field_name = $element['#field_name'];
    $bundle = $element['#bundle'];
    // Add bundle to template.
    $variables['bundle'] = $bundle;
    // Add a clean field name without the field_BUNDLE_ prefix.
    $safe_field_name_prefix = 'field_' . $bundle . '_';
    $variables['field_name_clean'] = str_replace($safe_field_name_prefix, '', $field_name);
  }

}

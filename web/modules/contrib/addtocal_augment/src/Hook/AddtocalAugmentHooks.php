<?php

namespace Drupal\addtocal_augment\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for addtocal_augment.
 */
class AddtocalAugmentHooks {
  /**
   * @file
   * Contains addtocal_augment.module.
   */

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public static function theme($existing, $type, $theme, $path) {
    return [
      'addtocal_links' => [
        'variables' => [
          'label' => NULL,
          'ical' => NULL,
          'outlook' => NULL,
          'google' => NULL,
          'id' => NULL,
          'icons' => NULL,
        ],
      ],
      'addtocal_links__modal' => [
        'variables' => [
          'label' => NULL,
          'ical' => NULL,
          'outlook' => NULL,
          'google' => NULL,
          'id' => NULL,
          'icons' => NULL,
        ],
      ],
    ];
  }

}

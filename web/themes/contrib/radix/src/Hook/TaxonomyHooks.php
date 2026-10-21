<?php

namespace Drupal\radix\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for radix.
 */
class TaxonomyHooks {
  /**
   * @file
   * Theme and preprocess functions for taxonomy and vocabularies.
   */

  /**
   * Implements hook_theme_suggestions_taxonomy_term_alter().
   *
   * @phpstan-param list<string> $suggestions
   * @phpstan-param array<string, mixed> $variables
   */
  #[Hook('theme_suggestions_taxonomy_term_alter')]
  public static function themeSuggestionsTaxonomyTermAlter(array &$suggestions, array $variables): void {
    /** @var \Drupal\taxonomy\TermInterface $term */
    $term = $variables['elements']['#taxonomy_term'];
    $sanitized_view_mode = strtr($variables['elements']['#view_mode'], '.', '_');
    // Add view mode theme suggestions.
    $suggestions[] = 'taxonomy_term__' . $sanitized_view_mode;
    $suggestions[] = 'taxonomy_term__' . $term->bundle() . '__' . $sanitized_view_mode;
    $suggestions[] = 'taxonomy_term__' . $term->id() . '__' . $sanitized_view_mode;
  }

}

<?php

namespace Drupal\radix\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for radix.
 */
class MenuHooks {

  /**
   * Implements hook_preprocess_menu().
   *
   * @phpstan-param array<string, mixed> $variables
   */
  #[Hook('preprocess_menu')]
  public static function preprocessMenu(array &$variables, string $hook): void {
    // No changes for menu toolbar.
    if ($hook == 'menu__toolbar') {
      return;
    }
    $variables['#cache']['contexts'][] = 'url.path';

    $request = \Drupal::request();
    $current_uri = $request->getRequestUri();
    $current_path = $request->getBaseUrl() . $request->getPathInfo();

    foreach ($variables['items'] as $key => $item) {
      if (!isset($item['url']) || !is_object($item['url'])) {
        continue;
      }
      $url = $item['url']->toString();

      // Links that only differ by query string can only be matched on it.
      if (str_contains($url, '?')) {
        $variables['#cache']['contexts'][] = 'url.query_args';
      }

      if ($url === $current_path || $url === $current_uri) {
        $variables['items'][$key]['in_active_trail'] = TRUE;
      }
      if ($item['url']->isRouted() && $item['url']->getRouteName() === '<nolink>') {
        $variables['items'][$key]['attributes']->addClass('navbar-text');
      }
    }
  }

}

---
hide:
  - toc
---

# Custom restrictions using hooks (for developers)

Site-specific restrictions not requiring UI-based changes can be done by implementing [hook\_plugin\_filter\_TYPE\_\_CONSUMER\_alter()](https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Plugin%21plugin.api.php/function/hook_plugin_filter_TYPE__CONSUMER_alter/11.x) (which is invoked for both themes and modules). Examples follow.

## Restrict certain blocks across all Layout Builder usage sitewide

```php
/**
 * Implements hook_plugin_filter_TYPE__CONSUMER_alter().
 */
#[Hook('plugin_filter_block__layout_builder_alter')]
function pluginFilterBlockLayoutBuilderAlter(array &$definitions) {
  // Explicitly remove the "Help" blocks from the list.
  unset($definitions['help_block']);
  // Explicitly remove the "Sticky at top of lists field_block".
  $disallowed_fields = [
    'sticky',
  ];
  foreach ($definitions as $plugin_id => $definition) {
    // Field block IDs are in the form 'field_block:{entity}:{bundle}:{name}',
    // for example 'field_block:node:article:revision_timestamp'.
    preg_match('/field_block:.*:.*:(.*)/', $plugin_id, $parts);
    if (isset($parts[1]) && in_array($parts[1], $disallowed_fields, TRUE)) {
      // Unset any field blocks that match our predefined list.
      unset($definitions[$plugin_id]);
    }
  }
}
```

## Restrict blocks on a specific entity type

```php
use Drupal\layout_builder\SectionStorageInterface;
use Drupal\node\NodeInterface;

/**
* Implements hook_plugin_filter_TYPE__CONSUMER_alter().
*/
#[Hook('plugin_filter_block__layout_builder_alter')]
public static function pluginFilterBlockLayoutBuilderAlter(array &$definitions, array $extra) {
  $disallowed_categories = [
    'Chaos Tools',
    'Content fields',
    'Forms',
    'Last Updated',
    'System',
    'User',
    'core',
  ];
  if (isset($extra['section_storage']) && $extra['section_storage'] instanceof SectionStorageInterface) {
    $section_storage = $extra['section_storage'];
    // Extract the entity from the storage.
    $entity = $section_storage->getContextValue('entity');
    // Ensure it is a Node of type `utexas_flex_page`.
    if ($entity instanceof NodeInterface && $entity->bundle() === 'MY_NODE_TYPE') {
      foreach ($definitions as $plugin_id => $definition) {
        if (in_array($definition['category'], $disallowed_categories)) {
          unset($definitions[$plugin_id]);
        }
      }
    }
  }
}
```

## Restrict access to layouts and blocks by user role

```php
/**
 * Implements hook_plugin_filter_TYPE__CONSUMER_alter().
 */
#[Hook('plugin_filter_layout_layout_builder_alter')]
function pluginFilterLayoutLayoutBuilderAlter(array &$definitions, array $extra) {
  $currentUser = User::load(\Drupal::currentUser()->id());
  if (isset($definitions['diagonal']) && !$currentUser->hasRole('administrator')) {
    unset($definitions['diagonal']);
  }
}
/**
 * Implements hook_plugin_filter_TYPE__CONSUMER_alter().
 */
#[Hook('plugin_filter_block__layout_builder_alter')]
function pluginFilterBlockLayoutBuilderAlter(array &$definitions, array $extra) {
  $currentUser = User::load(\Drupal::currentUser()->id());
  if (!$currentUser->hasRole('administrator')) {
    unset($definitions['inline_block:icon_with_text']);
    unset($definitions['inline_block:news_cards_external']);
    unset($definitions['inline_block:program_list']);
    unset($definitions['inline_block:profile_collection']);
    unset($definitions['inline_block:view']);
    unset($definitions['inline_block:work_study_list']);
    unset($definitions['inline_block:advanced_accordion']);
  }
}
```

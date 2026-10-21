<?php

declare(strict_types=1);

namespace Drupal\layout_builder_restrictions\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a Layout Builder Restriction attribute for plugin discovery.
 *
 * @see plugin_api
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class LayoutBuilderRestriction extends Plugin {

  /**
   * Constructs a LayoutBuilderRestriction attribute.
   *
   * @param string $id
   *   The plugin ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $title
   *   The human-readable name of the restriction plugin.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $description
   *   The human-readable description of the restriction plugin.
   */
  public function __construct(
    public readonly string $id,
    public readonly TranslatableMarkup $title,
    public readonly TranslatableMarkup $description,
  ) {
  }

}

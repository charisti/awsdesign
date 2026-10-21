<?php

declare(strict_types=1);

namespace Drupal\date_augmenter\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a Date Augmenter attribute object.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class DateAugmenter extends Plugin {

  /**
   * Constructs a DateAugmenter attribute.
   *
   * @param string $id
   *   The plugin ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $label
   *   The plugin label.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $description
   *   The plugin description.
   * @param int $weight
   *   This is relative to other date augmenters in the Field UI
   *   when selecting date augmenters for a given field instance.
   */
  public function __construct(
    public readonly string $id,
    public readonly TranslatableMarkup $label,
    public readonly ?TranslatableMarkup $description = NULL,
    public readonly int $weight = 0,
  ) {
  }

}

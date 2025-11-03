<?php

declare(strict_types=1);

namespace Drupal\Tests\drupal_cms_olivero\Functional;

use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests this theme's compatibility with Canvas.
 */
#[Group('drupal_cms_olivero')]
final class CanvasCompatibilityTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'drupal_cms_olivero';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['canvas'];

  /**
   * Tests that Canvas can be installed alongside this theme.
   */
  public function test(): void {
    // If this test passes at all, it means Canvas can be installed alongside
    // this theme, and that's all we care about right now.
  }

}

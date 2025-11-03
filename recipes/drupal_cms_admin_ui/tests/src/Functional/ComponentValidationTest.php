<?php

declare(strict_types=1);

namespace Drupal\Tests\drupal_cms_admin_theme\Functional;

use Drupal\FunctionalTests\Core\Recipe\RecipeTestTrait;
use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;

#[Group('drupal_cms_admin_ui')]
#[IgnoreDeprecations]
class ComponentValidationTest extends BrowserTestBase {

  use RecipeTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  public function test(): void {
    $dir = realpath(__DIR__ . '/../../..');

    // The recipe should apply cleanly.
    $this->applyRecipe($dir);
    // Apply it again to prove that it is idempotent.
    $this->applyRecipe($dir);

    // Ensure that there are no broken blocks in the navigation (or anywhere
    // else). We need to test this with the root user because they have all
    // permissions, and therefore any broken blocks in the navigation will be
    // obvious to them.
    $this->drupalLogin($this->rootUser);
    $this->assertSession()->pageTextNotContains('This block is broken or missing.');
  }

}

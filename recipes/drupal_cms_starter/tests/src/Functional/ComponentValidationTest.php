<?php

declare(strict_types=1);

namespace Drupal\Tests\drupal_cms_starter\Functional;

use Behat\Mink\Element\NodeElement;
use Composer\InstalledVersions;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\drupal_cms_content_type_base\ContentModelTestTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

#[Group('drupal_cms_starter')]
#[IgnoreDeprecations]
class ComponentValidationTest extends BrowserTestBase {

  use ContentModelTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A version of RecipeTestTrait::applyRecipe() that doesn't time out.
   */
  private function applyRecipe(string $path, array $options = []): void {
    $arguments = [
      (new PhpExecutableFinder())->find(),
      'core/scripts/drupal',
      'recipe',
      // Never apply recipes interactively.
      '--no-interaction',
      ...$options,
      $path,
    ];
    $process = (new Process($arguments))
      ->setWorkingDirectory($this->getDrupalRoot())
      ->setEnv([
        'DRUPAL_DEV_SITE_PATH' => $this->siteDirectory,
        // Ensure that the command boots Drupal into a state where it knows it's
        // a test site.
        // @see drupal_valid_test_ua()
        'HTTP_USER_AGENT' => drupal_generate_test_ua($this->databasePrefix),
      ])
      ->setTimeout(0);

    $process->run();
    $this->assertSame(0, $process->getExitCode(), $process->getErrorOutput());
    // Applying a recipe:
    // - creates new checkpoints, hence the "state" service in the test runner
    //   is outdated
    // - may install modules, which would cause the entire container in the test
    //   runner to be outdated.
    // Hence the entire environment must be rebuilt for assertions to target the
    // actual post-recipe-application result.
    // @see \Drupal\Core\Config\Checkpoint\LinearHistory::__construct()
    $this->rebuildAll();
  }

  public function test(): void {
    // Apply this recipe once. It is a site starter kit and therefore unlikely
    // to be applied again in the real world.
    $dir = InstalledVersions::getInstallPath('drupal/drupal_cms_starter');
    $this->applyRecipe($dir);

    $this->ensureFileExists('05439bd3-1c60-4e1a-8719-e9da071e88e4');

    // The front page should be accessible to everyone.
    $this->drupalGet('<front>');
    $assert_session = $this->assertSession();
    $assert_session->statusCodeEquals(200);
    // Also, the front page should be "/", instead of "/home".
    $assert_session->addressEquals('/');
    $assert_session->pageTextContains('This is the home page of your new site.');
    // The privacy policy page isn't published, so it should respond with a
    // 404, not 403.
    $this->drupalGet('/privacy-policy');
    $assert_session->statusCodeEquals(404);
    // A non-existing page should also respond with a 404.
    $this->drupalGet('/node/999999');
    $assert_session->statusCodeEquals(404);
    // A non-permitted page should respond with a 403.
    $this->drupalGet('/admin');
    $assert_session->statusCodeEquals(403);

    $editor = $this->drupalCreateUser(['administer modules']);
    $editor->addRole('content_editor')->save();
    // Don't use one-time login links, because they will bypass the dashboard
    // upon login.
    $this->useOneTimeLoginLinks = FALSE;
    $this->drupalLogin($editor);

    // Find the top-level items in the navigation.
    $navigation_items = array_map(
      fn (NodeElement $item): string => $item->getText(),
      $this->getSession()->getPage()->findAll('css', '.toolbar-block__list > li > a'),
    );
    // Every item should only appear once.
    $items_count = array_count_values($navigation_items);
    $this->assertSame(count($items_count), array_sum($items_count));
    // The Dashboard link should be the first navigation item.
    $this->assertSame('Dashboard', $navigation_items[0]);

    // The first item in the navigation footer should be the Help link, and it
    // should only appear once.
    $first_footer_item = $assert_session->elementExists('css', '#menu-footer .toolbar-block');
    $assert_session->elementExists('named', ['link', 'Help'], $first_footer_item);
    $assert_session->elementsCount('named', ['link', 'Help'], 1, $first_footer_item->getParent());

    // We should be on the welcome dashboard, and we should see the list of
    // recent content.
    $assert_session->addressEquals('/admin/dashboard');
    $recent_content = $assert_session->elementExists('css', 'h2:contains("Recent content")')
      ->getParent();
    $assert_session->elementExists('named', ['link', 'Privacy policy'], $recent_content);
    $assert_session->elementExists('named', ['link', 'Home'], $recent_content);

    // Ensure that the Project Browser local tasks work as expected.
    $this->drupalGet('/admin/modules');
    // Get the Project Browser local tasks.
    $elements = $assert_session->elementExists('css', 'h2:contains("Primary tabs") + nav')
      ->findAll('css', 'ul li a');
    $local_tasks = [];
    /** @var \Behat\Mink\Element\NodeElement $element */
    foreach ($elements as $element) {
      $link_text = $element->getText();
      $local_tasks[$link_text] = $element->getAttribute('data-drupal-link-system-path');
    }
    // The first task should go to core's regular modules page.
    $this->assertSame('admin/modules', reset($local_tasks));
    // Ensure the Project Browser tasks are in the expected order, have the
    // expected link text, and link to the expected place.
    $project_browser_tasks = preg_grep('|admin/modules/browse/.+|', $local_tasks);
    $this->assertSame(['Recommended add-ons', 'Browse modules'], array_keys($project_browser_tasks));
    $this->assertStringEndsWith('/recommended', $project_browser_tasks['Recommended add-ons']);
    $this->assertStringEndsWith('/drupalorg_jsonapi', $project_browser_tasks['Browse modules']);
    // We should have access to all Project Browser tasks.
    foreach ($project_browser_tasks as $path) {
      $this->drupalGet($path);
      $assert_session->statusCodeEquals(200);
    }
  }

}

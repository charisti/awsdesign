<?php

declare(strict_types=1);

namespace Drupal\Tests\simple_search_form\Functional;

use Drupal\simple_search_form\Plugin\Block\SimpleSearchFormBlock;
use Drupal\Tests\BrowserTestBase;
use Drupal\views\Entity\View;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the simple search form block.
 */
#[Group('simple_search_form')]
#[CoversClass(SimpleSearchFormBlock::class)]
#[IgnoreDeprecations]
#[RunTestsInSeparateProcesses]
final class SimpleSearchFormBlockTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'path',
    'simple_search_form',
  ];

  /**
   * Tests that the block can automatically configure itself.
   */
  public function testPreconfigureBlock(): void {
    $account = $this->drupalCreateUser(['administer blocks']);
    $this->drupalLogin($account);

    // If we try to place the block now, we should get nothing because there is
    // no search page.
    $this->drupalGet('/admin/structure/block/add/simple_search_form_block/stark');
    $assert_session = $this->assertSession();
    $assert_session->fieldValueEquals('settings[action_path]', '');
    $assert_session->fieldValueEquals('settings[get_parameter]', '');

    \Drupal::service('module_installer')->install([
      'simple_search_form_test_index',
    ]);

    // The test view supplies a search page, which should preconfigure the
    // action path and GET parameter.
    $session = $this->getSession();
    $session->reload();
    $this->assertStringEndsWith('/search-with-view', $assert_session->fieldExists('settings[action_path]')->getValue());
    $assert_session->fieldValueEquals('settings[get_parameter]', 'text');

    // Remove the page display from the test view. This should result in the
    // GET parameter being preconfigurable, but not the path.
    $view = View::load('search');
    $displays = $view->get('display');
    unset($displays['page_1']);
    $view->set('display', $displays)->save();

    $session->reload();
    $assert_session->fieldValueEquals('settings[action_path]', '');
    $assert_session->fieldValueEquals('settings[get_parameter]', 'text');

    // Remove the view to test that creating a page with the `/search` alias
    // also preconfigures the block.
    $view->delete();

    // If we create a page with the `/search` alias, the block should
    // preconfigure itself to use that.
    $this->drupalCreateContentType(['type' => 'page']);
    $this->drupalCreateNode([
      'type' => 'page',
      'path' => ['alias' => '/search'],
    ]);
    $session->reload();
    $this->assertStringEndsWith('/search', $assert_session->fieldExists('settings[action_path]')->getValue());
    // There's no view, so the GET parameter should not be preconfigure-able.
    $assert_session->fieldValueEquals('settings[get_parameter]', '');
  }

}

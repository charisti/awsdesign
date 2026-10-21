<?php

declare(strict_types=1);

namespace Drupal\Tests\radix\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Form\FormStateInterface;
use Drupal\radix\Hook\FormHooks;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

require_once dirname(__DIR__, 3) . '/src/Hook/FormHooks.php';
require_once dirname(__DIR__, 3) . '/includes/form.theme';

/**
 * Tests Radix form hook implementations.
 */
#[CoversClass(FormHooks::class)]
#[Group('radix')]
final class FormHooksTest extends UnitTestCase {

  /**
   * Tests the legacy search form alter hook bridge.
   */
  public function testSearchBlockFormAlterLegacyHook(): void {
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    $form = ['keys' => []];
    $form_state = $this->createStub(FormStateInterface::class);

    radix_form_search_block_form_alter($form, $form_state, 'search_block_form');

    self::assertSame('', $form['keys']['#title']);
    self::assertSame(20, $form['keys']['#size']);
    self::assertSame('Search', (string) $form['keys']['#placeholder']);
  }

}

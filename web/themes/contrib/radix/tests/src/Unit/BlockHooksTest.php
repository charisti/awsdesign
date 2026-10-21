<?php

declare(strict_types=1);

namespace Drupal\Tests\radix\Unit;

use Drupal\block_content\BlockContentInterface;
use Drupal\radix\Hook\BlockHooks;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

require_once dirname(__DIR__, 3) . '/src/Hook/BlockHooks.php';

/**
 * Tests Radix block hook implementations.
 */
#[CoversClass(BlockHooks::class)]
#[Group('radix')]
final class BlockHooksTest extends UnitTestCase {

  /**
   * Tests that block suggestions remain a list after deduplication.
   */
  public function testThemeSuggestionsRemainSequential(): void {
    $block_content = $this->createStub(BlockContentInterface::class);
    $block_content->method('bundle')->willReturn('article');

    $suggestions = [
      'block',
      'block__existing',
      'block__existing',
    ];
    $variables = [
      'elements' => [
        '#configuration' => ['view_mode' => 'full.teaser'],
        '#id' => '123',
        'content' => ['#block_content' => $block_content],
      ],
    ];

    BlockHooks::themeSuggestionsBlockAlter($suggestions, $variables);

    self::assertSame([
      'block',
      'block__block_content__view__full_teaser',
      'block__block_content__type__article',
      'block__block_content__view_type__article__full_teaser',
      'block__block_content__id__123',
      'block__block_content__id_view__123__full_teaser',
      'block__existing',
    ], $suggestions);
  }

}

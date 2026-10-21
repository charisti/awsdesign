<?php

declare(strict_types=1);

namespace Drupal\Tests\radix\Unit;

use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

require_once dirname(__DIR__, 3) . '/includes/utility.theme';

/**
 * Tests Radix theme utility functions.
 */
#[CoversFunction('radix_clean_identifier')]
#[Group('radix')]
final class UtilityThemeTest extends UnitTestCase {

  /**
   * Tests cleaning identifiers for theme suggestions.
   */
  #[DataProvider('identifierProvider')]
  public function testCleanIdentifier(mixed $identifier, string $expected): void {
    self::assertSame($expected, radix_clean_identifier($identifier));
  }

  /**
   * Provides identifiers and their expected cleaned values.
   *
   * @return array<string, array{mixed, string}>
   *   The test cases.
   */
  public static function identifierProvider(): array {
    return [
      'string' => ['field_name', 'field_name'],
      'null' => [NULL, ''],
      'non-string' => [123, ''],
      'special characters' => ['field-name.foo/bar', 'field_name_foo_bar'],
    ];
  }

}

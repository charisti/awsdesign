<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate_plus\Unit\process;

use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate_plus\Plugin\migrate\process\SkipOnValue;
use Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase;

/**
 * Tests the skip on value process plugin.
 *
 * @group migrate
 * @coversDefaultClass \Drupal\migrate_plus\Plugin\migrate\process\SkipOnValue
 */
final class SkipOnValueTest extends MigrateProcessTestCase {

  /**
   * @covers ::process
   */
  public function testProcessSkipsOnValue(): void {
    $configuration = [];
    $configuration['method'] = 'process';
    $configuration['value'] = 86;
    $plugin = new SkipOnValue($configuration, 'skip_on_value', []);
    $plugin->transform('86', $this->migrateExecutable, $this->row, 'destinationProperty');
    $this->assertTrue($plugin->isPipelineStopped());
  }

  /**
   * @covers ::process
   */
  public function testProcessSkipsOnMultipleValue(): void {
    $configuration = [];
    $configuration['method'] = 'process';
    $configuration['value'] = [1, 1, 2, 3, 5, 8];
    $plugin = new SkipOnValue($configuration, 'skip_on_value', []);
    $plugin->transform('5', $this->migrateExecutable, $this->row, 'destinationProperty');
    $this->assertTrue($plugin->isPipelineStopped());
  }

  /**
   * @covers ::process
   */
  public function testProcessBypassesOnNonValue(): void {
    $configuration = [];
    $configuration['method'] = 'process';
    $configuration['value'] = 'sourceValue';
    $configuration['not_equals'] = TRUE;
    $plugin = new SkipOnValue($configuration, 'skip_on_value', []);
    $value = $plugin->transform('sourceValue', $this->migrateExecutable, $this->row, 'destinationProperty');
    $this->assertEquals('sourceValue', $value);
    $this->assertFalse($plugin->isPipelineStopped());
    $configuration['value'] = 86;
    $plugin = new SkipOnValue($configuration, 'skip_on_value', []);
    $value = $plugin->transform('86', $this->migrateExecutable, $this->row, 'destinationProperty');
    $this->assertEquals('86', $value);
    $this->assertFalse($plugin->isPipelineStopped());
  }

  /**
   * @covers ::process
   */
  public function testProcessSkipsOnMultipleNonValue(): void {
    $configuration = [];
    $configuration['method'] = 'process';
    $configuration['value'] = [1, 1, 2, 3, 5, 8];
    $plugin = new SkipOnValue($configuration, 'skip_on_value', []);
    $value = $plugin->transform(4, $this->migrateExecutable, $this->row, 'destinationProperty');
    $this->assertEquals('4', $value);
    $this->assertFalse($plugin->isPipelineStopped());
  }

  /**
   * @covers ::process
   */
  public function testProcessBypassesOnMultipleNonValue(): void {
    $configuration = [];
    $configuration['method'] = 'process';
    $configuration['value'] = [1, 1, 2, 3, 5, 8];
    $configuration['not_equals'] = TRUE;
    $plugin = new SkipOnValue($configuration, 'skip_on_value', []);
    $value = $plugin->transform(5, $this->migrateExecutable, $this->row, 'destinationProperty');
    $this->assertEquals('5', $value);
    $this->assertFalse($plugin->isPipelineStopped());
    $plugin = new SkipOnValue($configuration, 'skip_on_value', []);
    $value = $plugin->transform(1, $this->migrateExecutable, $this->row, 'destinationProperty');
    $this->assertEquals('1', $value);
    $this->assertFalse($plugin->isPipelineStopped());
  }

  /**
   * @covers ::row
   */
  public function testRowBypassesOnMultipleNonValue(): void {
    $configuration = [];
    $configuration['method'] = 'row';
    $configuration['value'] = [1, 1, 2, 3, 5, 8];
    $configuration['not_equals'] = TRUE;
    $value = (new SkipOnValue($configuration, 'skip_on_value', []))
      ->transform(5, $this->migrateExecutable, $this->row, 'destinationProperty');
    $this->assertEquals($value, '5');
    $value = (new SkipOnValue($configuration, 'skip_on_value', []))
      ->transform(1, $this->migrateExecutable, $this->row, 'destinationProperty');
    $this->assertEquals($value, '1');
  }

  /**
   * @covers ::row
   */
  public function testRowSkipsOnValue(): void {
    $configuration = [];
    $configuration['method'] = 'row';
    $configuration['value'] = 86;
    $this->expectException(MigrateSkipRowException::class);
    (new SkipOnValue($configuration, 'skip_on_value', []))
      ->transform('86', $this->migrateExecutable, $this->row, 'destinationProperty');
  }

  /**
   * Tests that a skip row exception with a message is raised.
   *
   * @covers ::row
   */
  public function testRowSkipWithMessage(): void {
    $configuration = [
      'method' => 'row',
      'value' => 86,
      'message' => 'The value is 86',
    ];
    $process = new SkipOnValue($configuration, 'skip_on_value', []);
    $this->expectException(MigrateSkipRowException::class);
    $this->expectExceptionMessage('The value is 86');
    $process->transform(86, $this->migrateExecutable, $this->row, 'destination_property');
  }

  /**
   * @covers ::row
   */
  public function testRowBypassesOnNonValue(): void {
    $configuration = [];
    $configuration['method'] = 'row';
    $configuration['value'] = 'sourceValue';
    $configuration['not_equals'] = TRUE;
    $value = (new SkipOnValue($configuration, 'skip_on_value', []))
      ->transform('sourceValue', $this->migrateExecutable, $this->row, 'destinationProperty');
    $this->assertEquals($value, 'sourceValue');
    $configuration['value'] = 86;
    $value = (new SkipOnValue($configuration, 'skip_on_value', []))
      ->transform('86', $this->migrateExecutable, $this->row, 'destinationProperty');
    $this->assertEquals($value, 86);
  }

  /**
   * @covers ::__construct
   */
  public function testRequiredConfiguration(): void {
    $configuration = [];
    // It doesn't meter which method we will put here, because it should throw
    // error on contraction of Plugin.
    $configuration['method'] = 'row';
    $this->expectException(\InvalidArgumentException::class);
    (new SkipOnValue($configuration, 'skip_on_value', []));
  }

}

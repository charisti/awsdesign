<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate_plus\Kernel\Plugin\migrate_plus\data_parser;

/**
 * Test of the data_parser Xml migrate_plus plugin.
 *
 * @group migrate_plus
 */
final class XmlTest extends BaseXml {

  /**
   * {@inheritdoc}
   */
  public function testParentTraversalMatch(): void {
    $this->markTestSkipped('This is currently unsupported.');
  }

  /**
   * {@inheritdoc}
   */
  protected function getDataParserPluginId(): string {
    return 'xml';
  }

}

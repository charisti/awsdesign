<?php

declare(strict_types=1);

namespace Drupal\Tests\layout_builder_restrictions\FunctionalJavascript;

use Drupal\block_content\Entity\BlockContent;
use Drupal\Component\Utility\DeprecationHelper;
use Drupal\filter\FilterFormatRepositoryInterface;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\block_content\Traits\BlockContentCreationTrait;

/**
 * General-purpose methods for testing restrictions.
 */
abstract class LayoutBuilderRestrictionsTestBase extends WebDriverTestBase {

  use BlockContentCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a node bundle.
    $this->createContentType(['type' => 'bundle_with_section_field']);

    $this->drupalLogin($this->drupalCreateUser([
      'access administration pages',
      'administer blocks',
      'administer node display',
      'administer node fields',
      'configure any layout',
      'create and edit custom blocks',
    ]));

    $this->getSession()->resizeWindow(1200, 4000);
    // phpcs:ignore
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // From the manage display page, go to manage the layout.
    $this->navigateToManageDisplay();
    // Checking is_enable will show allow_custom.
    $page->checkField('layout[enabled]');
    $page->checkField('layout[allow_custom]');
    $page->pressButton('Save');

  }

  /**
   * A node type machine name.
   *
   * @var string
   */
  public static $testNodeBundle = 'bundle_with_section_field';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'layout_builder',
    'layout_builder_restrictions',
    'node',
    'field_ui',
    'block_content',
  ];

  /**
   * Specify the theme to be used in testing.
   *
   * @var string
   */
  protected $defaultTheme = 'olivero';

  /**
   * Navigation helper.
   */
  public function navigateToManageDisplay() {
    $this->drupalGet('admin/structure/types/manage/' . self::$testNodeBundle . '/display/default');
  }

  /**
   * Navigation helper.
   */
  public function navigateToNodeLayout($node_id) {
    $this->drupalGet('/node/' . $node_id . '/layout');
  }

  /**
   * Navigation helper.
   */
  public function navigateToNodeSettingsTray($node_id) {
    $this->drupalGet('/node/' . $node_id . '/layout');
    $this->clickLink('Add block');
    $this->assertNotEmpty($this->assertSession()->waitForText('Choose a block'));
  }

  /**
   * Content creation helper.
   */
  public function generateTestNode() {
    $editable_node = $this->createNode([
      'uid' => 0,
      'type' => self::$testNodeBundle,
      'title' => 'The first node title',
      'body' => [
        [
          'value' => 'The first node body',
        ],
      ],
    ]);
    return $editable_node->id();
  }

  /**
   * Generate 3 blocks from 2 different block types.
   *
   * @return array
   *   A keyed array of the generated demo blocks with IDs.
   */
  public function generateTestBlocks() {
    $bundle = $this->createBlockContentType([
      'id' => 'basic',
      'label' => 'Basic',
    ]);
    $bundle = $this->createBlockContentType([
      'id' => 'alternate',
      'label' => 'Alternate',
    ], TRUE);
    $blocks = [
      'Basic Block 1' => 'basic',
      'Basic Block 2' => 'basic',
      'Alternate Block 1' => 'alternate',
    ];
    $default_format = DeprecationHelper::backwardsCompatibleCall(
      \Drupal::VERSION,
      '11.4.0',
      fn() => \Drupal::service(FilterFormatRepositoryInterface::class)->getDefaultFormat()->id(),
      fn() => filter_default_format(),
    );
    foreach ($blocks as $info => $type) {
      $block = BlockContent::create([
        'info' => $info,
        'type' => $type,
        'body' => [
          [
            'value' => 'This is the block content',
            'format' => $default_format,
          ],
        ],
      ]);
      $block->save();
      $blocks[$info] = $block->uuid();
    }
    return $blocks;
  }

}

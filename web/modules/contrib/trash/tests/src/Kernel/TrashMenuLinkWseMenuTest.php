<?php

declare(strict_types=1);

namespace Drupal\Tests\trash\Kernel;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Tests\workspaces\Kernel\WorkspaceTestTrait;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\trash\Menu\TrashMenuLinkManager;
use Drupal\workspaces\Entity\Workspace;
use Drupal\workspaces\WorkspaceInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Runs the menu link trash tests with wse_menu installed.
 */
#[CoversClass(TrashMenuLinkManager::class)]
#[Group('trash')]
#[RunTestsInSeparateProcesses]
class TrashMenuLinkWseMenuTest extends TrashMenuLinkTest {

  use WorkspaceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'menu_ui',
    'options',
    'views',
    'workspaces',
    'workspaces_ui',
    'wse',
    'wse_menu',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->workspaceManager = \Drupal::service('workspaces.manager');

    $this->installConfig(['system', 'views', 'wse']);
    $this->installSchema('workspaces', ['workspace_association']);
    if (isset(workspaces_schema()['workspace_association_revision'])) {
      $this->installSchema('workspaces', ['workspace_association_revision']);
    }
    $this->installSchema('wse', ['workspace_published_revisions']);
    $this->installEntitySchema('workspace');

    $this->setCurrentUser($this->createUser([
      'administer workspaces',
      'administer menu',
      'view any workspace',
    ]));

    // Build the Live tree first, because a workspace tree starts out as a copy
    // of it.
    \Drupal::service('plugin.manager.menu.link')->rebuild();

    $this->workspaces['stage'] = Workspace::create(['label' => 'Stage']);
    $this->workspaces['stage']->save();
  }

  /**
   * Counts the rows in a workspace-specific menu tree table.
   */
  protected function countWorkspaceMenuTreeRows(string $plugin_id, WorkspaceInterface $workspace): int {
    $database = $this->container->get('database');
    $table = 'wse_menu_tree_' . str_replace('-', '_', $workspace->uuid());
    if (!$database->schema()->tableExists($table)) {
      return 0;
    }

    return (int) $database->select($table, 't')
      ->condition('t.id', $plugin_id)
      ->countQuery()
      ->execute()
      ->fetchField();
  }

  /**
   * Tests that trashing a link in a workspace leaves the Live tree alone.
   *
   * Covers the trash itself and a following rebuild.
   * rebuildWorkspaceMenuTree() copies the Live tree into the workspace table
   * and replays the workspace's tracked changes, and a trashed link must not
   * come back through that replay.
   *
   * @see \Drupal\Tests\wse_menu\Kernel\WseMenuTreeRebuildTest::testRebuildFromLiveKeepsLiveRowForTrashedLink()
   */
  public function testTrashingMenuLinkInWorkspace(): void {
    // Trash has to be the outermost decorator, ahead of wse_menu's. Without
    // that, its guard never sees these writes and every assertion below would
    // still pass for the wrong reason.
    $this->assertInstanceOf(TrashMenuLinkManager::class, \Drupal::service('plugin.manager.menu.link'));

    $link = $this->createMenuLink('Workspace Link');
    $plugin_id = $link->getPluginId();
    $this->assertSame(1, $this->countMenuTreeRows($plugin_id));

    // Add the workspace after the link. A workspace tree is built as a copy of
    // Live when the workspace is created, so a workspace that predates the link
    // never holds its row, and the delete below would have nothing to remove.
    $workspace = Workspace::create(['label' => 'Trashing']);
    $workspace->save();
    $this->assertSame(1, $this->countWorkspaceMenuTreeRows($plugin_id, $workspace), 'The workspace tree starts out with the link.');

    $this->workspaceManager->executeInWorkspace($workspace->id(), function () use ($link): void {
      $this->getEntityTypeManager()->getStorage('menu_link_content')->delete([$link]);
    });

    // Live keeps the link. The trash flag is only on the workspace revision, so
    // the published menu must be unaffected.
    $this->assertNotEmpty(MenuLinkContent::load($link->id()));
    $this->assertSame(1, $this->countMenuTreeRows($plugin_id), 'Trashing in a workspace must not touch the Live menu tree.');

    // The workspace tree drops it, and rendering there must not throw.
    $this->assertSame(0, $this->countWorkspaceMenuTreeRows($plugin_id, $workspace), 'Trashing in a workspace removes the link from that workspace tree.');
    $this->assertMenuRendersInWorkspace($workspace);

    // Rebuilding from Live must not disturb either tree.
    \Drupal::service('wse_menu.tree_storage')->rebuildWorkspaceMenuTree($workspace);

    $this->assertSame(1, $this->countMenuTreeRows($plugin_id), 'A workspace tree rebuild must not delete the Live row.');
    $this->assertSame(0, $this->countWorkspaceMenuTreeRows($plugin_id, $workspace), 'A rebuild must not replay a trashed link back into the workspace tree.');
    $this->assertMenuRendersInWorkspace($workspace);
  }

  /**
   * Tests that publishing a workspace does not leave a trashed link in Live.
   *
   * Publishing makes the workspace revisions default, so the trash applies to
   * Live and the published menu tree has to lose the row. Publishing runs in
   * the 'ignore' trash context, so its writes reach the guard on the menu link
   * manager, and that is what keeps the row out.
   */
  public function testPublishingWorkspaceWithTrashedMenuLink(): void {
    $link = $this->createMenuLink('Published Link');
    $plugin_id = $link->getPluginId();
    $this->assertSame(1, $this->countMenuTreeRows($plugin_id));

    $workspace = Workspace::create(['label' => 'Publishing']);
    $workspace->save();

    $this->workspaceManager->executeInWorkspace($workspace->id(), function () use ($link): void {
      $this->getEntityTypeManager()->getStorage('menu_link_content')->delete([$link]);
    });
    $this->assertSame(1, $this->countMenuTreeRows($plugin_id), 'Live keeps the link while the workspace is unpublished.');

    \Drupal::service('workspaces.operation_factory')->getPublisher($workspace)->publish();

    $this->assertEmpty(MenuLinkContent::load($link->id()), 'The link is trashed in Live after publishing.');
    $this->assertSame(0, $this->countMenuTreeRows($plugin_id), 'Publishing a trashed link must remove its Live menu tree row.');
    $this->assertArrayNotHasKey($plugin_id, $this->loadMenuTree('test-menu'));
  }

  /**
   * Asserts that the test menu renders inside a workspace without throwing.
   */
  protected function assertMenuRendersInWorkspace(WorkspaceInterface $workspace): void {
    $this->workspaceManager->executeInWorkspace($workspace->id(), function (): void {
      try {
        $build = $this->buildMenu('test-menu');
        $this->assertNotEquals(0, $build['#cache']['max-age'], 'The menu render cache should not be poisoned.');
      }
      catch (PluginException $e) {
        $this->fail('Rendering the menu in a workspace threw a PluginException: ' . $e->getMessage());
      }
    });
  }

}

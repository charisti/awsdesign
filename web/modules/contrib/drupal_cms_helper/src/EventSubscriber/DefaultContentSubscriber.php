<?php

declare(strict_types=1);

namespace Drupal\drupal_cms_helper\EventSubscriber;

use Drupal\canvas\Plugin\Field\FieldType\ComponentTreeItem;
use Drupal\Core\DefaultContent\ExportMetadata;
use Drupal\Core\DefaultContent\PreExportEvent;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Adjusts exported default content for Drupal CMS.
 *
 * @internal
 *   This is an internal part of Drupal CMS and may be changed or removed at any
 *   time without warning. External code should not interact with this class.
 */
final class DefaultContentSubscriber implements EventSubscriberInterface {

  public function __construct(
    private readonly EntityRepositoryInterface $entityRepository,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      PreExportEvent::class => 'preExport',
    ];
  }

  /**
   * Prepares to export a content entity.
   */
  public function preExport(PreExportEvent $event): void {
    // @todo Remove when https://www.drupal.org/i/3557465 is released in core.
    foreach ($event->entity->getFieldDefinitions() as $field_name => $definition) {
      if ($definition->getType() === 'created') {
        // Drop created timestamps so imports use the current time.
        $event->setExportable($field_name, FALSE);
      }
    }
    // Special handling for Canvas component trees.
    // @todo Remove after https://www.drupal.org/i/3534561 is released.
    if (class_exists(ComponentTreeItem::class)) {
      $event->setCallback('field_item:' . ComponentTreeItem::PLUGIN_ID, $this->exportComponentTreeItem(...));
    }

    // Canvas is installed, it adds a `target_uuid` property to entity reference
    // fields, which is useless in exported content.
    $callbacks = $event->getCallbacks();
    $decorated = $callbacks['field_item:entity_reference'] ?? NULL;
    if ($decorated) {
      $event->setCallback('field_item:entity_reference', function () use ($decorated): ?array {
        $values = $decorated(...func_get_args());
        if (is_array($values)) {
          unset($values['target_uuid']);
        }
        return $values;
      });
    }
  }

  /**
   * Exports a Canvas component tree item.
   *
   * @param \Drupal\canvas\Plugin\Field\FieldType\ComponentTreeItem $item
   *   The field item to export.
   * @param \Drupal\Core\DefaultContent\ExportMetadata $metadata
   *   Metadata about the entity being exported.
   *
   * @return array
   *   The exported field item values.
   */
  private function exportComponentTreeItem(ComponentTreeItem $item, ExportMetadata $metadata): array {
    // Find all entities referenced in the component tree and add them as
    // dependencies of the entity being exported.
    $dependencies = $item->calculateFieldItemValueDependencies($item->getEntity());
    foreach ($dependencies['content'] ?? [] as $dependency) {
      // @see \Drupal\Core\Entity\EntityBase::getConfigDependencyName()
      [$entity_type_id,, $uuid] = explode(':', $dependency);
      $dependency = $this->entityRepository->loadEntityByUuid($entity_type_id, $uuid);
      if ($dependency instanceof ContentEntityInterface) {
        $metadata->addDependency($dependency);
      }
    }
    // Don't export any empty properties; they're not valid for import.
    $values = array_filter($item->getValue());
    // The component version is not necessary in exported content.
    unset($values['component_version']);
    return $values;
  }

}

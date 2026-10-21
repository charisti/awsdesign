<?php

declare(strict_types=1);

namespace Drupal\drupal_cms_helper;

use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * @internal
 *   This is an internal part of Drupal CMS and may be changed or removed at any
 *   time without warning. External code should not interact with this class.
 */
final class SiteExportFileStorage extends FileStorage {

  public function __construct(
    private readonly ConfigManagerInterface $configManager,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    string $directory,
    string $collection = StorageInterface::DEFAULT_COLLECTION,
  ) {
    parent::__construct($directory, $collection);
  }

  /**
   * {@inheritdoc}
   */
  public function write($name, array $data): bool {
    // Work around https://www.drupal.org/i/3002532.
    if (preg_match('/^language\.entity\.(?!und|zxx)/', $name)) {
      $data['dependencies']['config'][] = 'language.entity.und';
      $data['dependencies']['config'][] = 'language.entity.zxx';
    }
    if ($this->stripUuid($name)) {
      unset($data['uuid']);
    }
    unset($data['_core']);
    return parent::write($name, $data);
  }

  /**
   * {@inheritdoc}
   */
  public function createCollection($collection): self {
    return new self(
      $this->configManager,
      $this->entityTypeManager,
      $this->directory,
      $collection,
    );
  }

  /**
   * Determines if the UUID should be stripped out of a config object.
   *
   * @param string $name
   *   The name of the config being exported.
   *
   * @return bool
   *   TRUE if the UUID should be removed, FALSE otherwise.
   */
  private function stripUuid(string $name): bool {
    $entity_type_id = $this->configManager->getEntityTypeIdByName($name);
    // If there's no entity type ID, this is simple config and the UUID should
    // be removed.
    if (empty($entity_type_id)) {
      return TRUE;
    }
    $id_key = $this->entityTypeManager->getDefinition($entity_type_id)
      ->getKey('id');
    // All config entities have a `uuid` key. We don't want to strip it out if
    // it also serves as the entity ID; for example, Canvas folders do that.
    // @see core.data_types.schema.yml
    return $id_key !== 'uuid';
  }

}

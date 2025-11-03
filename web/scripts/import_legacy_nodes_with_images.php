<?php

use Drupal\Core\Database\Database;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\file\Entity\File;

/**
 * Usage:
 *   ddev drush php:script scripts/import_legacy_nodes_with_images.php 10
 * Last arg = limit; default 10.
 *
 * Prereqs:
 * - legacy DB connection in settings.local.php (host.docker.internal:32790)
 * - rsync physical files after import:
 *   rsync -av ~/Sites/drupal-ddev/web/sites/default/files/ \
 *            ~/Sites/drupal-cms/web/sites/default/files/
 */

$limit = (int) ($argv[1] ?? 10);
if ($limit <= 0) { $limit = 10; }

// Which image field(s) to try (by machine name). Add more if needed.
$candidateImageFields = ['field_image', 'field_images']; // single + multi

echo "Importing up to $limit nodes from legacy DB (with images if present)...\n";

// Connect legacy DB
try {
  $legacy = Database::getConnection('default', 'legacy');
} catch (\Throwable $e) {
  fwrite(STDERR, "Failed to connect to legacy DB: " . $e->getMessage() . "\n");
  exit(1);
}

// Destination existing bundles
$existingTypes = array_keys(NodeType::loadMultiple());
$existingTypes = array_combine($existingTypes, $existingTypes);

// Pull latest published nodes
$rows = $legacy->select('node_field_data', 'n')
  ->fields('n', ['nid', 'type', 'title', 'langcode', 'status', 'created', 'changed', 'uid'])
  ->condition('status', 1)
  ->orderBy('created', 'DESC')
  ->range(0, $limit)
  ->execute()
  ->fetchAllAssoc('nid');

if (!$rows) {
  echo "No rows found in legacy node_field_data.\n";
  exit(0);
}

// Helper: load body
$getBody = function($nid) use ($legacy) {
  return (string) $legacy->select('node__body', 'b')
    ->fields('b', ['body_value'])
    ->condition('entity_id', $nid)
    ->range(0, 1)
    ->execute()
    ->fetchField();
};

// Helper: find image files for a given node across known image fields
$getImageUris = function($nid) use ($legacy, $candidateImageFields) {
  $uris = [];

  foreach ($candidateImageFields as $field) {
    // Example: node__field_image, column "{field}_target_id" stores FID
    $table = 'node__' . $field;
    $col = $field . '_target_id';

    // Check table exists in legacy
    try {
      $exists = $legacy->schema()->tableExists($table);
    } catch (\Throwable $t) {
      $exists = false;
    }
    if (!$exists) {
      continue;
    }

    // Fetch all file IDs attached to this node for that field
    $fids = $legacy->select($table, 't')
      ->fields('t', [$col])
      ->condition('entity_id', $nid)
      ->execute()
      ->fetchCol();

    if (!$fids) { continue; }

    // Resolve each fid to a URI from file_managed
    $query = $legacy->select('file_managed', 'fm')
      ->fields('fm', ['uri'])
      ->condition('fid', $fids, 'IN');
    $theseUris = $query->execute()->fetchCol();

    foreach ($theseUris as $u) {
      if ($u && !in_array($u, $uris, true)) {
        $uris[] = $u;
      }
    }
  }

  return $uris;
};

// Ensure File entity exists in destination for a given URI.
// Assumes files are/will be present on disk under web/sites/default/files/ (public://...).
$ensureFileEntity = function(string $uri): ?File {
  // Normalize e.g. "public://path/to.jpg"
  // If legacy URIs are "public://..." this just works after rsync.
  $storage = \Drupal::entityTypeManager()->getStorage('file');
  $existing = $storage->loadByProperties(['uri' => $uri]);
  /** @var \Drupal\file\Entity\File $file */
  $file = $existing ? reset($existing) : NULL;

  if (!$file) {
    try {
      $file = File::create(['uri' => $uri]);
      $file->setPermanent();
      $file->save();
    } catch (\Throwable $t) {
      \Drupal::logger('import_legacy')->warning('Failed to create file entity for @uri: @msg', [
        '@uri' => $uri,
        '@msg' => $t->getMessage(),
      ]);
      return NULL;
    }
  }
  return $file;
};

$created = 0;
foreach ($rows as $nid => $row) {
  $bundle = $existingTypes[$row->type] ?? 'page';
  if (!isset($existingTypes[$bundle])) {
    echo "Bundle '$bundle' not found in destination, skipping nid $nid\n";
    continue;
  }

  $bodyValue = $getBody($nid);

  $node = Node::create([
    'type' => $bundle,
    'title' => $row->title ?: ('Imported node ' . $nid),
    'langcode' => $row->langcode ?: 'en',
    'uid' => 1, // set to 1 (admin); change if you want to map users
    'status' => 1,
    'body' => [
      'value' => $bodyValue,
      'format' => 'basic_html',
    ],
    'created' => (int) $row->created,
    'changed' => (int) $row->changed,
  ]);

  // Attach images if field(s) exist on this bundle in the destination.
  $fieldManager = \Drupal::service('entity_field.manager');
  $defs = $fieldManager->getFieldDefinitions('node', $bundle);

  $imageUris = $getImageUris($nid);
  if (!empty($imageUris)) {
    foreach ($candidateImageFields as $field) {
      if (!empty($imageUris) && isset($defs[$field]) && $defs[$field]->getType() === 'image') {
        // Prepare File entities for the URIs (public://...)
        $items = [];
        foreach ($imageUris as $uri) {
          // Ensure file entity exists
          $file = $ensureFileEntity($uri);
          if ($file) {
            $items[] = ['target_id' => $file->id()];
          }
        }
        if (!empty($items)) {
          // If field is single-value, keep just the first file
          $cardinality = $defs[$field]->getFieldStorageDefinition()->getCardinality();
          if ($cardinality === 1 && count($items) > 1) {
            $items = [reset($items)];
          }
          $node->set($field, $items);
          // Only populate the first matching image field we find
          break;
        }
      }
    }
  }

  try {
    $node->save();
    $created++;
    echo "✔ Imported legacy nid $nid → new nid " . $node->id() . " (type: $bundle, images: " . count($imageUris) . ")\n";
  } catch (\Throwable $t) {
    echo "✖ Failed to save nid $nid: " . $t->getMessage() . "\n";
  }
}

echo "Done. Created $created node(s).\n";

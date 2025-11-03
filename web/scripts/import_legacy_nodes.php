<?php

use Drupal\Core\Database\Database;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Usage:
 *   drush php:script scripts/import_legacy_nodes.php 20
 * (Last arg = limit; default 10)
 */

$limit = (int) ($argv[1] ?? 10);
if ($limit <= 0) { $limit = 10; }

echo "Importing up to $limit nodes from legacy DB...\n";

try {
  // Connect to the secondary (legacy) DB as configured in settings.local.php
  $conn = Database::getConnection('default', 'legacy');
} catch (\Exception $e) {
  fwrite(STDERR, "Failed to connect to legacy DB: " . $e->getMessage() . "\n");
  exit(1);
}

// Get available content types in destination to avoid invalid bundles.
$existing_types = array_keys(NodeType::loadMultiple());
$existing_types = array_combine($existing_types, $existing_types);

// Pull latest published nodes from the legacy site.
$query = $conn->select('node_field_data', 'n')
  ->fields('n', ['nid', 'type', 'title', 'langcode', 'status', 'created', 'changed', 'uid'])
  ->condition('status', 1)
  ->orderBy('created', 'DESC')
  ->range(0, $limit);

$rows = $query->execute()->fetchAllAssoc('nid');

if (!$rows) {
  echo "No rows found in legacy node_field_data.\n";
  exit(0);
}

echo "Found " . count($rows) . " rows. Creating nodes...\n";

$created = 0;
foreach ($rows as $nid => $row) {
  $bundle = $existing_types[$row->type] ?? 'page'; // fallback if bundle doesn't exist
  if (!isset($existing_types[$bundle])) {
    // If even 'page' doesn't exist, create a basic page in Olivero installs.
    echo "Bundle '$bundle' not found, skipping nid $nid\n";
    continue;
  }

  // Fetch body (if present)
  $body_value = '';
  $body = $conn->select('node__body', 'b')
    ->fields('b', ['body_value'])
    ->condition('entity_id', $nid)
    ->range(0, 1)
    ->execute()
    ->fetchField();
  if ($body) {
    $body_value = (string) $body;
  }

  // Create node in destination.
  $node = Node::create([
    'type' => $bundle,
    'title' => $row->title ?: ('Imported node ' . $nid),
    'langcode' => $row->langcode ?: 'en',
    'uid' => 1, // author admin; change to $row->uid if you mapped users earlier
    'status' => 1,
    'body' => [
      'value' => $body_value,
      'format' => 'basic_html',
    ],
    'created' => (int) $row->created,
    'changed' => (int) $row->changed,
  ]);

  try {
    $node->save();
    $created++;
    echo "✔ Imported legacy nid $nid → new nid " . $node->id() . " (type: $bundle)\n";
  } catch (\Throwable $t) {
    echo "✖ Failed to import nid $nid: " . $t->getMessage() . "\n";
  }
}

echo "Done. Created $created node(s).\n";

<?php

use Drupal\Core\Database\Database;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\file\Entity\File;

/**
 * Usage:
 *   ddev drush php:script scripts/import_legacy_nodes_smart.php 10
 * (last arg = limit; default 10)
 *
 * Prereqs:
 * - Legacy DB connection in settings.local.php (host.docker.internal + correct port)
 * - After import, rsync physical files:
 *   rsync -av ~/Sites/drupal-ddev/web/sites/default/files/ \
 *            ~/Sites/drupal-cms/web/sites/default/files/
 */

$limit = (int) ($argv[1] ?? 10);
if ($limit <= 0) { $limit = 10; }

echo "Smart-import: up to $limit nodes with body + images (auto-detect fields)\n";

try { $legacy = Database::getConnection('default', 'legacy'); }
catch (\Throwable $e) { fwrite(STDERR, "Legacy DB connect failed: ".$e->getMessage()."\n"); exit(1); }

$existingTypes = array_keys(NodeType::loadMultiple());
$existingTypes = array_combine($existingTypes, $existingTypes);

// ---- helpers ---------------------------------------------------------------

/** Return legacy text fields (machine names) for a bundle (text_long/text_with_summary). */
$getLegacyTextFields = function(string $bundle) use ($legacy): array {
  $fields = [];
  // field_config stores field_name/type, field_config_instance in D8+ became field_config (bundle info is in field_config + field_config_storage)
  // Simpler: check field_config where entity_type='node', bundle=?, type in (...)
  try {
    $fields = $legacy->select('field_config', 'fc')
      ->fields('fc', ['field_name'])
      ->condition('entity_type', 'node')
      ->condition('bundle', $bundle)
      ->condition('type', ['text_long','text_with_summary'], 'IN')
      ->execute()->fetchCol();
  } catch (\Throwable $t) {}
  // Fallback: try standard "body"
  if (!in_array('body', $fields, true)) { array_unshift($fields, 'body'); }
  // unique
  return array_values(array_unique($fields));
};

/** Return legacy image field names (type=image) for a bundle. */
$getLegacyImageFields = function(string $bundle) use ($legacy): array {
  $fields = [];
  try {
    $fields = $legacy->select('field_config', 'fc')
      ->fields('fc', ['field_name'])
      ->condition('entity_type', 'node')
      ->condition('bundle', $bundle)
      ->condition('type', 'image')
      ->execute()->fetchCol();
  } catch (\Throwable $t) {}
  // Common fallbacks
  foreach (['field_image','field_images','field_hero_image'] as $f) {
    if (!in_array($f, $fields, true)) { $fields[] = $f; }
  }
  return array_values(array_unique($fields));
};

/** Get first non-empty body text from legacy among candidate fields. */
$getLegacyBodyValue = function(int $nid, array $candidates) use ($legacy): string {
  foreach ($candidates as $field) {
    $table = 'node__' . $field;
    $valueCol = $field . '_value';
    // Prefer *_value, but if the table is body, legacy may be node__body.body_value
    if ($field === 'body') { $valueCol = 'body_value'; }
    try {
      if (!$legacy->schema()->tableExists($table)) { continue; }
      $value = $legacy->select($table, 't')
        ->fields('t', [$valueCol])
        ->condition('entity_id', $nid)
        ->orderBy('delta', 'ASC')
        ->range(0, 1)
        ->execute()
        ->fetchField();
      if ($value && trim((string)$value) !== '') {
        return (string) $value;
      }
    } catch (\Throwable $t) { /* ignore and try next */ }
  }
  return '';
};

/** Get URIs of images from legacy among image fields for nid. */
$getLegacyImageUris = function(int $nid, array $imageFields) use ($legacy): array {
  $uris = [];
  foreach ($imageFields as $field) {
    $table = 'node__' . $field;
    $col   = $field . '_target_id';
    try {
      if (!$legacy->schema()->tableExists($table)) { continue; }
      $fids = $legacy->select($table, 't')->fields('t', [$col])->condition('entity_id', $nid)->execute()->fetchCol();
      if (!$fids) { continue; }
      $these = $legacy->select('file_managed', 'fm')->fields('fm', ['uri'])->condition('fid', $fids, 'IN')->execute()->fetchCol();
      foreach ($these as $u) {
        if ($u && !in_array($u, $uris, true)) { $uris[] = $u; }
      }
    } catch (\Throwable $t) {}
  }
  return $uris;
};

/** Ensure a File entity exists in destination for a given public:// URI. */
$ensureFileEntity = function(string $uri): ?File {
  try {
    $existing = \Drupal::entityTypeManager()->getStorage('file')->loadByProperties(['uri' => $uri]);
    /** @var \Drupal\file\Entity\File|null $file */
    $file = $existing ? reset($existing) : NULL;
    if (!$file) {
      $file = File::create(['uri' => $uri]);
      $file->setPermanent();
      $file->save();
    }
    return $file;
  } catch (\Throwable $t) {
    \Drupal::logger('import_legacy')->warning('File entity failed for @uri: @msg', ['@uri'=>$uri,'@msg'=>$t->getMessage()]);
    return NULL;
  }
};

/** Choose a destination field name of given type on a bundle, preferring same name if available. */
$pickDestinationField = function(string $bundle, array $preferredNames, string $type) {
  $defs = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', $bundle);
  // prefer same names
  foreach ($preferredNames as $name) {
    if (isset($defs[$name]) && $defs[$name]->getType() === $type) {
      return $name;
    }
  }
  // else first field of that type
  foreach ($defs as $name => $def) {
    if ($def->getType() === $type) {
      return $name;
    }
  }
  return NULL;
};

// ---- fetch candidate nodes -------------------------------------------------

$rows = $legacy->select('node_field_data', 'n')
  ->fields('n', ['nid','type','title','langcode','status','created','changed'])
  ->condition('status', 1)
  ->orderBy('created','DESC')
  ->range(0, $limit)
  ->execute()
  ->fetchAllAssoc('nid');

if (!$rows) { echo "No published rows in legacy node_field_data.\n"; exit(0); }

$created = 0;
foreach ($rows as $nid => $row) {
  $bundle = $existingTypes[$row->type] ?? 'page';
  if (!isset($existingTypes[$bundle])) {
    echo "Skip nid $nid: bundle '$bundle' not found in destination.\n";
    continue;
  }

  // BODY
  $legacyTextFields = $getLegacyTextFields($row->type);
  $bodyValue = $getLegacyBodyValue((int)$nid, $legacyTextFields);

  // IMAGES
  $legacyImgFields = $getLegacyImageFields($row->type);
  $imageUris = $getLegacyImageUris((int)$nid, $legacyImgFields);

  // Build node
  $node = Node::create([
    'type'    => $bundle,
    'title'   => $row->title ?: ('Imported ' . $nid),
    'langcode'=> $row->langcode ?: 'en',
    'status'  => 1,
    'uid'     => 1,
    'created' => (int) $row->created,
    'changed' => (int) $row->changed,
  ]);

  // Map body to a destination long text field
  if ($bodyValue !== '') {
    $destBodyField = $pickDestinationField($bundle, $legacyTextFields, 'text_long');
    if (!$destBodyField) {
      // Many body fields are text_with_summary; accept that too
      $destBodyField = $pickDestinationField($bundle, $legacyTextFields, 'text_with_summary') ?? 'body';
    }
    if ($destBodyField) {
      $node->set($destBodyField, ['value' => $bodyValue, 'format' => 'basic_html']);
    }
  }

  // Map images to a destination image field
  $attachedTo = NULL;
  if (!empty($imageUris)) {
    $destImageField = $pickDestinationField($bundle, $legacyImgFields, 'image');
    if ($destImageField) {
      $items = [];
      foreach ($imageUris as $uri) {
        $file = $ensureFileEntity($uri);
        if ($file) { $items[] = ['target_id' => $file->id()]; }
      }
      if (!empty($items)) {
        // respect cardinality 1
        $defs = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', $bundle);
        $cardinality = $defs[$destImageField]->getFieldStorageDefinition()->getCardinality();
        if ($cardinality === 1 && count($items) > 1) { $items = [reset($items)]; }
        $node->set($destImageField, $items);
        $attachedTo = $destImageField;
      }
    }
  }

  try {
    $node->save();
    $created++;
    printf("✔ nid %d → new nid %d  bundle=%s  body=%s  images=%d%s\n",
      $nid, $node->id(), $bundle,
      ($bodyValue !== '' ? 'yes' : 'no'),
      count($imageUris),
      ($attachedTo ? " (dest: $attachedTo)" : '')
    );
  } catch (\Throwable $t) {
    echo "✖ Failed nid $nid: " . $t->getMessage() . "\n";
  }
}

echo "Done. Created $created node(s).\n";

<?php
/** Run pending entity definition updates (creates missing tables/cols). */
$m = \Drupal::entityDefinitionUpdateManager();
if ($m->needsUpdates()) {
  foreach ($m->getChangeList() as $entity_type_id => $changes) {
    echo "Updating entity: $entity_type_id\n";
  }
  try { $m->applyEntityTypeUpdates(); }
  catch (\Throwable $e) { echo "applyEntityTypeUpdates: ".$e->getMessage()."\n"; }
  try { $m->applyFieldStorageDefinitionUpdates(); }
  catch (\Throwable $e) { echo "applyFieldStorageDefinitionUpdates: ".$e->getMessage()."\n"; }
  echo "✅ Entity definitions updated.\n";
} else {
  echo "✅ No entity definition updates needed.\n";
}

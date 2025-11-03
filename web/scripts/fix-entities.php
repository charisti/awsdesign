<?php
use Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface;

$manager = \Drupal::entityDefinitionUpdateManager();

if ($manager->needsUpdates()) {
  echo "---- Entity definition updates detected ----\n";
  foreach ($manager->getChangeList() as $entity_type_id => $changes) {
    echo " - $entity_type_id\n";
  }
  try {
    $manager->updateDefinitions();
    echo "✅ Entity definitions updated successfully.\n";
  }
  catch (\Throwable $e) {
    echo "✗ Error during updateDefinitions(): " . $e->getMessage() . "\n";
  }
} else {
  echo "✅ No entity definition updates needed.\n";
}

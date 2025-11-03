#!/bin/bash
echo "🚧  Drupal Repair Script for Drupal 11 (Media, Redirect, Schema fixes)"
set -e

# ---------------------------------------------------------------------
# 0.  Sanity check
# ---------------------------------------------------------------------
if ! ddev status >/dev/null 2>&1; then
  echo "❌  DDEV not running. Start your project first:  ddev start"
  exit 1
fi

# ---------------------------------------------------------------------
# 1.  Deinstall problem modules (ignore errors)
# ---------------------------------------------------------------------
echo "🧹  Removing broken or missing modules and themes..."
ddev drush pmu media_library media redirect bootstrap5 bootstrap_barrio migrate_upgrade -y || true
ddev drush cr || true

# ---------------------------------------------------------------------
# 2.  Drop leftover tables
# ---------------------------------------------------------------------
echo "🗑️  Dropping leftover tables..."
ddev mysql -e "DROP TABLE IF EXISTS redirect, redirect_404, media_revision_field_data, media_revision, media_field_data, media, media_type;" db

# ---------------------------------------------------------------------
# 3.  Remove stale config and schema flags
# ---------------------------------------------------------------------
echo "🧼  Cleaning schema and config entries..."
for item in system.schema.media system.schema.media_library system.schema.redirect \
             core.extension:module.bootstrap5 core.extension:module.bootstrap_barrio \
             core.extension:theme.bootstrap5 core.extension:theme.bootstrap_barrio \
             core.extension:module.migrate_upgrade ; do
  ddev drush cdel "$item" -y 2>/dev/null || true
done
ddev drush cr

# ---------------------------------------------------------------------
# 4.  Reinstall clean Core modules
# ---------------------------------------------------------------------
echo "📦  Reinstalling clean Core modules..."
ddev drush en media media_library -y
ddev drush en pathauto metatag redirect -y
ddev drush updb -y
ddev drush cr

# ---------------------------------------------------------------------
# 5.  Verify DB tables
# ---------------------------------------------------------------------
echo "🔍  Verifying media/redirect tables..."
ddev exec mysql -e "SHOW TABLES LIKE 'media%';" db
ddev exec mysql -e "SHOW TABLES LIKE 'redirect%';" db

# ---------------------------------------------------------------------
# 6.  Verify entity schema
# ---------------------------------------------------------------------
echo "🧩  Checking entity definitions..."
ddev drush php:eval '\Drupal::entityDefinitionUpdateManager()->needsUpdates() ? print("❌  still pending\n") : print("✅  OK\n");'

echo "✅  Repair complete. Check /admin/reports/status in your browser."

#!/usr/bin/env bash
# Run after each deploy (Plesk Git → "Additional deployment actions").
# Installs core, contrib modules and vendor/ exactly as pinned in composer.lock.
#
# Override the binaries if they are not on PATH, e.g.:
#   PHP_BIN=/opt/plesk/php/8.3/bin/php COMPOSER_BIN=/usr/local/bin/composer bash scripts/deploy.sh
set -euo pipefail

cd "$(dirname "$0")/.."

PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-$(command -v composer)}"

"$PHP_BIN" "$COMPOSER_BIN" install --no-dev --no-interaction --optimize-autoloader

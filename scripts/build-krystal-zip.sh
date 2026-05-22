#!/usr/bin/env bash
# Build a self-contained zip for Krystal shared hosting (vendor + Vite build, no Node on server).
# Output: build/looptrends-krystal.zip — upload, set document root to /public, edit .env, run migrate once.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
STAGE="$ROOT/build/krystal-bundle"
OUT_ZIP="$ROOT/build/looptrends-krystal.zip"

for cmd in rsync zip openssl perl composer npm; do
  command -v "$cmd" >/dev/null || {
    echo "Missing required command: $cmd" >&2
    exit 1
  }
done

echo "==> Cleaning build/"
rm -rf "$ROOT/build"
mkdir -p "$ROOT/build"

echo "==> Staging files (rsync)…"
mkdir -p "$STAGE"
rsync -a \
  --exclude='.git/' \
  --exclude='node_modules/' \
  --exclude='/vendor/' \
  --exclude='build/' \
  --exclude='.env' \
  --exclude='.env.*' \
  --exclude='tests/' \
  --exclude='phpunit.xml' \
  --exclude='.phpunit.result.cache' \
  --exclude='.phpunit.cache/' \
  --exclude='storage/logs/*.log' \
  --exclude='database/*.sqlite' \
  --exclude='public/hot' \
  --exclude='heyzine-flipbook.zip' \
  --exclude='Artboard*.png' \
  --exclude='*.zip' \
  "$ROOT/" "$STAGE/"

mkdir -p "$STAGE/storage/logs" "$STAGE/storage/framework/sessions" \
  "$STAGE/storage/framework/views" "$STAGE/storage/framework/cache/data" \
  "$STAGE/bootstrap/cache"

# Never ship dev route/config cache (paths and env differ on Krystal).
find "$STAGE/bootstrap/cache" -maxdepth 1 -name '*.php' -delete 2>/dev/null || true

APP_KEY="base64:$(openssl rand -base64 32)"
export APP_KEY

echo "==> Temporary .env (SQLite) for Composer / Artisan during build…"
SQLITE_PATH="$STAGE/database/.build.sqlite"
rm -f "$SQLITE_PATH"
touch "$SQLITE_PATH"
cat >"$STAGE/.env" <<EOF
APP_NAME="Robert Todd Trends"
APP_ENV=local
APP_KEY=${APP_KEY}
APP_DEBUG=true
APP_URL=http://127.0.0.1
DB_CONNECTION=sqlite
DB_DATABASE=${SQLITE_PATH}
BCRYPT_ROUNDS=12
CACHE_STORE=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
LOG_CHANNEL=stack
LOG_STACK=single
EOF

echo "==> composer install (production)…"
(
  cd "$STAGE"
  composer install --no-dev --optimize-autoloader --no-interaction
)

echo "==> npm ci && npm run build…"
(
  cd "$STAGE"
  npm ci
  npm run build
  rm -rf node_modules
)

echo "==> Final .env (Krystal / MySQL placeholders + STORAGE_USE_PUBLIC_PATH)…"
perl -pe 's/__APP_KEY__/$ENV{APP_KEY}/g' "$ROOT/scripts/krystal.env.template" >"$STAGE/.env"
rm -f "$SQLITE_PATH"

cp "$ROOT/scripts/krystal-INSTALL.txt" "$STAGE/INSTALL.txt"

echo "==> One-time migration URL secret (injected into public/run-migrations-once.php)…"
MIG_KEY="$(openssl rand -hex 32)"
export MIG_KEY
if grep -q '__MIGRATION_RUNNER_SECRET__' "$STAGE/public/run-migrations-once.php" 2>/dev/null; then
  perl -pe 's/__MIGRATION_RUNNER_SECRET__/$ENV{MIG_KEY}/g' "$STAGE/public/run-migrations-once.php" >"$STAGE/public/run-migrations-once.php.tmp"
  mv "$STAGE/public/run-migrations-once.php.tmp" "$STAGE/public/run-migrations-once.php"
else
  echo "Warning: expected __MIGRATION_RUNNER_SECRET__ placeholder in public/run-migrations-once.php" >&2
fi

cat >"$STAGE/MIGRATE_ONCE.txt" <<EOF
=== One-time database setup (no SSH) ===

1. Finish editing .env (MySQL + APP_URL) and save.

2. Open this URL once in your browser (same scheme + host as APP_URL, no trailing slash before /run):

   YOUR_APP_URL_HERE/run-migrations-once.php?key=${MIG_KEY}

   Example: if APP_URL is https://trends.example.com then open:
   https://trends.example.com/run-migrations-once.php?key=${MIG_KEY}

3. When the page says OK, delete: public/run-migrations-once.php

--- copy key only (if needed) ---
${MIG_KEY}
EOF

echo "==> Zip archive…"
rm -f "$OUT_ZIP"
(
  cd "$STAGE"
  zip -r -q "$OUT_ZIP" . -x "*.git*" -x ".DS_Store"
)

echo "==> Done: $OUT_ZIP ($(du -h "$OUT_ZIP" | cut -f1))"
echo "    Upload → extract → document root = /public → edit .env → open URL from MIGRATE_ONCE.txt once → delete public/run-migrations-once.php"
echo "    (Migration key is inside the zip in MIGRATE_ONCE.txt — also embedded in run-migrations-once.php.)"

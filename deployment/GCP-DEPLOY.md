# GCP deployment & SSH operations

Production for **https://trends.roberttodds.com** runs on a Google Cloud VM. Use **git** for deploys; use **SSH** to inspect production or copy state for local testing.

## Infrastructure

| Item | Value |
|------|--------|
| GCP project | `trends-rt-20260430` |
| VM | `trends-web-1` (`europe-west2-a`) |
| Static IP | `35.242.167.88` |
| SSH config alias | **`TrendsGCP`** (in `~/.ssh/config`, hostname `35.242.167.88`) |
| App root on server | `/var/www/looptrends` |
| Web root | `/var/www/looptrends/public` |
| PHP | 8.3 (CLI on VM) |
| Process / web | nginx + PHP-FPM |

See also [gcp-vm-live-notes.md](gcp-vm-live-notes.md) for backups, DNS, and rollback notes.

---

## GitHub & git credentials

| Item | Value |
|------|--------|
| Repository | `thedoctor0124/robert-todd-trends` |
| Default branch | `main` |
| Remote on server | `origin` → same GitHub URL |

**Important:** Git pushes must use the **`thedoctor0124`** GitHub account. The **`roberttodds`** account does **not** have write access and will get `403` on push.

On your Mac, before pushing:

```bash
gh auth switch --user thedoctor0124
gh auth setup-git    # once per machine — makes git use the active gh account
```

Verify:

```bash
gh auth status   # active account should be thedoctor0124
```

---

## Standard deploy (preferred)

### 1. On your Mac — commit and push

```bash
cd /path/to/rt-trends   # or local clone of robert-todd-trends

git status
git add <files>
git commit -m "Your message"
git push origin main
```

### 2. On the server — pull, build, migrate

```bash
ssh TrendsGCP 'cd /var/www/looptrends && \
  git pull origin main && \
  composer install --no-dev --optimize-autoloader --no-interaction && \
  npm ci && \
  npm run build && \
  php artisan migrate --force && \
  php artisan optimize:clear && \
  php artisan config:cache'
```

### 3. Smoke test

```bash
curl -s -o /dev/null -w "%{http_code}\n" https://trends.roberttodds.com/
curl -s -o /dev/null -w "%{http_code}\n" https://trends.roberttodds.com/admin/send-access
# 200 on home; 302 on admin routes when not logged in is expected
```

Hard-refresh the site in a browser after deploy (Cmd+Shift+R).

---

## If `git pull` fails on the server

Usually caused by local edits on the VM (e.g. after an old rsync deploy). **Only** reset if you are sure the server should match `origin/main` exactly:

```bash
ssh TrendsGCP 'cd /var/www/looptrends && git fetch origin && git reset --hard origin/main'
```

Then re-run the composer / npm / artisan steps from the deploy section above.

**Do not** use `git reset --hard` if someone has unpushed hotfixes only on the server — coordinate first.

---

## Fallback: rsync (emergency only)

Use only when `git push` is blocked and deploy cannot wait. Prefer fixing GitHub access and using git.

```bash
rsync -avz \
  --exclude '.env' \
  --exclude 'node_modules' \
  --exclude 'vendor' \
  --exclude '.git' \
  --exclude 'storage/logs' \
  --exclude 'storage/framework/views' \
  --exclude 'database/database.sqlite' \
  --exclude 'database/sync' \
  --exclude 'public/hot' \
  ./ TrendsGCP:/var/www/looptrends/
```

Then run the same `composer`, `npm`, `artisan` commands as in the standard deploy. Align git afterward:

```bash
ssh TrendsGCP 'cd /var/www/looptrends && git fetch origin && git reset --hard origin/main'
```

---

## SSH and syncing production state for local testing

Use **`TrendsGCP`** for all commands below. Production **`.env` is on the server only** — never commit it. Read credentials from the server when needed; do not store production passwords in this repo.

### Inspect production

```bash
# App & git state
ssh TrendsGCP 'cd /var/www/looptrends && git log -1 --oneline && git status -sb'

# Laravel
ssh TrendsGCP 'cd /var/www/looptrends && php artisan about'

# DB connection settings (no password in docs — read from output)
ssh TrendsGCP 'grep -E "^DB_|^APP_URL|^MAIL_" /var/www/looptrends/.env'

# Quick MySQL checks (uses credentials from .env on server)
ssh TrendsGCP 'cd /var/www/looptrends && php artisan tinker --execute="
echo App\Models\Season::count().\" seasons\n\";
echo App\Models\Publication::count().\" publications\n\";
echo App\Models\User::count().\" users\n\";
"'
```

### Export production database to your Mac

```bash
mkdir -p database/sync

# Dump (stderr warnings suppressed; password comes from server .env)
ssh TrendsGCP 'set -a && source /var/www/looptrends/.env 2>/dev/null; set +a; \
  mysqldump -h"${DB_HOST:-127.0.0.1}" -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" \
  --single-transaction --quick 2>/dev/null' \
  > database/sync/production.sql
```

Import locally (example: Homebrew MySQL as root):

```bash
mysql -u root -e "DROP DATABASE IF EXISTS looptrends; CREATE DATABASE looptrends CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root looptrends < database/sync/production.sql
```

Point local `.env` at MySQL and match production storage flags:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=looptrends
DB_USERNAME=root
DB_PASSWORD=

STORAGE_USE_PUBLIC_PATH=true
```

Run `php artisan config:clear` after changing `.env`.

`database/sync/` is gitignored — do not commit dumps.

### Sync uploads (covers, PDFs)

Production uses `public/uploads/` (~300MB+). Git does not include these files.

```bash
mkdir -p public/uploads
rsync -avz TrendsGCP:/var/www/looptrends/public/uploads/ public/uploads/
```

### Local run after sync

```bash
composer install
npm ci && npm run build
php artisan migrate   # usually no-op if DB imported from production
php artisan serve
```

Open http://127.0.0.1:8000 — homepage needs SS27 season + featured publication in DB (present after a full production import).

---

## Production `.env` reminders

Read live values on the server; typical production settings include:

- `APP_URL=https://trends.roberttodds.com`
- `DB_CONNECTION=mysql`
- `STORAGE_USE_PUBLIC_PATH=true` (uploads under `public/uploads/`)
- Real SMTP for outbound mail (invite links, order emails)

After changing `.env` on the server:

```bash
ssh TrendsGCP 'cd /var/www/looptrends && php artisan optimize:clear && php artisan config:cache'
```

---

## Admin features (post–access-invite deploy)

- **Send free access link:** `/admin/send-access` (sidebar: “Send Access Link”)
- **Claim URL pattern:** `/access/invite/{token}` (emailed to customer; 30-day expiry)

Requires migration `2026_05_22_000001_create_access_invites_table` on the server (`php artisan migrate --force`).

---

## Safety

- **Never** run `migrate:fresh`, `db:wipe`, or `schema:drop` against production.
- **Never** commit `.env`, `database/sync/*.sql`, or production uploads.
- Prefer **`git pull`** deploys over rsync so Mac, GitHub, and VM stay in sync.
- Use **`thedoctor0124`** for all pushes to `thedoctor0124/robert-todd-trends`.

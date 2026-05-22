# LoopTrends

Premium trend publication platform built with Laravel 12, Livewire 4, and Bootstrap 5. Designed for Robert Todd Ltd to distribute embedded flipbook publications with purchase and subscription access.

## Features

- **Flipbook viewer** with embedded publications from external services
- **Authentication** via email/password or Google OAuth
- **Individual purchases** and **season subscriptions** via Square payments
- **Admin panel** for managing seasons, publications, users, discount codes, and orders
- **Free pass system** — admins can grant complimentary access (triggers email notification)
- **Discount codes** — percentage or fixed amount, scoped to seasons or publications
- **Season-based navigation** with prev/next between publications
- **Local or cloud storage** — public disk on shared hosting, or optional Google Cloud Storage
- **Dockerfile** included if you deploy to containers (e.g. Cloud Run)

## Requirements

- PHP 8.2+
- Composer
- Node.js 18+
- MySQL 8.0 (or SQLite for local development)

## Local Development

```bash
# Install dependencies
composer install
npm install

# Copy environment file
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate

# Build assets
npm run build

# Start server
php artisan serve
```

## Environment Variables

See `.env.example` for all required configuration including:

- **Database** — MySQL connection details
- **Google OAuth** — `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`
- **Square Payments** — `SQUARE_ACCESS_TOKEN`, `SQUARE_LOCATION_ID`, `SQUARE_APPLICATION_ID`
- **Google Cloud Storage** (optional) — leave `GOOGLE_CLOUD_STORAGE_BUCKET` empty to use local `storage/app/public`

## Shared hosting (recommended for this app)

1. **PHP** 8.2+ (match `Dockerfile` / `composer.json`) with common extensions: `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `json`, `ctype`, `fileinfo`, `xml`, `gd`, `zip`, `curl`, `intl`.
2. **Document root** must be the `public/` directory (not the project root).
3. Install PHP deps: `composer install --no-dev --optimize-autoloader`.
4. **Front-end:** run `npm ci && npm run build` on your machine, then deploy the generated `public/build` directory (or run Node on the server if available).
5. Copy `.env.example` to `.env`. Set `APP_URL`, database, mail, Square, and Google OAuth. For **local uploads**: keep `GOOGLE_CLOUD_STORAGE_BUCKET` empty, use `FILESYSTEM_DISK=public`, `QUEUE_CONNECTION=sync`, and `CACHE_STORE=file` (defaults in `.env.example`).
6. `php artisan key:generate` and `php artisan migrate --force` (or use **`public/run-migrations-once.php`** with a secret in the file and `?key=...` in the browser, then delete that script).
7. `php artisan storage:link` so uploaded covers and PDFs are reachable under `/storage` (skip if `STORAGE_USE_PUBLIC_PATH=true`).
8. Ensure `storage/` and `bootstrap/cache/` are writable by the web server (e.g. `775`).

Optional later: add a cron line `* * * * * php /path/to/artisan schedule:run` if you start using the Laravel scheduler.

### Krystal one-shot zip (no SSH on server)

On your Mac (with `composer`, `npm`, `rsync`, `zip`, `openssl`, `perl`):

```bash
./scripts/build-krystal-zip.sh
```

This writes **`build/looptrends-krystal.zip`** (large: includes `vendor/` and compiled `public/build/`). Upload, extract, set **document root** to **`public`**, edit **`.env`**, then open the one-time URL in **`MIGRATE_ONCE.txt`** (key is pre-generated; no editing PHP). Delete **`public/run-migrations-once.php`** after you see OK. See **`INSTALL.txt`** in the zip.

## Docker / Cloud Run

Use one **production** GCP project for Artifact Registry, Cloud Build, and Cloud Run (set `gcloud config set project` and `GCP_PROJECT` to that project — not an unrelated default project).

```bash
# Local Docker development
docker-compose up -d

# Run migrations inside container
docker-compose exec app php artisan migrate

# Build image (pushes to Artifact Registry in the active gcloud project)
export GCP_PROJECT=your-production-project-id
gcloud config set project "$GCP_PROJECT"
gcloud builds submit --config cloudbuild.yaml .

# Deploy new revision to Cloud Run (after every successful build)
export GCP_PROJECT=your-production-project-id
./scripts/deploy-cloud-run.sh
```

## Admin Access

Any user with an `@roberttodds.com` email is automatically granted admin access on registration or Google login. Admins can also manually toggle admin status for other users.

## Architecture

| Layer | Technology |
|---|---|
| Framework | Laravel 12 |
| Frontend | Livewire 4 + Bootstrap 5 |
| Payments | Square Web Payments SDK |
| Auth | Laravel Socialite (Google OAuth) |
| Storage | Local `public` disk (default) or optional GCS |
| Database | MySQL |
| Deployment | Shared PHP host, VPS, or Docker / Cloud Run |

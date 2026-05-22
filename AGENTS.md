# Agent guide — Robert Todd Trends (LoopTrends)

Laravel 12 + Livewire 4 app. Production runs on a **GCP VM**; code lives on **GitHub**.

## Must-read before deploy or production debugging

| Topic | Document |
|--------|----------|
| **Deploy to GCP (git)** | [deployment/GCP-DEPLOY.md](deployment/GCP-DEPLOY.md) |
| **SSH, DB, uploads for local testing** | [deployment/GCP-DEPLOY.md#ssh-and-syncing-production-state-for-local-testing](deployment/GCP-DEPLOY.md#ssh-and-syncing-production-state-for-local-testing) |
| VM host notes (IP, paths, backups) | [deployment/gcp-vm-live-notes.md](deployment/gcp-vm-live-notes.md) |

## Quick reference

- **GitHub repo:** `https://github.com/thedoctor0124/robert-todd-trends`
- **Git push account:** `thedoctor0124` (not `roberttodds` — push will 403 otherwise). Run `gh auth switch --user thedoctor0124` and `gh auth setup-git` before `git push`.
- **SSH alias:** `TrendsGCP` → `35.242.167.88`, app at `/var/www/looptrends`
- **Live URL:** `https://trends.roberttodds.com`
- **Do not** rsync deploy unless `git push` is impossible; prefer `git pull` on the server.
- **Never** run destructive DB commands on production (`migrate:fresh`, `db:wipe`, etc.).

## Local development

See [README.md](README.md). Production data is **not** in git — sync DB/uploads from GCP when you need a realistic local copy (see GCP-DEPLOY.md).

# Google Cloud VM Deployment Notes

- Project: `trends-rt-20260430`
- VM: `trends-web-1`
- Zone: `europe-west2-a`
- Static IP: `35.242.167.88`
- App path: `/var/www/looptrends`
- Web root: `/var/www/looptrends/public`
- Backup bucket: `gs://trends-rt-20260430-backups`
- Backup script: `/usr/local/sbin/looptrends-backup`
- Backup cron: `/etc/cron.d/looptrends-backup`
- Snapshot policy: `looptrends-daily-snapshots`
- Uptime check: `looptrends-vm-http`

## Rollback

Do not change DNS until testing is complete. Until DNS is changed, Krystal remains the live site.

If DNS is changed and rollback is needed:

1. Point `trends.roberttodds.com` back to the previous Krystal target.
2. Keep the GCP VM running while DNS propagates so logs and data can be inspected.
3. If any live orders/uploads occurred on the VM, export the VM database and uploaded files before switching fully away.

## Post-DNS Cutover

After `trends.roberttodds.com` points at `35.242.167.88`, update `.env`:

```bash
APP_URL=https://trends.roberttodds.com
SESSION_SECURE_COOKIE=true
```

Then run:

```bash
cd /var/www/looptrends
php artisan optimize:clear
php artisan config:cache
sudo certbot --nginx -d trends.roberttodds.com
sudo systemctl reload nginx
```

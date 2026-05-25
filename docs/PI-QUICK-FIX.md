# Pi Engineer — Why the import got stuck at 5% (one-page summary)

## TL;DR

The drive auto-mounted, but `www-data` (the user PHP runs as) couldn't
read it. So the worker enumerated zero files and exited silently, and
the OLD JS didn't pick up the "done" state, so the UI stayed at 5%
forever.

## How to fix it RIGHT NOW with a drive plugged in

```bash
# 1. Fix the currently-mounted drive's permissions
sudo chmod -R a+rX /media/bridgebox

# 2. Reset the stuck progress file
sudo -u www-data rm /var/www/bridgebox/storage/app/usb_import_progress.json
```

Then click **Refresh** on the dashboard, then **Copy to BridgeBox**.

## How to make sure future USB plugs just work

```bash
# Add www-data to the user group that owns auto-mounted drives
sudo usermod -aG pi www-data
sudo systemctl restart php8.4-fpm
sudo systemctl restart nginx

# Test by unplugging the drive, plugging it back in, then:
sudo -u www-data ls /media/bridgebox
# Should list files. If "Permission denied", run the chmod above as well.
```

## Apply the v2 backend (so future failures show a useful error)

After replacing the three files in this zip:

```bash
sudo -u www-data php /var/www/bridgebox/artisan view:clear
sudo -u www-data php /var/www/bridgebox/artisan route:clear
sudo -u www-data php /var/www/bridgebox/artisan config:clear
```

Hard-refresh the dashboard (Ctrl + F5).

The new backend:
- Pre-flight checks drive read permissions and shows the chmod command
  on the dashboard if it fails (no more "stuck at 5%").
- Removed virus scanning entirely (per your request).
- Shows the destination folder per file as it copies
  ("Copying photo.jpg → image folder").
- Groups library content by folder with Preview + Download buttons.

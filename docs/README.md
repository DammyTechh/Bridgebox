# BridgeBox — USB Mount Permissions Note (for hardware engineer)

## What the screenshots showed

The drive `/media/bridgebox` was detected (great — `lsblk` is finding it
correctly), but:

- The drive row showed **0 file(s)** even though the USB stick has files
- The progress bar got stuck at **5% / "Preparing import…"** forever

## Why this happens

When `udisks2` auto-mounts a USB drive, it mounts it with permissions
tied to the user who logged in graphically (or to the `pi` user on a
headless install). The web server runs as a **different** user
(`www-data` on Apache/Nginx, or whoever owns the `php-fpm` process).

If `www-data` doesn't have read access to `/media/bridgebox`, then:

1. `inspectDrive()` enumerates 0 files (PHP can't open the directory).
2. The user clicks **Copy to BridgeBox** → the worker is launched.
3. The worker also gets 0 files → writes `status=done` and exits.

But the **OLD `usb-import.js`** running on your engineer's setup didn't
properly read the new "done" status because of a separate bug, so the
UI stays stuck at "Preparing import…" forever.

## Two parts to the fix

### Part A — Replace the two backend files with the v2 versions

| In this zip                                              | Replaces                                      |
|----------------------------------------------------------|-----------------------------------------------|
| `new/app/Services/UsbImportService.php`                  | `app/Services/UsbImportService.php`           |
| `new/public/assets/js/usb-import.js`                     | `public/assets/js/usb-import.js`              |
| `new/public/assets/css/usb-import.css`                   | `public/assets/css/usb-import.css`            |

Then on the Pi:

```bash
sudo -u www-data php artisan view:clear
sudo -u www-data php artisan route:clear
sudo -u www-data php artisan config:clear
```

And **hard-refresh the browser** (Ctrl + F5) to skip the cached old JS.

The new backend now:

- ✅ Has **no virus scan** (removed entirely as you requested).
- ✅ Does an explicit permission pre-flight on the drive — if the web
  user can't read it, the dashboard shows the **actual error** instead
  of hanging forever:
  > *"Cannot read /media/bridgebox. The web server (www-data) has no
  > permission. Run: sudo chmod -R a+rX /media/bridgebox"*
- ✅ Updates progress for every file with the destination folder, e.g.
  *"Copying photo.jpg → image folder (12/47)"*.
- ✅ Groups the **Available content** library by folder — Video,
  Audio, Document, Image, Archive — so each file appears under its
  own folder heading with **Preview** + **Download** buttons.

### Part B — Fix the udisks2 mount permissions on the Pi

This is the actual root cause of the "0 file(s)" / stuck-at-5% you saw.

#### Quick fix (works right now, applies to the currently-mounted drive)

```bash
sudo chmod -R a+rX /media/bridgebox
```

(`a+rX` = add read for everyone; `X` is uppercase X = add execute only on
directories, so files don't accidentally become executable.)

After running that, click **Refresh** on the dashboard and try the
import again. The `0 file(s)` will become the real count, and the copy
will progress normally.

#### Permanent fix (so future USB mounts auto-allow www-data)

Tell `udisks2` to mount removable drives with `umask=0022` so the web
server can always read them. Edit (or create) this file:

```bash
sudo nano /etc/udev/rules.d/99-bridgebox-usb.rules
```

Paste:

```
# BridgeBox: ensure USB drives mount with permissions readable by www-data
ACTION=="add", KERNEL=="sd[a-z][0-9]", SUBSYSTEM=="block", \
    ENV{ID_FS_USAGE}=="filesystem", \
    RUN+="/usr/bin/systemd-mount --no-block --collect $devnode /media/$env{ID_FS_LABEL}"
```

A simpler, more reliable alternative: **add `www-data` to the user group
that owns the mount point**. On Pi OS that's usually the `pi` user's
primary group:

```bash
sudo usermod -aG pi www-data
sudo systemctl restart php8.4-fpm
sudo systemctl restart nginx
```

Then unplug and re-plug the drive. The new mount will be readable by
`www-data` because it's now in the same group as the auto-mounter.

#### Verifying the fix

```bash
sudo -u www-data ls -la /media/bridgebox | head -5
```

If that command lists files (rather than "Permission denied" or empty),
the web server can read the drive and the dashboard will work.

You can also run the diagnostic command (if you applied the previous
fix package that included it):

```bash
sudo -u www-data php /var/www/bridgebox/artisan usb:diagnose
```

It will print exactly which detection step succeeded and what
`/media/bridgebox` looks like from `www-data`'s perspective.

## What changed visually for users

The teacher / admin dashboard's **USB Content Import** panel now:

1. Shows a per-file copy line like
   *"Copying lesson-3.mp4 → video folder (4/27)"* in real time.
2. Replaces the old yellow "Virus scanner not installed" warning with…
   nothing. (Virus scanning is gone — the warning was no longer
   relevant.)
3. The **Available content** grid is now grouped by folder (Video,
   Audio, Document, Image, Archive, Other), with **Preview** and
   **Download** buttons on every item. Students see the same grouped
   view but no Copy buttons (read-only).

## Rollback

If anything goes wrong, restore the previous versions from your git
history:

```bash
cd /var/www/bridgebox
git checkout -- app/Services/UsbImportService.php
git checkout -- public/assets/js/usb-import.js
git checkout -- public/assets/css/usb-import.css
```

# BridgeBox USB Content Import — Hardware Setup & Testing Guide

**For:** Hardware Engineer setting up the Raspberry Pi
**Feature:** USB flash content import to BridgeBox storage
**Last updated:** 2026-04-28

---

## What this feature does

When an admin or teacher plugs a USB flash drive into the Raspberry Pi:

1. The dashboard auto-detects the drive (refreshes every 8 seconds).
2. Admin/teacher selects the drive and clicks **Copy from USB**.
3. BridgeBox runs a virus scan (ClamAV preferred; falls back to a basic
   safety check if ClamAV is not installed).
4. If clean, all supported media files are copied into BridgeBox storage,
   automatically sorted into subfolders by file type:
   - `mp4`, `mkv`, `mov`, `avi`, `webm` → `library/video`
   - `mp3`, `wav`, `m4a`, `flac` → `library/audio`
   - `pdf`, `doc(x)`, `xls(x)`, `ppt(x)`, `txt`, `epub` → `library/document`
   - `jpg`, `png`, `gif`, `webp`, `svg` → `library/image`
   - `zip`, `rar`, `7z`, `tar.gz` → `library/archive`
   - anything else allowed → `library/other`
5. A live progress bar + mini "what's copying right now" panel shows progress.
6. Students see imported content on their dashboard and can preview/play/read/download — but **NOT copy from USB**.

Files larger than **2 GB** are skipped automatically (configurable in
`UsbImportService::MAX_FILE_SIZE_BYTES`).

Executable file types (`.exe`, `.bat`, `.cmd`, `.scr`, `.vbs`, `.ps1`,
`.com`, `.msi`, `.dll`, `.jar`) are blocked at the scan stage — they will
never be copied, even if ClamAV says the drive is clean.

---

## One-time Raspberry Pi setup

These steps must be done once on the Pi, in addition to the original
BridgeBox setup in `setup/setupcommands.sh`.

### 1. Install required packages

```bash
sudo apt update
sudo apt -y install clamav clamav-daemon udisks2 util-linux
```

- **clamav / clamav-daemon** — virus scanning
- **udisks2** — automatic mounting of plugged-in USB drives under
  `/media/<user>/<label>`
- **util-linux** — provides `lsblk` (already installed on Raspberry Pi OS,
  listed for completeness)

### 2. Update virus signature database

```bash
sudo freshclam
```

Run this manually the first time. After that, the `clamav-freshclam` service
runs in the background and updates daily. If the Pi has no internet (the usual
case in production), refresh signatures whenever the Pi is brought online for
maintenance, OR copy `/var/lib/clamav/*.cvd` from a connected machine.

### 3. Sudoers file

Edit (or create) `/etc/sudoers.d/bridgebox` and ensure these lines are
present (`www-data` should be replaced with the user running PHP-FPM —
check with `ps aux | grep php-fpm`):

```
www-data ALL=(root) NOPASSWD: /usr/bin/lsblk
www-data ALL=(root) NOPASSWD: /usr/bin/clamscan
www-data ALL=(root) NOPASSWD: /usr/bin/mount
www-data ALL=(root) NOPASSWD: /usr/bin/umount
```

Validate:
```bash
sudo visudo -cf /etc/sudoers.d/bridgebox
```

### 4. Storage symlink (one-time, may already be done)

BridgeBox stores imported files under `storage/app/public/library/...`. For
Nginx to serve them at `/storage/library/...` we need the standard Laravel
symlink:

```bash
cd /var/www/bridgebox
sudo -u www-data php artisan storage:link
```

You should see `[OK] The [public/storage] link has been connected to [storage/app/public].`

### 5. Permissions on storage folder

```bash
sudo chown -R www-data:www-data /var/www/bridgebox/storage
sudo chmod -R 775 /var/www/bridgebox/storage
```

### 6. Run the new database migration

```bash
cd /var/www/bridgebox
sudo -u www-data php artisan migrate
```

This creates the `imported_contents` table.

### 7. Bump Nginx upload size (already in setup, verify)

In `/etc/nginx/sites-available/bridgebox`, the `client_max_body_size` should
be 200M or higher (it already is, in the original setup). Even though USB
imports don't go through HTTP upload, this protects future flexibility:

```
client_max_body_size 200M;
```

Reload after any change:
```bash
sudo nginx -t && sudo systemctl reload nginx
```

### 8. Auto-mount behavior

`udisks2` auto-mounts USB drives plugged into the Pi to:
```
/media/<username>/<volume-label>
```
e.g., `/media/pi/USB16GB`.

BridgeBox detects these via `lsblk` and presents them in the dropdown.

If the Pi runs **headless without a desktop**, `udisks2` may not auto-mount.
In that case, configure `usbmount` instead:

```bash
sudo apt -y install usbmount
```

`usbmount` mounts to `/media/usb0`, `/media/usb1`, etc. BridgeBox handles
both layouts.

---

## How to test on the Pi

### Test 1 — Drive detection
1. Boot the Pi, log in to the admin dashboard at `http://10.42.0.1/dashboard/admin`.
2. Plug in a USB flash drive. Within ~8 seconds, the **USB Content Manager**
   panel should show the drive in its dropdown (e.g., `USB16GB (sda1) — 14.2 GB free of 14.9 GB`).
3. If it doesn't appear, click **Refresh**.
4. SSH into the Pi and run `lsblk -P -o NAME,LABEL,MOUNTPOINT,RM,TYPE,SIZE`.
   The drive should show `RM="1"` and a `MOUNTPOINT` value. If
   `MOUNTPOINT=""`, mount it manually:
   ```bash
   sudo mkdir -p /media/usb-test
   sudo mount /dev/sda1 /media/usb-test
   ```
   Then click Refresh again.

### Test 2 — Virus scan (clean drive)
1. Put a few harmless files (one mp4, one pdf, one mp3, one jpg) on a USB drive.
2. Plug it in, select it, click **Copy from USB**.
3. Status line should show: "Scanning drive for viruses..." then "Copying files..."
4. Mini panel should show each file name and category as it copies.
5. Final status: "Copied N of N file(s)".
6. Verify on disk:
   ```bash
   ls /var/www/bridgebox/storage/app/public/library/video/
   ls /var/www/bridgebox/storage/app/public/library/audio/
   ls /var/www/bridgebox/storage/app/public/library/document/
   ls /var/www/bridgebox/storage/app/public/library/image/
   ```

### Test 3 — Virus scan (EICAR test virus)
ClamAV ships with a built-in test signature. Create a test file with the
EICAR string:
```bash
echo 'X5O!P%@AP[4\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*' > /tmp/eicar.com
```
Copy `/tmp/eicar.com` to a USB drive, plug it into the Pi, click Copy.

Expected: Scan fails, copy aborted, error banner shows infected file path.

### Test 4 — Student visibility
1. Log in as a student.
2. The **Shared Resources** panel on the dashboard should show recently
   imported files.
3. Click **View all** → student library page (`/dashboard/student/library`).
4. Confirm:
   - Students see Play/View/Read/Download buttons.
   - Students DO NOT see any "Copy" button.
   - Students cannot delete files.

### Test 5 — Resilience
- Yank the USB drive mid-copy → status should report "failed" within a few seconds.
- Restart Nginx mid-copy → on next page load, the in-progress state should
  be detected from the progress JSON file and resume showing.

---

## Testing on a developer Windows laptop (no Pi)

The same code works on Windows so the developer can test without the Pi:

1. Install BridgeBox on the laptop normally (`php artisan serve`).
2. Plug a USB flash drive into the laptop.
3. Open `http://127.0.0.1:8000/dashboard/admin`.
4. The USB Content Manager will use **WMIC** to list drive letters (e.g., `E:\\`).
5. ClamAV is normally NOT installed on dev laptops → BridgeBox falls back
   to the basic scan (extension blocklist + EICAR check). The status banner
   will mention this clearly.

If WMIC is missing or restricted, BridgeBox lists every non-C drive letter
as a fallback so testing still works.

---

## Where files live

```
/var/www/bridgebox/storage/app/public/library/
    ├── video/
    ├── audio/
    ├── document/
    ├── image/
    ├── archive/
    └── other/
```

Public URL of a file: `http://10.42.0.1/storage/library/<category>/<file>`

Database table: `imported_contents`
- `original_name` — file name as on the USB
- `stored_path` — relative path inside `storage/app/public`
- `category` — video/audio/document/image/archive/other
- `extension`, `mime_type`, `size_bytes`, `hash`
- `source_drive` — which USB it came from
- `scan_status` — clean / infected / skipped / unscanned
- `scan_engine` — clamav / basic / none
- `imported_by` — admin or teacher user id
- `created_at`, `updated_at`

To wipe all imported content (e.g., before redeploy):
```bash
cd /var/www/bridgebox
sudo -u www-data php artisan tinker --execute="App\Models\ImportedContent::truncate();"
sudo rm -rf storage/app/public/library/*/*
```

---

## Troubleshooting

| Symptom | Likely cause | Fix |
|---|---|---|
| Dropdown stays "Detecting USB drives..." | `lsblk` not in PATH for `www-data` | Sudoers entry above; check with `sudo -u www-data lsblk` |
| Drive plugged in, not in dropdown | Not mounted | Install `udisks2` OR `usbmount`; verify with `mount` |
| Copy fails with "Drive path not accessible" | `www-data` can't read mount | `chmod o+rx` on the mount, or mount with `umask=0022` |
| ClamAV scan times out | Big drive, signatures not loaded | Run `freshclam`; increase `clamscan` timeout in `UsbImportService::scanWithClamAv` |
| Files not visible to students | `php artisan storage:link` not run | Run it; restart Nginx |
| Permission denied writing to `library/` | storage owner wrong | `chown -R www-data:www-data storage/` |
| Progress stuck at 0% | Progress JSON not writable | `chmod 775 storage/app` |
| `php-fpm` killed mid-copy | OOM | Increase `memory_limit` in `php.ini` to 512M; bump Pi swap |

---

## Production checklist (final)

Before declaring this feature done in production:

- [ ] `clamav` + `clamav-daemon` installed
- [ ] `freshclam` ran successfully at least once
- [ ] `udisks2` installed AND USB auto-mounts to `/media/<user>/...`
- [ ] `php artisan migrate` run; `imported_contents` table exists
- [ ] `php artisan storage:link` run; `public/storage` symlink exists
- [ ] Sudoers file validated with `visudo -cf`
- [ ] Tested with clean USB → import succeeds
- [ ] Tested with EICAR virus file → import blocked
- [ ] Tested student library shows imported items
- [ ] Tested students CANNOT delete or copy from USB
- [ ] `storage/` owned by `www-data` with `775` perms
- [ ] Nginx `client_max_body_size` ≥ 200M
- [ ] PHP `memory_limit` ≥ 256M, `max_execution_time = 0` for FPM (so big copies don't get killed)

---

## Contact

If anything in this guide is unclear or doesn't match the deployed system,
ping the BridgeBox developer team before improvising — small permission
mistakes here can break the live LMS for users who are mid-class.

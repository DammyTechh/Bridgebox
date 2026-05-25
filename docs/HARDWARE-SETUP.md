# USB Content Import — Hardware Setup & Test Guide

This document describes how to prepare a Raspberry Pi running BridgeBox so the
**USB Content Import** feature works end-to-end. It is written for the hardware
engineer setting up the production unit.

---

## What this feature does

When an admin or teacher plugs a USB flash drive into the Raspberry Pi:

1. The drive appears on the **admin** and **teacher** dashboards.
2. Clicking **Copy to BridgeBox** kicks off a background job that:
   - Runs a virus scan on the drive (ClamAV).
   - Copies every safe file to `storage/app/public/library/{category}/`,
     organised by file type:
     - `mp4`, `mkv`, `webm`, `mov`, `avi`, `wmv`, `flv`, `m4v`, `3gp` → **video/**
     - `mp3`, `wav`, `ogg`, `m4a`, `flac`, `aac`, `wma`, `opus` → **audio/**
     - `pdf`, `doc`, `docx`, `xls`, `xlsx`, `ppt`, `pptx`, `txt`, `rtf`,
       `odt`, `ods`, `odp`, `csv`, `md`, `epub` → **document/**
     - `jpg`, `jpeg`, `png`, `gif`, `webp`, `bmp`, `svg`, `tiff` → **image/**
     - `zip`, `rar`, `7z`, `tar`, `gz` → **archive/**
     - everything else (excluding blocked types) → **other/**
   - Records each imported item in the `imported_contents` database table.
3. The dashboards show a live progress bar with the **% complete**, the
   **current file being copied**, the **category icon**, and the
   **virus-scan status**.
4. Students automatically see the imported content in their **Shared
   Resources** panel and can preview / play / read / download — but they
   **cannot** trigger imports themselves.

> **Blocked extensions** (always rejected, regardless of scan result):
> `exe`, `msi`, `bat`, `cmd`, `com`, `scr`, `pif`, `vbs`, `vbe`, `js`, `jse`,
> `wsf`, `wsh`, `ps1`, `psm1`, `jar`, `reg`, `dll`, `sys`, `app`, `sh`, `bash`.

---

## 1. Prerequisites

Already installed on the Pi as part of the existing BridgeBox setup:
- Raspberry Pi OS
- Nginx
- PHP 8.4-FPM
- SQLite (PHP extension `pdo_sqlite`)
- The `bridgebox` Laravel app at `/var/www/bridgebox`

---

## 2. Install required system packages

SSH into the Pi and run:

```bash
sudo apt update
sudo apt install -y clamav clamav-daemon util-linux exfat-fuse exfatprogs ntfs-3g udisks2
```

What each one does:

| Package          | Why we need it                                                        |
|------------------|-----------------------------------------------------------------------|
| `clamav`         | Provides `clamscan`, the virus scanner used before any copy starts.   |
| `clamav-daemon`  | Keeps virus signatures fresh in the background.                       |
| `util-linux`     | Provides `lsblk`, used to detect mounted USB drives.                  |
| `exfat-fuse` + `exfatprogs` | exFAT support (most modern USB drives use exFAT).          |
| `ntfs-3g`        | NTFS support (Windows-formatted USB drives).                          |
| `udisks2`        | Lets the Pi auto-mount USB drives without manual intervention.        |

Then update virus signatures the first time:

```bash
sudo systemctl stop clamav-freshclam
sudo freshclam
sudo systemctl start clamav-freshclam
```

> The first download of signatures takes a few minutes and needs internet.
> After the initial sync, `clamav-freshclam` updates them automatically
> whenever the Pi briefly has internet access (e.g. during maintenance).

---

## 3. Confirm USB auto-mount works

1. Plug in any USB flash drive.
2. Run:

   ```bash
   lsblk -o NAME,RM,TYPE,MOUNTPOINT,SIZE,LABEL
   ```

3. You should see a row whose `RM` (removable) column is `1` and whose
   `MOUNTPOINT` column is non-empty (typically something like
   `/media/pi/MYUSB` or `/run/media/pi/MYUSB`). If the `MOUNTPOINT` is
   empty, manual auto-mount is not running — fix it with:

   ```bash
   sudo systemctl enable --now udisks2
   ```

   …and re-plug the drive.

---

## 4. Configure sudoers (optional, recommended)

The USB import service does **not** require sudo — copying happens entirely
under the `www-data` user inside the Pi's own filesystem. Sudo is only
needed if you ever want to allow `umount` from the dashboard. For now you
can skip this step; see `docs/admin-actions-sudoers.md` if you want to
extend it later.

---

## 5. Database migration

From the project root (`/var/www/bridgebox`):

```bash
sudo -u www-data php artisan migrate
sudo -u www-data php artisan storage:link
```

The `storage:link` step is critical — it creates the symlink that lets
Nginx serve imported files directly from `public/storage/library/...`
(matches the README's "Static files served by Nginx, not Laravel"
performance design).

---

## 6. Permissions

Make sure the web user can write into `storage/app/public/library`:

```bash
sudo mkdir -p /var/www/bridgebox/storage/app/public/library
sudo chown -R www-data:www-data /var/www/bridgebox/storage
sudo chmod -R 775 /var/www/bridgebox/storage
```

---

## 7. Optional: bump Nginx upload limits for very large imports

The import does **not** go through Nginx — files are read from the USB
drive locally and written straight to disk. So the existing
`client_max_body_size 200M;` in your Nginx vhost is enough.

---

## 8. End-to-end test (with a real USB drive)

1. Plug a USB flash drive containing a few `.mp4`, `.mp3`, `.pdf`, and
   `.jpg` files into the Pi.
2. From a phone connected to the BridgeBox hotspot, open
   `http://10.42.0.1` in the browser and log in as **admin**.
3. Scroll down to **USB Content Import**. The drive should appear within
   ~3 seconds with a Copy button.
4. Tap **Copy to BridgeBox**. The progress card animates:
   - The percentage climbs from 0 to 100.
   - The file name and category change in real time.
   - The scan badge shows **Clean (clamav)** once the scan finishes.
5. When the job is done, the **Available content** grid populates.
6. Log in as a **student**. The same files appear under
   **Shared Resources**, with **Open** buttons. There is **no Copy
   button** for students.

---

## 9. Test on Windows (developer / pre-deployment)

Even without the Pi, you can validate the entire workflow on a Windows
laptop:

1. Run `php artisan serve` from PowerShell.
2. Plug a USB drive into a USB port on the laptop.
3. Open the admin dashboard at `http://127.0.0.1:8000/dashboard/admin`.
4. The drive will appear by its drive letter (e.g. `G:\`) with the same
   metadata. The Copy button works identically.

> ⚠️ ClamAV is generally not present on Windows dev machines. The panel
> will display a yellow **"Virus scanner not installed"** notice and use
> only the extension blocklist. This is expected behaviour for dev — on
> the Pi (with `clamav` installed) the warning will not appear.

---

## 10. Troubleshooting

**The drive doesn't appear on the dashboard**

- Run `lsblk -o NAME,RM,MOUNTPOINT` on the Pi. If MOUNTPOINT is empty,
  re-enable udisks2: `sudo systemctl restart udisks2` and re-plug.
- Check the laravel log: `tail -f /var/www/bridgebox/storage/logs/laravel.log`.

**Progress bar gets stuck at 0% / "Preparing…"**

- The background worker process failed to launch. Check that
  `/var/www/bridgebox/artisan` is executable by `www-data`:
  `sudo -u www-data php /var/www/bridgebox/artisan list | grep usb:run-import`
  should print the new command.

**"ClamAV (clamscan) is not installed" warning on the Pi**

- Re-run `sudo apt install -y clamav clamav-daemon`.
- Verify with `which clamscan` — it should print `/usr/bin/clamscan`.

**Files copied but don't appear in Shared Resources**

- Check the storage symlink: `ls -la /var/www/bridgebox/public/storage`.
- It should link to `../storage/app/public`. If missing, run
  `sudo -u www-data php artisan storage:link`.

**A copy is "stuck" — how to reset**

- Delete the progress file:
  `sudo -u www-data rm /var/www/bridgebox/storage/app/usb_import_progress.json`
- The dashboard will show "No drive detected" again, and the next plug-in
  will start fresh.

---

## 11. Maintenance notes

- The import job can be cancelled from the database side by clearing the
  progress JSON (see Troubleshooting).
- Imported files live in `storage/app/public/library/{video,audio,...}`.
  To bulk-export the whole library to a backup USB drive, just `cp -r`
  that folder.
- Records in `imported_contents` keep the SHA-256 hash of every file —
  re-importing the same file will create a duplicate row but a different
  stored filename. Future versions can de-duplicate against this hash.

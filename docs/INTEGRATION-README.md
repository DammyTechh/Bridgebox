# USB Content Import — Integration Guide

This package adds the **USB Content Import** feature to BridgeBox without
breaking anything that's already in production.

The package contains three folders:

```
new/         <-- copy these files into the project as-is
modified/    <-- modified versions of existing files OR small patches to apply
docs/        <-- documentation (drop into your /docs folder if you have one)
```

---

## ✅ Step-by-step integration (do these in order)

### 1. Drop in the new files (no replacements yet)

Copy each of these into the matching path in your `bridgebox/` project:

| Source (this package)                                                | Destination (your project)                                            |
|----------------------------------------------------------------------|-----------------------------------------------------------------------|
| `new/database/migrations/2026_04_28_000001_create_imported_contents_table.php` | `database/migrations/2026_04_28_000001_create_imported_contents_table.php` |
| `new/app/Models/ImportedContent.php`                                 | `app/Models/ImportedContent.php`                                      |
| `new/app/Services/UsbImportService.php`                              | `app/Services/UsbImportService.php`                                   |
| `new/app/Console/Commands/RunUsbImportCommand.php`                   | `app/Console/Commands/RunUsbImportCommand.php`                        |
| `new/app/Http/Controllers/UsbImportController.php`                   | `app/Http/Controllers/UsbImportController.php`                        |
| `new/resources/views/partials/usb-import-panel.blade.php`            | `resources/views/partials/usb-import-panel.blade.php`                 |
| `new/public/assets/css/usb-import.css`                               | `public/assets/css/usb-import.css`                                    |
| `new/public/assets/js/usb-import.js`                                 | `public/assets/js/usb-import.js`                                      |

> None of these files exist in the project today. Just copy them in.

### 2. Apply the small patches

#### 2a) `routes/web.php`

Open `modified/routes/web-patch.php` and follow the two-step instruction
inside it (add one `use` line near the top, append one Route block at
the bottom). **Do not delete anything** in the existing routes file.

#### 2b) `app/Console/Kernel.php`

Open `modified/app/Console/Kernel-patch.php` and add the single line it
shows to the `$commands` array.

### 3. Replace the three dashboard views

These three files in `modified/` are full replacements:

| Source (this package)                                          | Destination                                              |
|----------------------------------------------------------------|----------------------------------------------------------|
| `modified/resources/views/dashboards/admin.blade.php`          | `resources/views/dashboards/admin.blade.php`             |
| `modified/resources/views/dashboards/teacher.blade.php`        | `resources/views/dashboards/teacher.blade.php`           |
| `modified/resources/views/dashboards/student.blade.php`        | `resources/views/dashboards/student.blade.php`           |

What changed in each:

- **admin.blade.php** — the SSID/password "Hotspot Settings" card has
  been **removed**. The Hotspot ON/OFF toggle and the Hotspot Status tile
  at the top of the page are **kept** (they were not part of the
  removal). The new USB Content Import panel is inserted directly after
  the Admin Controls section.

- **teacher.blade.php** — every existing element is intact (hero,
  metrics, lanes, quick-access). The USB import panel is **appended at
  the bottom** in compact ("mini") mode so it doesn't disturb the
  existing layout.

- **student.blade.php** — every existing element is intact. A new
  **Shared Resources** panel is appended at the bottom showing the
  imported library only. **No Copy / Refresh / Start buttons are
  rendered** for students — the partial automatically hides them when
  `variant => 'student'`.

### 4. Run the migration & link storage

From the project root:

```bash
php artisan migrate
php artisan storage:link
```

(On the Raspberry Pi, prefix both commands with `sudo -u www-data`.)

### 5. Clear caches

```bash
php artisan config:clear
php artisan view:clear
php artisan route:clear
```

If you're using cached config/routes in production, re-cache afterwards:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 6. Test it

#### Windows (your dev machine, before the engineer has the Pi)

1. Plug a USB drive into your laptop.
2. Run `php artisan serve` from the project root.
3. Sign in as admin at `http://127.0.0.1:8000/dashboard/admin`.
4. Scroll to **USB Content Import** — your USB drive should appear with
   its drive letter (e.g. `G:\`).
5. Click **Copy to BridgeBox** — watch the live progress bar.
6. Sign in as a teacher in another browser tab — same panel appears at
   the bottom of the teacher dashboard.
7. Sign in as a student — **Shared Resources** appears at the bottom,
   showing the imported files with **Open** buttons. No copy controls.

> A yellow "Virus scanner not installed" notice will show on Windows.
> That's expected — ClamAV usually isn't on dev machines. The
> extension blocklist still rejects `.exe`, `.bat`, `.dll`, etc.

#### Raspberry Pi (production)

Hand the engineer `docs/usb-import-setup.md` — it walks through:
- Installing ClamAV + USB filesystem support packages
- Verifying auto-mount with `lsblk`
- The end-to-end smoke test
- Common troubleshooting (stuck progress, missing storage symlink, etc.)

---

## 🔒 Safety / rollback

If anything goes wrong, you can roll back cleanly:

```bash
# Roll back the database table
php artisan migrate:rollback --step=1

# Restore the three dashboard views from your git history
git checkout -- resources/views/dashboards/admin.blade.php
git checkout -- resources/views/dashboards/teacher.blade.php
git checkout -- resources/views/dashboards/student.blade.php

# Revert routes/web.php and Kernel.php from git history
git checkout -- routes/web.php app/Console/Kernel.php

# Optionally remove the new files
rm app/Models/ImportedContent.php
rm app/Services/UsbImportService.php
rm app/Console/Commands/RunUsbImportCommand.php
rm app/Http/Controllers/UsbImportController.php
rm resources/views/partials/usb-import-panel.blade.php
rm public/assets/css/usb-import.css
rm public/assets/js/usb-import.js
rm storage/app/usb_import_progress.json     # if it was created
rm -rf storage/app/public/library            # if files were imported
```

This is why the new feature lives in **separate** files everywhere it
can — so adding/removing it is mostly drop-in.

---

## What files changed and why — quick reference

| File                                            | Type      | Why                                                                                  |
|-------------------------------------------------|-----------|--------------------------------------------------------------------------------------|
| `database/migrations/...create_imported_contents_table.php` | NEW       | Stores metadata for every imported file.                                             |
| `app/Models/ImportedContent.php`                | NEW       | Eloquent model + extension-to-category mapping.                                      |
| `app/Services/UsbImportService.php`             | NEW       | Drive detection (Win + Linux), virus scan, copy-with-progress.                       |
| `app/Console/Commands/RunUsbImportCommand.php`  | NEW       | Background worker the service launches in a detached process.                        |
| `app/Http/Controllers/UsbImportController.php`  | NEW       | HTTP endpoints: drives, start, progress, list, destroy.                              |
| `resources/views/partials/usb-import-panel.blade.php` | NEW | Reusable Blade partial — admin / teacher / student variants.                         |
| `public/assets/css/usb-import.css`              | NEW       | Styles using BridgeBox's existing palette (no global CSS changes).                   |
| `public/assets/js/usb-import.js`                | NEW       | Drives the panel — vanilla JS, no build step.                                        |
| `routes/web.php`                                | PATCH     | Adds five `/usb/*` routes.                                                           |
| `app/Console/Kernel.php`                        | PATCH     | Registers the new artisan command.                                                   |
| `resources/views/dashboards/admin.blade.php`    | REPLACED  | Removed SSID/password card; added USB panel.                                         |
| `resources/views/dashboards/teacher.blade.php`  | REPLACED  | Appended USB panel after existing lanes.                                             |
| `resources/views/dashboards/student.blade.php`  | REPLACED  | Appended read-only Shared Resources panel.                                           |

That's it — 13 files touched, 8 brand new, 2 patched, 3 replaced.

---

## Need help?

If anything misbehaves after the integration, the most useful files to
check are:

1. `storage/logs/laravel.log` — application errors.
2. `storage/app/usb_import_progress.json` — the live job state.
3. The browser DevTools network tab while watching `/usb/drives` and
   `/usb/progress` — both should return 200 with JSON.

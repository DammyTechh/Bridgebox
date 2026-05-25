# BridgeBox

<p align="center">
  <img src="docs/bridgebox.jpeg" alt="BridgeBox device" width="520">
</p>

BridgeBox is an offline-first learning hub designed for schools with limited connectivity. It provides local access to lessons, assignments, quizzes, and exams, with role-based dashboards for admins, teachers, and students.

## Highlights

- Offline-first UX with local caching
- Role-based dashboards (Admin, Teacher, Student)
- Content management for classes, subjects, topics, lessons, assessments, and assignments
- CSV bulk upload for students
- Export tools for assessments and assignments

## Requirements

- PHP 8.2+ (8.3 recommended)
- Composer
- A database (MySQL/MariaDB or SQLite)
- Node.js 18+ (optional, only if rebuilding front-end assets)

## Installation

1. Clone the repository

```bash
git clone <your-repo-url>
cd bridgebox
```

2. Install dependencies

```bash
composer install
```

3. Create your environment file

```bash
cp .env.example .env
php artisan key:generate
```

4. Configure the database in `.env`

SQLite:

```ini
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```

5. Run migrations

```bash
php artisan migrate
```

## First-Time Setup (Installer)

On first web request, BridgeBox redirects to `/install`.

At `/install`, you can:

- Create the first admin account
- Select which school sections to create
- Optionally seed demo data

After completing the installer, a lock file is created at `storage/app/installed.lock` to prevent reruns. To re-run the installer, delete that file.

## Seeding (Optional)

If you prefer manual seeding without the installer:

```bash
php artisan db:seed --class=SectionsSeeder
```

To seed demo data:

```bash
php artisan db:seed --class=DemoAcademicSeeder
```

## Running the App

```bash
php artisan serve
```

Then visit:

- Landing page: `http://10.42.0.1/`
- Installer: `http://10.42.0.1/install` (only if not installed)

## Offline Support

BridgeBox uses a service worker (`public/sw.js`) to cache core pages and assets for offline use. If you add new static content (for example, CSV samples or offline resources), update the precache list in `public/sw.js` and bump the cache version.

## CSV Bulk Upload (Students)

Admin users can bulk upload students using a CSV file from:

`Admin Dashboard -> Students -> Bulk Upload`

A sample CSV is available at:

`public/assets/samples/students.csv`

## Testing

```bash
php artisan test
```

## Troubleshooting

- If the installer redirects unexpectedly, ensure `storage/app/installed.lock` is removed.
- If migrations fail, confirm your `.env` database settings.

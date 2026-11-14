# Doctor profile backend

Laravel 13 backend for the existing doctor website. Requires PHP 8.3+, Composer, PHP GD with WebP support, Fileinfo and a dedicated MySQL-compatible database.

## Initial setup

1. Copy `.env.example` to `.env` and configure database credentials.
2. Run `composer install`.
3. Run `php artisan key:generate`.
4. Run `php artisan migrate --seed`.
5. Run `php artisan website:import` to import the saved reference content and photographs, then `php artisan project:status`.
6. Run `php artisan serve --host=127.0.0.1 --port=8000`.

No Node installation or frontend build is needed for the admin or public pages. Public styles, scripts and fonts are stored under `public/site/`. Its AdminLTE, Bootstrap, icons and font assets are stored under `public/vendor/`. Private keys, credentials, vendor packages and local database files are ignored by Git. Do not use PHP 8.0 from the existing XAMPP installation.

## Validation

Run `php artisan test --compact`. Tests use SQLite in memory by default and refuse to run against an application database. External MySQL test configurations must use `APP_ENV=testing` and a dedicated database whose name ends with `_test`.

## Scope

Phase 2 adds administrator login/logout, rate-limited authentication, administrator authorization, password changes and an AdminLTE dashboard with database-backed totals.

Phase 3 adds `/admin/settings` and `/admin/media`. Settings edit only the primary doctor profile and primary site configuration. Qualifications and additional telephone numbers are entered one per line; introduction videos accept a YouTube link or ID. Social links require HTTPS. Profile images, logo and favicon can be selected from uploaded images, changed or cleared.

Phase 4 provides `/admin/content/{module}` lists and forms for sliders, featured statistics, services, education, awards, gallery, FAQs, videos, experience, chambers, consultation steps and specialist highlights. Sidebar links and dashboard cards open their management pages. Only these 12 modules are writable through the content routes; settings remain in their own editor, and reviews have a separate moderation queue at `/admin/reviews`.

## Content management behavior

- New content starts hidden. Use the Visible checkbox or list action to change visibility. Display order is a non-negative integer; lower values appear first, with record ID breaking ties.
- Lists support search, visibility filters and pagination. Archive hides content and retains it for restoration. Restored content remains hidden until explicitly enabled. Only archived content can be permanently deleted; library image files are retained.
- Image-enabled modules reuse the existing media library. Sliders and gallery records require an image. Archived content retains its image references and continues to protect those images from deletion.
- Service slugs are generated when empty and remain unique across active and archived records. YouTube video fields accept a video ID or a recognized YouTube URL. Experience dates reject reversed ranges; current positions have no end date. Text is rendered through escaped Blade output.
- Chambers have a timezone and up to 28 time slots. Slots use weekday numbers 0 (Sunday) through 6 (Saturday), must end later on the same day, and cannot have duplicate opening times on the same weekday. Active slots on the same weekday cannot overlap. Saving a chamber updates its hours transactionally; archiving preserves hours, and permanent deletion cascades them.

Phase 5 serves the database-backed website at `/`, `/about`, `/services`, `/faqs`, `/gallery` and `/videos`, using shared Blade navigation/footer and the original responsive design. Saved settings update immediately; published content is ordered by display order and ID. Hidden/archived items stay private. Reviews appear only when approved with an approval date. Phase 6 enables public review submissions and administrator moderation. Original static page URLs redirect permanently to their matching public pages.

Run `php artisan website:import` once after migrations to load the offline snapshot from `database/seeders/data/website.json` and `database/seeders/assets/`. It imports 39 content items and one review already published on the reference, plus managed WebP images. It creates no accounts and makes no requests to the reference website. Import mappings prevent duplicates and preserve later edits, cleared fields, archives and deletions. On the first run, nonempty settings are preserved and blank fields filled; modules with existing custom content, including archived content, are skipped and reported. The import is not a reset command. Back up the database and `storage/app/public/uploads/` together.

The original static copy remains in `dist/` for comparison. Admin saves update the Laravel website, not those static files. Production must serve `backend/public/` on PHP-capable hosting; the existing static hosting manifest does not publish this backend. Embedded YouTube videos still require internet access.

## Media storage and replacement

- JPG, PNG and WebP uploads are validated by content, extension, file size (5 MB maximum) and dimensions (4096 × 4096 maximum), then decoded and re-encoded as WebP. SVG and executable uploads are rejected.
- Normalized files live in `storage/app/public/uploads/YYYY/MM/` with generated UUID filenames. Original filenames, dimensions, byte sizes, alternative text and uploader IDs are saved as metadata. Include this directory and the database in backups.
- Public image responses use the `/images/{media}` route and a fixed WebP content type with `nosniff`. There is no dependency on a Windows storage symlink. These are public website images, not a private document store.
- Replacing an image preserves its database ID and references, writes a new versioned image URL and removes the old file. Choosing a different image in Settings leaves the previous library item available for reuse.
- Deletion checks references in doctor/site settings and all existing image-enabled content tables, including soft-deleted records. Used images must be detached first. File operations are limited to managed image paths on the public disk.
- Search and Bootstrap pagination are available in the library. Alternative text can be edited without replacing a file.

The isolated PHP runtime on this machine uses `upload_max_filesize=6M` and `post_max_size=8M`. Production PHP must allow at least a 5 MB file plus multipart form overhead, have GD WebP support and permit writes to Laravel's storage directories.

## Administrator access

Run `php artisan admin:create` in an interactive terminal. It prompts for a username, display name and hidden password with confirmation. Usernames use 3–64 lowercase letters, numbers, dots, underscores or hyphens, starting with a letter or number; login ignores surrounding spaces and case. Contact email is optional via `--email=ADDRESS`. Passwords need at least 12 characters, uppercase and lowercase letters, a number and a symbol. Existing accounts are not overwritten or promoted. For automation, use `php artisan admin:create USERNAME --name="DISPLAY NAME" --password-stdin` and pipe the password through standard input; never put a password in command arguments.

Sign in at `/admin/login` using your username and password. Existing email-based accounts receive distinct usernames derived from the email prefix when the username migration runs; password hashes and administrator privileges are preserved. To inspect or change an existing administrator username, run `php artisan admin:username ACCOUNT [USERNAME]`, where ACCOUNT is the administrator ID or existing email. This command never changes passwords or grants administrator status. Password changes require the current password and sign out existing sessions. There is no public registration endpoint and no hard-coded administrator password. On this Windows machine, the initial development account has contact email `admin@doctor-profile.test` and, after migration, username `admin`; its generated password is saved in ignored `.local/admin-access.txt`. Change it after signing in. This file is not updated by password changes. Run `./scripts/backend.ps1 create-admin` from the repository root to create another account securely.

See [the Phase 1 database plan and setup guide](../docs/backend-phase-1.md) for module details and Windows helper commands.

## Review submissions and moderation

Visitors submit a name, optional location/workplace, 1–5 rating and 10–3,000 character review using the homepage’s Write a Review dialog. Publishing consent is required. Successful submissions show an acknowledgement and remain Pending until an administrator approves them. Visitor input cannot set approval, archive or account fields. Reviews render as escaped plain text; no email, phone or private medical details are requested.

Submissions use Laravel’s web session and request-forgery protection, a hidden spam-trap field and a separate rate limit of five attempts per IP per hour. Invalid submissions preserve their input and reopen the form with errors. Submission limiting uses the configured Laravel cache store, which must be shared between application instances in production.

The dashboard Review card and sidebar open `/admin/reviews`. The queue defaults to Pending and supports search, status filters, counts and pagination. Approve records the administrator and approval time; Reject or Mark pending immediately hides the review and clears approval metadata. Repeat approval keeps the original approval details. Visitor wording is preserved rather than edited by an administrator.

Archive hides a review and clears its approval. Restore returns it to Pending, requiring fresh approval before publication. Permanent deletion is restricted to archived reviews and requires a confirmation in the interface. Moderation routes require an authenticated administrator and return private, non-cached responses.

Phase 7 adds production configuration, security headers, readiness checks and release packaging. This local implementation sends no messages to visitors and requires no external review service.

## Production deployment — Phase 7

The source package is built with `./scripts/package-backend.ps1` from the repository root. It includes application code, local frontend assets, migrations, the offline reference importer, this guide and `production.env.example`. It excludes secrets, Composer dependencies, test files, local runtimes, cached configuration, database contents and uploaded images. The ZIP and SHA256 sidecar are saved under ignored `.local/releases/`. This is a source release; install production dependencies on the server before running it.

Use PHP 8.3+ with Composer and GD WebP, Fileinfo, PDO MySQL, cURL, DOM/XML, Mbstring and OpenSSL. Use a currently supported MySQL/MariaDB server with a dedicated database and user. Allow PHP uploads of at least 6 MB and requests of at least 8 MB. The local MariaDB 10.4 installation is only for development.

Set the domain’s document root to the unpacked application's `public/` directory, such as `/home/ACCOUNT/doctor-profile/public`. On cPanel, use this layout when Domains permits it; the main domain has a fixed document root and needs the alternative below. Keep the rest of Laravel outside the public document root. Never upload the whole application into a publicly served directory, or move `public/index.php` into the application root. Apache must honor the included `public/.htaccess`; Nginx must forward non-file requests to `public/index.php`, deny hidden files and execute only that PHP entry point. Configure the web server to redirect HTTP and alternate hostnames to the canonical HTTPS domain.

### cPanel setup

In cPanel, confirm the domain’s document root in **Domains**, choose PHP 8.3 or later using **MultiPHP Manager** or the provider’s PHP selector, and enable the required extensions. Use **MultiPHP INI Editor** for upload/request limits. Create a database and dedicated user in **MySQL Databases** or **Manage My Databases**, grant the user access to that database, and use the complete account-prefixed names in `.env`. Enable the domain’s certificate through **SSL/TLS Status** or the provider’s SSL controls before using secure session cookies.

Upload and unpack the source ZIP into `/home/ACCOUNT/doctor-profile`, outside `public_html`. If Terminal/SSH is available, run the installation commands below from that private directory using the PHP version selected for the website. Confirm `php -v`; the terminal and web PHP versions can differ. If Terminal/SSH or Composer is unavailable, the provider must run installation/migration commands or a dependency-inclusive package must be prepared for its PHP version. Do not create a public browser-based Artisan/installation endpoint.

For the main domain’s fixed `public_html` document root, first back up any existing website there. Copy only the **contents of** `doctor-profile/public/`, including hidden `.htaccess`, into `public_html`. Keep `.env`, `vendor`, `storage`, `config` and the rest of the application in the private `doctor-profile` directory. In the copied `public_html/index.php`, change the three private file paths to:

```php
__DIR__.'/../doctor-profile/storage/framework/maintenance.php'
__DIR__.'/../doctor-profile/vendor/autoload.php'
__DIR__.'/../doctor-profile/bootstrap/app.php'
```

Keep each path in its corresponding existing maintenance check, autoloader or bootstrap statement. After the `$app = require_once ...` statement and before `$app->handleRequest(...)`, add `$app->usePublicPath(__DIR__);`. This example assumes the two directories are siblings under the account home; adapt paths only after confirming the actual hosting layout. Copy updated public assets into `public_html` on later releases while preserving this adjusted entry point. Prefer a configurable document root pointing directly at `doctor-profile/public` for an addon domain/subdomain when the provider allows it.

cPanel documents the main-domain restriction in [Manage the Domain](https://docs.cpanel.net/cpanel/domains/domains/manage-the-domain/). These are preparation instructions; uploading files does not configure DNS, create the database or install the certificate automatically.

For a fresh installation, run from the unpacked application directory:

```sh
cp production.env.example .env
# Edit .env privately: real APP_NAME, canonical HTTPS APP_URL and database credentials.
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
composer check-platform-reqs --no-dev
php artisan key:generate --force
php artisan migrate --force --seed
php artisan website:import
php artisan admin:create
php artisan project:check-production
php artisan optimize
```

Generate an application key only for a fresh installation. Keep an existing key when migrating/restoring this installation. Use your own administrator username and strong password; optional contact email can be supplied with `--email`. Do not copy the local development account. The readiness command rejects the known development email. It performs read-only checks and never prints credentials. `--config-only` skips database/filesystem inspection and is not a full launch check. A passing command cannot verify hosting document-root isolation, TLS certificates, PHP-FPM limits, database vendor support status or backup restoration.

Grant the PHP process write access to `storage/` and `bootstrap/cache/` without making the application world-writable. Keep `.env` readable only by the account/PHP process. Session cookies require HTTPS; session and rate-limit data use the database by default. Set `TRUSTED_PROXIES` only to the actual load-balancer IPs/CIDRs if TLS terminates at a proxy. Direct HTTPS hosting leaves it empty. The production host must match `APP_URL`; additional hostnames should redirect at the web server. Application responses include anti-sniffing, same-origin framing, referrer and limited CSP headers. Production HTTPS responses enable HSTS for the current hostname.

### Moving existing content and recovery

To preserve all current edits, export the current application database using the hosting database tool or a matching MySQL dump utility. Copy `storage/app/public/uploads/` at the same time, and separately retain the application key in a secure backup. Store these backups privately, outside `public/` and outside the source release. On the destination, restore the database and matching uploads, retain the key, update connection/domain settings, create a real production administrator and remove the development account after confirming the new login. Run `php artisan migrate --force`, the production check and `php artisan optimize`. Do not seed/import again when restoring a complete database.

Before updates, take a matched database/uploads backup and retain the previous application release. Enter maintenance with `php artisan down`, replace source while preserving `.env` and `storage/`, install locked production dependencies, run `php artisan config:clear`, then `php artisan migrate --force`, `php artisan project:check-production`, `php artisan optimize` and `php artisan up`. Reload PHP-FPM/OPcache through the host as needed. Do not run tests, `migrate:fresh` or `db:wipe` against production.

If an update fails, keep maintenance active. Restore the prior source release and compatible database/uploads backup when a schema or content rollback is required, preserve the matching key, rebuild caches and verify before `php artisan up`. Do not blindly run migration rollback against live content. Schedule private database and upload backups through the hosting provider and prove restoration in a separate staging database.

After configuring the domain, check `/up`, all six public pages, images, mobile navigation, FAQ/gallery interactions, administrator login, settings/content visibility, upload/replacement and a review’s submission/approval/rejection cycle. Verify `/.env`, `/composer.json` and `/storage/logs/laravel.log` are inaccessible. Use HTTPS when testing secure cookies. Review server logs and remove any test content before launch.

These steps follow [Laravel 13 deployment guidance](https://laravel.com/framework/docs/13.x/deployment). The static `dist/` hosting configuration is separate and cannot run this PHP application.

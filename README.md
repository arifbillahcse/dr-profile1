# Dr. Farzana Rahman Shathi website

The active website is the Laravel application in `backend/`. It contains the doctor profile, public pages and administrator dashboard. See [backend setup and cPanel instructions](backend/README.md).

Profile information and portrait were checked against the [official Medinova profile](https://medinovamalibagh.com/doctor-detailes/74) on 14 November 2026 and the client-supplied chamber poster. The source manifest is `backend/database/seeders/data/farzana-shathi.json`.

After pulling an existing deployment, run `php artisan website:doctor` inside `backend/`, then `php artisan optimize`. This archives the previous doctor’s public content and preserves administrator accounts. Repeated runs keep later administrator edits.

## Historical design reference


A standalone HTML, CSS and JavaScript version of https://www.drrunaakhterdola.com/, captured on 13 November 2026. The original page layouts, responsive styles, photographs, fonts, icons and browser interactions are stored locally.

## Open locally

Open `dist/index.html` in a browser, or run:

```sh
node scripts/serve.mjs
```

Then visit http://localhost:4173. No installation or build step is required.

## Pages

- `dist/index.html`: homepage, hero sliders, qualifications, services, consultation, videos, experience and reviews.
- `dist/about-us.html`: biography and professional background.
- `dist/service-page.html`: medical services and specialist care information.
- `dist/faq-page.html`: expandable questions and answers.
- `dist/gallery-page.html`: photo gallery and image viewer.

## File organization

```text
dist/
  index.html
  about-us.html
  service-page.html
  faq-page.html
  gallery-page.html
  assets/
    css/       Stylesheets; site.css contains local adjustments
    js/        Browser scripts; site.js handles local review previews
    fonts/     Downloaded fonts and icon fonts
    images/    Photos, logos and decorative images
scripts/
  serve.mjs            Local preview server
  sync-reference.mjs   Optional refresh from the public reference
.openai/hosting.json   Existing hosting configuration
```

Downloaded dependencies keep their hashed filenames to avoid name collisions. The website needs only `dist` when uploaded to a static host.

## Interactions and external services

Navigation stays within the local pages. Mobile navigation, hero sliders, the introduction video dialog, gallery previews and FAQ accordions use the original frontend libraries, downloaded into the project. Phone and WhatsApp links retain the reference website's destinations. Embedded YouTube videos and Facebook content need internet access.

The original review endpoint and session tokens are excluded. Reviews in this static version are clearly identified as local previews and saved only in the visitor's browser. They are not sent to the clinic. Online review submission requires a separately configured backend.

Analytics belonging to the reference website are excluded. Medical and contact information is reproduced from the supplied reference.

## Refresh the reference

```sh
node scripts/sync-reference.mjs
```

This explicitly replaces the five page files and downloads their public dependencies into the organized asset folders. It preserves `dist/assets/js/site.js` and `dist/assets/css/site.css`. Refreshing overwrites edits made directly in the generated page files. Failed downloads are reported.

## Hosting

The existing `.openai/hosting.json` configuration serves `dist` as a static directory. No deployment is performed by the scripts above.

## Laravel website and backend — Phases 1–7

The new `backend/` application provides the Laravel 13 foundation, MySQL-compatible migrations, content models, safe initial settings and automated database tests. PHP 8.3+ is required. The database plan, setup instructions and phase boundaries are in [docs/backend-phase-1.md](docs/backend-phase-1.md).

On this Windows development machine:

```powershell
./scripts/local-database.ps1 start
./scripts/backend.ps1 status
./scripts/backend.ps1 test
./scripts/backend.ps1 serve
```

The admin login is at http://127.0.0.1:8000/admin/login. Phase 2 includes login/logout, password changes and the reference-style AdminLTE dashboard with real database counts. Initial local account details are saved privately in `.local/admin-access.txt`. Run `./scripts/backend.ps1 create-admin` in your terminal to create another administrator securely.

Phase 3 adds [Website settings](http://127.0.0.1:8000/admin/settings) for the doctor profile, qualifications, biography, introduction video, photographs, branding, contacts, social links and SEO. The [Media library](http://127.0.0.1:8000/admin/media) supports image uploads, alternative text, replacement, search and deletion of unused files.

Images accept JPG, PNG and WebP up to 5 MB and 4096 × 4096 pixels. They are decoded and normalized as WebP with generated filenames. Images used by settings or website content, including archived records, cannot be deleted. Replacing a library image updates every section referencing it; choosing a different image in Settings preserves the old library item.

Phase 4 enables content management for all 12 planned modules: sliders, featured statistics, services, education, awards, gallery, FAQs, YouTube videos, experience, chambers, consultation steps and specialist highlights. Open a section from the dashboard or sidebar to add/edit content, set display order, change visibility, search, archive and restore. Permanent deletion is limited to archived content and keeps media files for reuse. Chamber hours support multiple validated time slots per weekday and a selectable timezone.

Phase 5 connects the public website at http://127.0.0.1:8000/ to the saved settings and all 12 content modules while retaining the reference design. About, Services, FAQs, Gallery and the video archive use shared Blade navigation/footer. Admin edits appear immediately; only visible, unarchived content and approved reviews appear. Mobile navigation, sliders, FAQ accordions and gallery previews work with local assets.

The reference snapshot has been imported into the local database: 39 content items, one previously published review and managed photographs. For a new installation, run `php artisan website:import` from `backend/` after migrations, or `./scripts/backend.ps1 import` from the root. Repeated imports preserve edits, archives and deletions without duplicates; modules containing custom content are skipped. See [backend setup](backend/README.md) for details.

The static files in `dist/` remain a separate reference copy. To use the dynamic website, open the Laravel URL above; production needs PHP hosting with `backend/public/` as its document root. Phase 6 adds the homepage review form and the [review moderation queue](http://127.0.0.1:8000/admin/reviews). New reviews stay Pending until approved; administrators can approve, reject, mark pending, archive, restore and permanently delete archived reviews. Approval records the administrator and time. Forms include validation, publishing consent, request-forgery protection, a spam-trap field and five submission attempts per IP per hour. The queue supports search, status filters, totals and pagination. Private runtime files and database credentials are stored in ignored `.local/` and `backend/.env`.

## Production release — Phase 7

Production preparation includes HTTPS cookie settings, security response headers, canonical host restrictions, explicit trusted-proxy configuration and `php artisan project:check-production`. The development environment remains local.

Build a source release with `./scripts/package-backend.ps1`. The ZIP excludes credentials, local data, dependencies and uploaded photographs; install production Composer dependencies on the PHP host and transfer existing data separately if required. Follow the [deployment, backup and recovery instructions](backend/README.md#production-deployment--phase-7). Configure the domain to serve Laravel’s `public/` directory. The existing static Sites configuration continues to serve only `dist/`.

Live deployment requires the target PHP hosting account and canonical domain. No live server or DNS changes have been made.

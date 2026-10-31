# eskayvie follow-up system (native PHP 8.1+, MySQL, Tailwind)

1. Create a MySQL database and import `sql/schema.sql`. For an existing installation, apply `sql/monthly-targets.sql`, `sql/activity-record-plan.sql`, `sql/customer-list.sql`, `sql/prospect-list.sql`, `sql/daily-tasks.sql`, `sql/side-volume-target.sql`, and `sql/bank-content.sql`. If Bank Content is already installed, also apply `sql/bank-content-number.sql` to add the auto-incrementing `bc_items.no` field. If the monthly-target migration was already applied with a decimal income column, also apply `sql/monthly-target-income-integer.sql`.
2. Edit `config/config.php` (db credentials, `base_url`, `mail_from`; set `mail_debug` to false in production).
3. `composer install` (needed for XLSX export and Bank Content QR codes). On cPanel where allow_url_fopen is off:
   `php -d allow_url_fopen=On composer.phar install`
4. Point the web root at `public/` (locally: `php -S localhost:8000 -t public`).
   On cPanel keep `src/`, `config/`, `vendor/` above `public_html` and put the contents of `public/` inside it (adjust the `require` path if you move things).
   Alternatively, to keep the full project in `public_html/playbook/` with the entry point at `public_html/playbook/public/index.php`, place this repository's `.htaccess` in `public_html/playbook/`. It routes `/playbook/login.php` and other app URLs to `public/` without displaying that folder, and denies direct web access to the app's private directories. Apache `mod_rewrite` and `.htaccess` overrides must be enabled.
5. Set `base_url` in `config/config.php` to `https://v-system.eskayvie.co.id/playbook` for email confirmation links when using that URL.
6. Open `/signup.php`, register, confirm via the emailed link, log in with your username (your member code, e.g. ESK123456).
   Share `/signup.php?ref=ESK123456` to register someone under that sponsor.

## Public Bank Content
- Apply `sql/bank-content.sql` once to create `bc_items` and `bc_notifications`, add content roles, and add the starter folders.
- Open `/content.php` to browse files without signing in. File and folder metadata comes from `bc_items`; uploaded file contents are stored under `storage/content-files/` using generated server-side names.
- Public visitors and regular users can only browse Approved folders and items. Administrator and Supervisor accounts can see items in every status.
- Administrator and Supervisor accounts have a QR code column in Bank Content. Each folder and uploaded file has a QR code containing its own unique Bank Content URL; click the QR image to preview it and regenerate it when needed. Regular users and public visitors do not see this column. The QR code generator uses the configured `base_url`.
- Only Administrator accounts can create folders, upload multiple files or folders, and add links. Administrator and Supervisor accounts can rename items, change status, replace or attach file contents, and delete items. Members whose rank is Supervisor are granted management access automatically. To grant Administrator access, update that account explicitly, for example: `UPDATE users SET content_role = 'Administrator' WHERE username = 'YOUR_ADMIN_USERNAME';`
- Each file is limited to 25 MB. PHP's `upload_max_filesize` must permit that size, and `post_max_size` must be large enough for the total size of a multi-file or folder upload.
- The migration seeds the sample document metadata shown in the library design. Those sample rows do not include document binaries; an Administrator or Supervisor must use **Attach file contents** for each row before it can be previewed or downloaded.
- Supported uploads are PDF, DOC/DOCX, XLS/XLSX, PPT/PPTX, CSV, TXT, and GIF/JPEG/PNG/WebP images. PDF, image, text, CSV, XLSX, and DOCX files can be previewed. Supported files can be downloaded. Deleting a folder deletes its contents.
- The storage directory is not directly web-accessible. All files in this public library can be read and downloaded without login; do not upload private or confidential documents.

Structure: `src/` (Database, Auth, Users, Members, ContentLibrary, FollowUps, MonthlyTargets, ActivityPlans, Customers, Prospects, DailyTasks, SideVolumeTargets, Mailer), `views/components/` (layout, button, input, textarea, select, alert, toggle, downline-row, customer-row, prospect-row, daily-task-row, pagination), `public/` (pages, api.php, export.php, assets/app.js).

## Errors and diagnostics
- All PHP warnings, uncaught exceptions and database connection failures are written to `storage/logs/app.log` (passwords are never logged).
- Open `/diagnostics.php` (only while `'debug' => true`) to see PHP/extension checks, database connection status, table row counts and the last 100 log lines.
- If the DB connection fails, check host, name, user and password in `config/config.php` first; the log line shows which host/db/user were tried.

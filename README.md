# eskayvie follow-up system (native PHP 8.1+, MySQL, Tailwind)

1. Create a MySQL database and import `sql/schema.sql`. For an existing installation, apply `sql/monthly-targets.sql` and then `sql/activity-record-plan.sql`. If the monthly-target migration was already applied with a decimal income column, also apply `sql/monthly-target-income-integer.sql`.
2. Edit `config/config.php` (db credentials, `base_url`, `mail_from`; set `mail_debug` to false in production).
3. `composer install` (only needed for XLSX export). On cPanel where allow_url_fopen is off:
   `php -d allow_url_fopen=On composer.phar install`
4. Point the web root at `public/` (locally: `php -S localhost:8000 -t public`).
   On cPanel keep `src/`, `config/`, `vendor/` above `public_html` and put the contents of `public/` inside it (adjust the `require` path if you move things).
5. Open `/signup.php`, register, confirm via the emailed link, log in with your username (your member code, e.g. ESK123456).
   Share `/signup.php?ref=ESK123456` to register someone under that sponsor.

Structure: `src/` (Database, Auth, Users, Members, FollowUps, MonthlyTargets, ActivityPlans, Mailer), `views/components/` (layout, button, input, textarea, select, alert, toggle, downline-row, pagination), `public/` (pages, api.php, export.php, assets/app.js).

## Errors and diagnostics
- All PHP warnings, uncaught exceptions and database connection failures are written to `storage/logs/app.log` (passwords are never logged).
- Open `/diagnostics.php` (only while `'debug' => true`) to see PHP/extension checks, database connection status, table row counts and the last 100 log lines.
- If the DB connection fails, check host, name, user and password in `config/config.php` first; the log line shows which host/db/user were tried.

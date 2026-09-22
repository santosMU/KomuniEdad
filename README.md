# KomuniEdad

Senior citizen program and community activity management, following the Group 4 Phase 1 proposal.

**Stack:** PHP 8.2+, Laravel 12, Blade templates, Bootstrap 5.3.8, Supabase PostgreSQL and Supabase Auth. Laravel is the application at this repository root. The original Next.js scaffold has been replaced. Bootstrap is bundled locally; no frontend Node build is required.

## Start on this Windows machine

Dependencies and a local demo environment have already been prepared. In PowerShell:

```powershell
cd 'D:\02_Projects\06_ProjectsDev\KomuniEdad'
& 'C:\xampp\php\php.exe' artisan serve --port=8010
```

Open http://127.0.0.1:8010. Use **Preview as** to explore Senior, Coordinator, and Admin screens. Demo data is session-only and changes no real records. Set `KOMUNIEDAD_DEMO=true` only for local demonstrations. The switch is unavailable in live mode and demo mode is rejected outside local/testing environments.

## Fresh checkout

Install Composer and PHP 8.2+ (XAMPP PHP works). Enable PHP curl, mbstring, OpenSSL, fileinfo, XML, and zip extensions. Then:

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
```

Set `KOMUNIEDAD_DEMO=true` in `.env` to preview, then `php artisan serve`. If PHP is not on PATH, use `C:\xampp\php\php.exe`. `vendor/` is intentionally not committed.

## XAMPP Apache option

You may use XAMPP's PHP with `artisan serve` without starting Apache or MySQL. If using Apache, configure a local virtual host with its DocumentRoot pointing to this project's **public/** directory, enable `mod_rewrite`, and allow `.htaccess` overrides. Do not expose the repository root as a web directory. No XAMPP configuration has been changed automatically.

Supabase provides PostgreSQL and authentication as specified in the proposal. XAMPP MySQL/phpMyAdmin is not used by this version.

## Connect Supabase

Use a development Supabase project. In SQL Editor apply, in order:

1. `supabase/schema.sql`
2. Every `supabase/migrations/*.sql` file in filename order (once each)
3. `supabase/seed.sql`

Set `SUPABASE_URL`, `SUPABASE_ANON_KEY`, and `KOMUNIEDAD_DEMO=false` in `.env`. Keep `SESSION_DRIVER=file`, `SESSION_ENCRYPT=true`, and `CACHE_STORE=file`. Use an anon JWT API key from your project; never a service-role key. Run `php artisan config:clear` after changes.

Register senior accounts at `/register`. Supabase handles passwords and email confirmation. The profile trigger always creates the lowest-privilege role; client metadata cannot assign coordinator/admin access. Initial admin provisioning must be performed by the project owner through trusted Supabase administration. Use that administrator's Users screen to grant further staff roles. Existing Auth accounts created before the migration need profile backfilling; see `docs/setup.md`.

Optional verification is off by default. Set `public.settings.require_verification=true` through trusted database administration only after the client approves that policy. No identity documents are collected in this implementation.

For deployment set `APP_ENV=production`, `APP_DEBUG=false`, `KOMUNIEDAD_DEMO=false`, an HTTPS `APP_URL`, `SESSION_SECURE_COOKIE=true`, and a unique `APP_KEY`. Retain writable private storage for encrypted sessions. Live sessions require signing in again on token expiry; automatic token refresh is not implemented.

## Implemented workflows

- Senior: sign up/sign in/sign out, activity search and category filters, details, enrollment, waitlist, withdrawal, profile, participation history, announcements, eligible feedback.
- Coordinator: assigned activity creation/editing/status changes, roster, attendance, reasoned participant actions/walk-ins, activity announcements, summary reports.
- Administrator: organization-wide activity oversight, accounts/roles/status/verification, categories, announcements, reports, recent audit events.
- Database: nine domain entities plus a verification settings table, relational constraints, row-level security, transactional seat allocation, duplicate prevention, eligible waitlist promotion, audit logging of staff mutations.

## Validation

```powershell
php artisan test
php artisan view:cache
```

Optional database tests need Node only:

```powershell
npm install
npm run test:db
```

Database tests execute the real migrations in isolated embedded PostgreSQL (PGlite) with a test-only Supabase Auth substitute. They do not connect to or modify your hosted project. Laravel HTTP integration tests use mocked Supabase responses. Hosted Supabase integration and true multi-connection concurrency/load tests remain necessary before production.

See `docs/requirements.md` for requirement coverage and limitations, `docs/api/endpoints.md` for routes/RPCs, and `docs/database/ERD.md` for the relational model. Nothing has been deployed or pushed by this change.

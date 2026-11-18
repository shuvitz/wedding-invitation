# Deployment Guide

Target production architecture:

```
Nuxt 4 SSR (web/)  --------->  Netlify
                                  |
                                  | fetches api/public/* (server-to-server
                                  | during SSR, browser-to-server during
                                  | client-side navigation)
                                  v
Laravel API + MySQL + Media  --->  Shared hosting (e.g. Hostinger)
```

No VPS, Docker, Redis, queues, or object storage are required or assumed. This document is the canonical deployment reference — `README.md` and `web/README.md` link here rather than duplicating it.

## Part A — Laravel API (shared hosting)

### 1. Requirements

- **PHP ≥ 8.2** (Laravel 12 requirement — confirm your host's PHP version explicitly; don't assume the default matches. This project's own local dev once had exactly this mismatch, an XAMPP-bundled PHP 8.1 next to a required 8.2+).
- MySQL 8 or MariaDB, reachable from the app.
- Composer.
- Node.js + npm, for building the Vue admin SPA assets (`npm run build`) — build-time only, not required at runtime.

### 2. Install

```bash
composer install --no-dev --optimize-autoloader
npm install
npm run build
```

### 3. Environment configuration

Copy `.env.example` to `.env` and set, at minimum:

| Variable | Production value |
|---|---|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | The real, public HTTPS URL of this Laravel deployment (e.g. `https://api.yourdomain.com`). **Every generated media URL is built from this** (`config/filesystems.php`'s `public` disk) — a wrong or localhost value silently breaks every image on the public site. |
| `APP_KEY` | Generate with `php artisan key:generate` — do not reuse the local dev key. |
| `DB_*` | Your host's MySQL credentials. |
| `CORS_ALLOWED_ORIGINS` | Comma-separated list including the deployed Nuxt/Netlify URL (e.g. `https://your-site.netlify.app`) — see [CORS](#7-cors) below. |

### 4. Database

```bash
php artisan migrate --force
php artisan db:seed --class=DemoInvitationSeeder --force   # optional: the fictional demo invitation
```

Don't run the full `php artisan db:seed` in production: it also creates the `test@example.com` / `password` admin. Create the real admin instead:

```bash
php artisan tinker --execute="App\Models\User::factory()->admin()->create(['name' => 'Your Name', 'email' => 'you@example.com', 'password' => 'a-long-unique-password'])"
```

### 5. Storage — explicit, required deployment step

Media (`Storage::disk('public')`) is served through a symlink from `public/storage` to `storage/app/public`. This is **not** created automatically at boot or by any Composer/npm script — it must be run explicitly after every fresh deploy that doesn't preserve the existing symlink:

```bash
php artisan storage:link
```

Verify both `APP_URL` and storage are configured correctly before considering the deploy done:

```bash
php artisan deploy:check
```

This command (added in Phase B1, `app/Console/Commands/CheckDeploymentConfiguration.php`) checks that `APP_URL` isn't still a localhost address in production, that the `storage:link` symlink exists, and that `storage/app/public` is writable — it fails with a clear, specific message and a non-zero exit code if any of those are wrong, instead of the failure only surfacing later as broken images on the live site. It does not modify anything; it only checks. Run it by hand after each deploy — it is deliberately not wired into any boot process.

### 6. Cache/config optimization (optional, recommended)

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Re-run these after any subsequent `.env` or route change — a cached config silently ignores new `.env` values, including `APP_URL`.

### 7. CORS

`config/cors.php` (added in Phase B1) restricts cross-origin access to `api/public/*` only — the authenticated admin API is same-origin only (served by the Vue SPA that Laravel itself hosts) and deliberately isn't in this policy. Allowed origins come from `CORS_ALLOWED_ORIGINS` in `.env` (comma-separated), which defaults to the local Nuxt dev server (`http://localhost:3000`). Add the deployed Netlify URL once it exists:

```
CORS_ALLOWED_ORIGINS=https://your-site.netlify.app
```

This matters even though Nuxt mostly fetches server-to-server: after the page hydrates, client-side navigation between pages fetches `api/public/*` directly from the browser, which **is** subject to CORS.

### 8. Web server document root

Point the web server's document root at **`public/`**, not the repository root — this is the standard Laravel requirement and applies unchanged here (no custom routing/rewrites beyond Laravel's own `public/index.php` front controller and its existing `.htaccess`, if your host uses Apache).

### 9. Required production environment variables (summary)

`APP_ENV`, `APP_DEBUG`, `APP_URL`, `APP_KEY`, `DB_CONNECTION`/`DB_HOST`/`DB_PORT`/`DB_DATABASE`/`DB_USERNAME`/`DB_PASSWORD`, `CORS_ALLOWED_ORIGINS`.

### 10. Laravel API URL

The URL you set as `APP_URL` above is also the value to give Nuxt as `NUXT_PUBLIC_API_BASE_URL` (with `/api/public` appended) — see Part B.

---

## Part B — Nuxt public frontend (Netlify)

### 1. Repository / base directory

This repo is a monorepo — the Nuxt app lives in `web/`. In Netlify's site settings, set **Base directory: `web`** (already configured in `netlify.toml` at the repo root, so a fresh "Import from Git" picks this up automatically).

### 2. Build command

`npm run build` (also from `netlify.toml`). Nitro's `netlify` preset (`web/nuxt.config.ts`) produces the `.netlify/functions-internal/` output shape Netlify's build system auto-detects — no `publish` directory needs to be set manually.

### 3. Required environment variable

| Variable | Value |
|---|---|
| `NUXT_PUBLIC_API_BASE_URL` | The deployed Laravel API's public URL, with `/api/public` appended — e.g. `https://api.yourdomain.com/api/public` |
| `NUXT_PUBLIC_LOCALE` | Optional. Defaults to `id` in this project; keep it equal to Laravel's `APP_LOCALE`. |

Set this under Netlify's **Site settings → Environment variables**, not committed to the repo. As of Phase B1, **the build fails immediately and clearly** if this is missing (Netlify's build environment sets `NODE_ENV=production` for every build, which trips the check in `web/nuxt.config.ts`) — this replaces the previous silent fallback to `http://localhost:8000/api/public`, which would have deployed a broken site with no build-time warning.

### 4. Nitro preset

Already set to `'netlify'` in `web/nuxt.config.ts` (changed from `'node-server'` in Phase A specifically for this target). No further configuration needed unless the deployment target changes — see `web/README.md`'s "If Hostinger is revisited later" note.

### 5. Deployment verification

After the first deploy, confirm SSR is actually happening (not a client-only shell) the same way prior phases verified it locally — `curl` the deployed URL with no JS execution and confirm real page content is present in the raw HTML response, not just an empty `<div id="__nuxt">`:

```bash
curl -s https://your-site.netlify.app/ | grep -o '<h1[^<]*'
```

Also check:
- A known published page's slug returns real content.
- A draft/nonexistent slug returns a proper 404, not a stack trace or blank page.
- Images render (confirms `NUXT_PUBLIC_API_BASE_URL` and Laravel's `APP_URL` are both correct — see troubleshooting below).

### 6. Connecting the Laravel API

Set `NUXT_PUBLIC_API_BASE_URL` (step 3) to the deployed Laravel domain, and add that same Netlify domain to Laravel's `CORS_ALLOWED_ORIGINS` (Part A, step 7) — the two settings point at each other and both are required for the site to work end-to-end.

### 7. Troubleshooting API/media URL problems

| Symptom | Likely cause | Fix |
|---|---|---|
| Netlify build fails with "NUXT_PUBLIC_API_BASE_URL is required in production" | Env var not set in Netlify | Add it under Site settings → Environment variables, redeploy |
| Site builds and deploys, but every page is empty/errors | `NUXT_PUBLIC_API_BASE_URL` set but wrong (typo, wrong domain, missing `/api/public`) | Verify the URL directly: `curl https://<value>/settings` should return JSON |
| Pages render but every image is broken | Laravel's `APP_URL` doesn't match its real public domain, or `storage:link` was never run | Check `php artisan deploy:check` on the Laravel side (Part A, step 5) |
| Browser console shows a CORS error on client-side navigation, first page load was fine | Netlify's domain isn't in Laravel's `CORS_ALLOWED_ORIGINS` | Add it (Part A, step 7) and redeploy Laravel (or just clear config cache if `.env` already had it) |
| Everything works locally, breaks only on Netlify | Something above was verified locally against `localhost` but never against the real deployed domains — re-check every URL-shaped env var against its production value | — |

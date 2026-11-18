# Denji CMS Starter

> Wedding invitation site built on [denji-skeleton](https://github.com/shuvitz/denji-skeleton) **v0.2.0**. Skeleton fixes are pulled in with `git fetch skeleton` + `git cherry-pick <hash>`.

A generic, self-hosted CMS starter for company-profile, landing-page, and small-business websites. Built on Laravel + Vue (admin) + Nuxt (public SSR frontend).

This is a **starter/skeleton repository** — it ships with the CMS itself (users, roles, settings, navigation, media, pages, content blocks) but no business-specific content. Every real site (a restaurant, an event organizer, a portfolio, a company profile) is built entirely through the admin UI, without touching application code.

## What's included

- **Authentication & roles** — session-based login (Sanctum), two roles: admin (full access) and editor (can create/edit draft content, cannot publish or delete)
- **User management** — admin-only CRUD, with safeguards against removing the last active admin
- **Settings** — site identity (name/logo/favicon), contact info, social links (including WhatsApp), and default SEO metadata
- **Navigation** — a flat, ordered list of menu links
- **Media library** — image uploads (JPEG/PNG/WebP/GIF), stored via Laravel's Filesystem abstraction, with reference protection (an image in use by a page or Settings can't be deleted)
- **Pages** — draft/published workflow, one page can be designated the homepage, structured JSON content made of a fixed set of blocks:
  - `hero`, `rich_text`, `image`, `cta`, `menu` (e.g. a priced item list), `gallery`, `schedule` (dated events with time, venue and a maps link, plus an optional countdown to the first event; dates render in `APP_LOCALE` / `NUXT_PUBLIC_LOCALE`), `form` (visitor form such as an RSVP or contact form — rendered by Nuxt only, not the Blade baseline), `couple` (the two people being married: name, parents, photo and Instagram username), `gift` (digital envelope: bank or e-wallet accounts with a copy button), `cover` (full-screen opener that greets the guest from `?to=Guest+Name`; one per page)
- **Form submissions** — visitors submit a published page's `form` block; Laravel validates against the field list stored on the page (text, long text, number, dropdown), rate-limits per IP (5/min, 50/day) and drops honeypot bots. Admins (not editors — it's personal data) view, delete and export them as CSV from the page editor's **Submissions** button. Deleting a page deletes its submissions.
- **Public API** (`/api/public/*`) — unauthenticated, read-only except the one form-submission `POST`, exposes only published content and never internal fields (no user info, no draft content, no admin IDs)
- **Nuxt public frontend** — real server-side rendering (not a client-only SPA), SEO metadata (title/description/canonical/OpenGraph/Twitter), image dimensions + lazy loading

## Architecture

```
Laravel (API + MySQL)
    |
    +-- Vue 3 admin SPA        /admin/*     (resources/js/)
    |
    +-- Blade public renderer  /{slug}      (resources/views/public/ — compatibility baseline)
    |
    +-- Nuxt public frontend   web/         (primary public frontend, SSR, deploys separately)
```

- **Laravel** owns all CMS logic: authentication, authorization, validation, content, media storage. It's the only thing that talks to MySQL.
- **Vue admin SPA** is served by Laravel itself at `/admin/*` — content editors work here.
- **Nuxt** (in `web/`) is a separate Node application and the primary public-facing frontend. It only ever calls Laravel's public API (`GET /api/public/*`) — it never touches the database directly, has no authentication, and holds no business logic. See [`web/README.md`](web/README.md).
- A Blade-based public renderer also exists (`resources/views/public/`) as a zero-JS compatibility baseline alongside Nuxt. Both can render the same published content.

Planned deployment target: Laravel on PHP shared hosting, Nuxt on Netlify. See [`DEPLOYMENT.md`](DEPLOYMENT.md).

## Requirements

- PHP 8.2+
- Composer
- Node.js 20+ and npm
- MySQL 8 / MariaDB (SQLite also works for quick local testing)

## Installation

```bash
git clone <this-repo> denji-cms
cd denji-cms

composer install
npm install

cp .env.example .env
php artisan key:generate
```

### Database

Set `DB_*` in `.env` to a real MySQL database (or leave `DB_CONNECTION=sqlite` and create `database/database.sqlite` for quick local testing), then:

```bash
php artisan migrate
php artisan db:seed
```

Seeding creates an admin account so you can log in for the first time, plus a fictional demo invitation (Raka & Sekar) published as the homepage, with placeholder photos (gradient + initials) added to the Media library. To add or refresh only the demo, e.g. on a server, run `php artisan db:seed --class=DemoInvitationSeeder`; it updates the same page instead of duplicating it. Admin login:

```
email:    test@example.com
password: password
```

**Change this password immediately after first login** — it's a known default, not a secret.

The admin's **Guests** page (admin only) keeps the guest list (added one by one or imported from a CSV of name, phone, notes; exported back to CSV with each guest's link and RSVP) and copies each guest's personal link (`FRONTEND_URL/?to=Name`) or opens WhatsApp with the invitation message ready to send (the text is editable on the same page, with `{nama}` and `{link}` placeholders). The cover block greets them by that name.

Migrations + seed produce one admin user plus the demo invitation above (one page and six photos in Media); Navigation is empty. Everything else is created through the admin UI.

### Environment variables

See `.env.example` for the full list with inline explanations. The ones specific to this CMS (beyond standard Laravel `.env` values):

| Variable | Purpose |
|---|---|
| `APP_URL` | Must be the real public Laravel domain in production — every generated media URL is built from it |
| `CORS_ALLOWED_ORIGINS` | Comma-separated origins allowed to call the public API cross-origin (the deployed Nuxt/Netlify URL) — see `config/cors.php` |
| `MEDIA_DISK` | Which Filesystem disk media uploads use — defaults to `public` |

## Running the app locally

Three independent processes, each on its own port:

```bash
# Terminal 1 — Laravel (API + admin SPA host + Blade public baseline)
php artisan serve            # http://localhost:8000

# Terminal 2 — Admin SPA asset dev server (Vite HMR)
npm run dev

# Terminal 3 — Nuxt public frontend
cd web && npm run dev        # http://localhost:3000
```

Or run Laravel + Vite + queue worker together:

```bash
composer dev
```

The admin SPA is reached through Laravel (`http://localhost:8000/login`, `/admin/*`) — Vite in terminal 2 only serves its assets. Nuxt in terminal 3 is a fully separate server that fetches from Laravel's `/api/public/*` endpoints; it never touches MySQL directly.

## Creating your first page

1. Log in to the admin SPA (`/login`) with the seeded admin account.
2. Configure **Settings** (site name, contact, social links) and **Navigation**.
3. Create a **Page**, add content blocks (hero, rich text, image, CTA, menu, gallery, schedule, form, couple, gift, cover), save as draft.
4. As an admin, publish the page. Optionally mark it as the homepage (only one page can be the homepage at a time).
5. View it on the Nuxt frontend at `http://localhost:3000/{slug}` (or `/` if it's the homepage), or on the Blade baseline at `http://localhost:8000/{slug}`.

## Testing

```bash
php artisan test
```

## Production build

```bash
# Admin SPA assets
npm run build

# Nuxt public frontend
cd web
npm run build
```

## Deployment

See [`DEPLOYMENT.md`](DEPLOYMENT.md) for the full production deployment guide (Laravel on shared hosting + Nuxt on Netlify), including required environment variables, the `storage:link`/`deploy:check` steps, CORS configuration, and troubleshooting.

## Project structure

- `resources/js/` — Vue 3 admin SPA (pages, components, Pinia stores)
- `app/Http/Controllers/Api/*` — admin JSON API (Sanctum session-authenticated)
- `app/Http/Controllers/Api/Public/*` — public, unauthenticated JSON API (`/api/public/*`), consumed by Nuxt
- `app/Http/Controllers/PublicPageController.php` + `resources/views/public/*` — Blade public renderer (compatibility baseline)
- `app/Services/BlockRegistry.php` — single source of truth for what a page's content blocks can be
- `web/` — Nuxt public frontend, a separate Node application — see [`web/README.md`](web/README.md)

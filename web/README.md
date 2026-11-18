# Denji CMS — Public Frontend (Nuxt)

Server-rendered public website for Denji CMS. This is a **separate Node application** living inside the main `denji-skeleton` repository — its own `package.json`, its own `node_modules`, its own dev/build process — independent of the root Laravel + Vue admin SPA setup.

## Architecture

```
Denji CMS
    |
Laravel API + MySQL  (repo root)
    |
    +-- Vue Admin SPA        /admin/*    (existing, unchanged)
    |
    +-- Nuxt Public Frontend /, /[slug]  (this directory)
```

- Laravel remains the CMS: content management, validation, publishing rules, authorization, media storage, persistence.
- This Nuxt app is *only* responsible for public rendering, layout, SEO, and routing. It holds no business logic and never talks to MySQL — it fetches read-only, unauthenticated JSON from Laravel's `GET /api/public/*` endpoints. The one exception is a `form` block, which `POST`s a visitor's submission from the browser to `/api/public/pages/{slug}/forms/{form_key}`.
- The existing Blade public renderer (`resources/views/public/*` at the repo root) is untouched and still works independently at the same Laravel port — this app doesn't replace it, both can render the same content today.

## Setup

```bash
npm install
cp .env.example .env
```

Edit `.env` if your Laravel API isn't at the default local address:

```
NUXT_PUBLIC_API_BASE_URL=http://localhost:8000/api/public
```

## Development

Requires Laravel already running (`php artisan serve`, from the repo root) so there's an API to fetch from.

```bash
npm run dev
```

Visit `http://localhost:3000`.

## Production build

```bash
npm run build
```

The Nitro preset is set to `netlify` in `nuxt.config.ts` — the current deployment target is Netlify, not a plain Node host. This produces `.netlify/functions-internal/` — an SSR function in the exact shape Netlify's build system auto-detects, not a long-running Node process.

## Netlify deployment

- **Base directory:** `web` (this app lives in a subdirectory of the main repo — see `netlify.toml` at the repo root)
- **Build command:** `npm run build`
- **Required env var:** `NUXT_PUBLIC_API_BASE_URL` (pointed at the deployed Laravel app's `/api/public` path) — set in Netlify's site environment variables, not committed. **The build fails immediately if this is missing** (see "Production requires `NUXT_PUBLIC_API_BASE_URL`" below) rather than silently deploying a broken site.
- No `publish` directory needs to be set manually — Netlify recognizes the `.netlify/` output shape Nitro's `netlify` preset produces
- No Docker, no VPS-specific infrastructure, no Redis, no background workers

Full step-by-step deployment/verification/troubleshooting guide, including how this connects to the Laravel side (CORS, `APP_URL`): [`../DEPLOYMENT.md`](../DEPLOYMENT.md).

### Production requires `NUXT_PUBLIC_API_BASE_URL`

`nuxt.config.ts` throws a build-time error if `NUXT_PUBLIC_API_BASE_URL` is unset while `NODE_ENV=production` (which Netlify's build environment always sets). This replaced a previous silent fallback to `http://localhost:8000/api/public` in every environment — harmless in local dev, but a real risk in production: a forgotten env var used to mean a deployed site trying to fetch a `localhost` address from Netlify's servers, failing on every page with no clear signal why. The `localhost` fallback still applies in development (`npm run dev`, where `NODE_ENV=development`), so local setup is unaffected.

### Local dev is unaffected

`npm run dev` uses Vite's dev server regardless of the configured Nitro preset — the preset only shapes `npm run build`'s output. Nothing about local development changed when the target moved from Hostinger to Netlify.

### If Hostinger is revisited later

Swap `nitro.preset` back to `'node-server'` in `nuxt.config.ts` and follow the original start command (`node .output/server/index.mjs`) — the app code itself (pages, components, composables) doesn't change between targets, only the build output shape.

## What this app does *not* do

- No authentication (the public site has none — admin auth lives entirely in Laravel/the Vue SPA).
- No direct database access.
- No file uploads or media storage (Media management stays in the Laravel admin).
- No duplication of Laravel's block-content validation — Nuxt only renders content Laravel has already validated and published.

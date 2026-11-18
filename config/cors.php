<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Explicit, reviewed CORS policy — this app previously had no published
    | config/cors.php, so Laravel silently fell back to its vendor default
    | (wildcard "*" origin on every api/* path). That was never a decided
    | policy, just an accident of an unpublished default; this file
    | replaces it with a scoped one.
    |
    | Only the unauthenticated public API needs cross-origin access at all:
    | Nuxt fetches it both server-to-server during SSR (not subject to
    | CORS) and directly from the browser during client-side navigation
    | after the page hydrates (which is subject to CORS). The authenticated
    | admin API (api/users, api/settings, api/pages, ...) is only ever
    | called same-origin, by the Vue admin SPA that Laravel itself serves —
    | it does not need a cross-origin allowance and is deliberately left
    | out of `paths` below.
    |
    */

    'paths' => ['api/public/*'],

    // POST is for the one public write, form submissions
    // (POST api/public/pages/{slug}/forms/{formKey}); every other public
    // route is GET-only regardless.
    'allowed_methods' => ['GET', 'HEAD', 'POST', 'OPTIONS'],

    /*
    | The production Nuxt/Netlify origin doesn't exist yet at the time this
    | config is written, so it must not be hardcoded. Set CORS_ALLOWED_ORIGINS
    | in .env to a comma-separated list of origins once it does (e.g. the
    | production Netlify URL, plus any deploy-preview domain if needed) —
    | see .env.example and DEPLOYMENT.md.
    */
    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('CORS_ALLOWED_ORIGINS', 'http://localhost:3000'))
    ))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    /*
    | The public API is read-only and unauthenticated by design — Nuxt
    | never sends cookies/credentials to it, so this stays false. Do not
    | flip this to true without also reviewing `allowed_origins` above,
    | since a wildcard origin plus credentials is a real vulnerability
    | combination (browsers already refuse that combination, but the
    | config itself should never invite it).
    */
    'supports_credentials' => false,

];

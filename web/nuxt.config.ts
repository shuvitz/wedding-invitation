import tailwindcss from '@tailwindcss/vite';

// Nuxt/Nitro set NODE_ENV=production for `nuxt build` (and therefore every
// Netlify build) and NODE_ENV=development for `nuxt dev` — this is what
// lets a missing env var fail the production build loudly instead of
// silently shipping a site that calls a localhost address from Netlify's
// servers. See ../DEPLOYMENT.md's "Troubleshooting" section.
const isProduction = process.env.NODE_ENV === 'production';
const apiBaseUrl = process.env.NUXT_PUBLIC_API_BASE_URL;

if (isProduction && !apiBaseUrl) {
    throw new Error(
        'NUXT_PUBLIC_API_BASE_URL is required in production builds (e.g. ' +
            'https://your-laravel-domain.com/api/public). Set it in Netlify\'s ' +
            'site environment variables — see web/README.md.',
    );
}

// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },

  // Explicit — this is the whole point of Nuxt in this architecture:
  // real server-rendered HTML, not a client-only SPA. Nuxt SSRs by
  // default, but this is deliberately not left implicit.
  ssr: true,

  // Netlify is the current deployment target — `node-server` (a plain
  // long-running Node process) doesn't fit Netlify's function-based
  // deploy model. This only changes the `npm run build` output shape;
  // local dev (`npm run dev`) is unaffected either way. See
  // ../DEPLOYMENT.md if a different host is used instead.
  nitro: {
    preset: 'netlify',
  },

  css: ['~/assets/css/app.css'],

  // Theme fonts (see assets/css/app.css). `display=swap` shows fallback
  // text immediately instead of blank text while the fonts load.
  app: {
    head: {
      link: [
        { rel: 'preconnect', href: 'https://fonts.googleapis.com' },
        { rel: 'preconnect', href: 'https://fonts.gstatic.com', crossorigin: '' },
        { rel: 'stylesheet', href: 'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Great+Vibes&display=swap' },
      ],
    },
  },

  vite: {
    plugins: [tailwindcss()],
  },

  runtimeConfig: {
    public: {
      // Server-to-server base URL for Laravel's public JSON API. Never
      // hardcoded — see .env.example. The localhost fallback only applies
      // in development (guarded above); production builds require the
      // real env var and fail otherwise.
      apiBaseUrl: apiBaseUrl || 'http://localhost:8000/api/public',
      // BCP 47 locale for formatting dates (e.g. `id` → "Sabtu, 14 Juni
      // 2026"). Fixed rather than the browser's, so SSR and hydration
      // render the same string. Override with NUXT_PUBLIC_LOCALE.
      locale: 'en',
    },
  },
});

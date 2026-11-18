/**
 * A `$fetch` instance bound to Laravel's public API base URL
 * (NUXT_PUBLIC_API_BASE_URL, see nuxt.config.ts / .env.example). Nuxt
 * never talks to MySQL directly and never duplicates Laravel's business
 * logic — every call here hits an already-validated, already-authorized
 * read-only endpoint.
 */
export function useApi() {
    const config = useRuntimeConfig();

    return $fetch.create({
        baseURL: config.public.apiBaseUrl,
    });
}

import type { ApiEnvelope, NavigationItem, SiteSettings } from '~/types/cms';

/**
 * Site-wide data (settings + navigation) needed by every page's layout.
 * Uses useAsyncData with a fixed key so Nuxt dedupes this to one fetch
 * per request during SSR, and reuses the payload on client hydration
 * instead of re-fetching.
 */
export function useSiteSettings() {
    const api = useApi();

    const settings = useAsyncData('site-settings', () =>
        api<ApiEnvelope<SiteSettings>>('/settings').then((res) => res.data)
    );

    const navigation = useAsyncData('site-navigation', () =>
        api<ApiEnvelope<NavigationItem[]>>('/navigation').then((res) => res.data)
    );

    return {
        settings: settings.data,
        navigation: navigation.data,
    };
}

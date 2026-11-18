import type { Ref } from 'vue';
import type { CmsPage } from '~/types/cms';

/**
 * The fallback hierarchy, exactly as specified:
 *   title:       page.meta_title -> page.title -> site name
 *   description: page.meta_description -> settings.seo.meta_description -> omitted
 *
 * Shared by both the homepage and dynamic [slug] pages so the two never
 * drift — there is deliberately no page-specific SEO content hardcoded
 * anywhere in this composable.
 */
export function usePageSeo(
    page: Ref<CmsPage | null | undefined>,
    siteName: Ref<string | null | undefined>,
    siteDescription: Ref<string | null | undefined>
) {
    const title = computed(() => page.value?.meta_title || page.value?.title || siteName.value || undefined);
    const description = computed(() => page.value?.meta_description || siteDescription.value || undefined);

    useSeoMeta({
        title: () => title.value,
        description: () => description.value,
        ogTitle: () => title.value,
        ogDescription: () => description.value,
        ogType: 'website',
        twitterCard: 'summary_large_image',
        twitterTitle: () => title.value,
        twitterDescription: () => description.value,
    });

    const route = useRoute();
    const requestUrl = useRequestURL();

    useHead({
        link: [
            {
                rel: 'canonical',
                href: () => new URL(route.path, requestUrl.origin).toString(),
            },
        ],
    });
}

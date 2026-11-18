<template>
    <BlockRenderer v-if="page" :blocks="page.content.blocks" :page-slug="page.slug" />
</template>

<script setup lang="ts">
import type { ApiEnvelope, CmsPage } from '~/types/cms';

const api = useApi();
const { settings } = useSiteSettings();

// Laravel resolves the designated homepage (Page::is_home) — see
// PageService::setHome / GET /api/public/home. A 404 here (no page
// currently designated as home, or none published) surfaces Nuxt's
// standard error page rather than a blank/broken screen.
//
// See [slug].vue for why createError() must be thrown here rather than
// inside the useAsyncData callback.
const { data: page, error } = await useAsyncData('home-page', () =>
    api<ApiEnvelope<CmsPage>>('/home').then((res) => res.data)
);

if (error.value) {
    const statusCode = (error.value as any).statusCode ?? 500;
    throw createError({
        statusCode,
        statusMessage: statusCode === 404 ? 'No homepage is configured yet.' : 'Something went wrong',
        fatal: true,
    });
}

usePageSeo(
    page,
    computed(() => settings.value?.site_identity.name),
    computed(() => settings.value?.seo.meta_description)
);
</script>

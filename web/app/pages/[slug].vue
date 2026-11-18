<template>
    <BlockRenderer v-if="page" :blocks="page.content.blocks" :page-slug="page.slug" />
</template>

<script setup lang="ts">
import type { ApiEnvelope, CmsPage } from '~/types/cms';

const route = useRoute();
const api = useApi();
const { settings } = useSiteSettings();

const slug = route.params.slug as string;

// Laravel only ever returns a published page for this endpoint — a draft
// or a genuinely missing slug are both a plain 404 from the API (see
// Api/Public/PageController::show), so there is nothing here that could
// leak draft content by accident.
//
// createError() must be thrown here, in the component body — throwing it
// *inside* the useAsyncData callback would just be caught by
// useAsyncData's own error handling and never reach Nuxt's page-level
// error/404 response.
const { data: page, error } = await useAsyncData(`page-${slug}`, () =>
    api<ApiEnvelope<CmsPage>>(`/pages/${slug}`).then((res) => res.data)
);

if (error.value) {
    const statusCode = (error.value as any).statusCode ?? 500;
    throw createError({
        statusCode,
        statusMessage: statusCode === 404 ? 'Page not found' : 'Something went wrong',
        fatal: true,
    });
}

usePageSeo(
    page,
    computed(() => settings.value?.site_identity.name),
    computed(() => settings.value?.seo.meta_description)
);
</script>

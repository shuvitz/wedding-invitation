<template>
    <div class="flex min-h-screen flex-col bg-background text-foreground">
        <header class="border-b border-border">
            <div class="mx-auto flex max-w-4xl items-center justify-between gap-4 px-6 py-4">
                <NuxtLink to="/" class="flex items-center gap-2 text-lg font-bold">
                    <img v-if="settings?.site_identity.logo" :src="settings.site_identity.logo.url" :alt="siteName" class="h-8 w-auto">
                    <span v-else>{{ siteName }}</span>
                </NuxtLink>
                <nav v-if="navigation && navigation.length > 0" class="flex flex-wrap gap-5 text-sm font-medium">
                    <a
                        v-for="item in navigation"
                        :key="item.label"
                        :href="item.url"
                        class="text-muted-foreground transition hover:text-foreground"
                    >
                        {{ item.label }}
                    </a>
                </nav>
            </div>
        </header>

        <main class="mx-auto w-full max-w-4xl flex-1 px-6 py-10">
            <slot />
        </main>

        <footer class="border-t border-border">
            <div class="mx-auto max-w-4xl px-6 py-8 text-sm text-muted-foreground">
                <p>&copy; {{ new Date().getFullYear() }} {{ siteName }}</p>
                <p v-if="settings?.contact.email || settings?.contact.phone" class="mt-1">
                    <span v-if="settings?.contact.email">{{ settings.contact.email }}</span>
                    <span v-if="settings?.contact.email && settings?.contact.phone"> &middot; </span>
                    <span v-if="settings?.contact.phone">{{ settings.contact.phone }}</span>
                </p>
                <p v-if="socialLinks.length > 0" class="mt-2 flex gap-4">
                    <span v-for="social in socialLinks" :key="social.platform">{{ social.platform }}: {{ social.value }}</span>
                </p>
            </div>
        </footer>
    </div>
</template>

<script setup lang="ts">
const { settings, navigation } = useSiteSettings();

const siteName = computed(() => settings.value?.site_identity.name || 'Denji CMS');

const socialLinks = computed(() => {
    const social = settings.value?.social;
    if (!social) return [];

    return (['instagram', 'facebook', 'tiktok', 'linkedin'] as const)
        .filter((platform) => social[platform])
        .map((platform) => ({ platform: platform.charAt(0).toUpperCase() + platform.slice(1), value: social[platform] }));
});

const locale = useRuntimeConfig().public.locale;

useHead(() => ({
    htmlAttrs: { lang: locale },
    link: settings.value?.site_identity.favicon
        ? [{ rel: 'icon', href: settings.value.site_identity.favicon.url }]
        : [],
}));
</script>

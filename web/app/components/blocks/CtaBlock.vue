<!--
    `url` was already validated by Laravel (BlockRegistry) to only ever be
    a relative path or an http(s) URL — never javascript:/data:/etc — so
    it's safe to use directly as an href here.
-->
<template>
    <section class="space-y-3 rounded-lg border border-border bg-card p-8 text-center">
        <h2 class="text-2xl font-semibold text-foreground">{{ data.title }}</h2>
        <p v-if="data.description" class="text-muted-foreground">{{ data.description }}</p>
        <a
            v-if="data.url && data.button_label"
            :href="data.url"
            class="inline-flex items-center justify-center rounded-md bg-primary px-6 py-2 text-sm font-medium text-primary-foreground shadow transition hover:bg-primary/90"
        >
            {{ data.button_label }}
        </a>
    </section>
</template>

<script setup lang="ts">
import type { CtaBlockData } from '~/types/cms';

defineProps<{ data: CtaBlockData }>();
</script>

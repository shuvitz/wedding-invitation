<template>
    <section class="space-y-4 py-8 text-center">
        <img
            v-if="data.image_url"
            :src="data.image_url"
            :alt="data.title ?? ''"
            :width="data.width ?? undefined"
            :height="data.height ?? undefined"
            class="mx-auto max-h-96 w-full rounded-lg object-cover"
        >
        <h1 class="text-4xl font-bold tracking-tight text-foreground">{{ data.title }}</h1>
        <p v-if="data.subtitle" class="text-lg text-muted-foreground">{{ data.subtitle }}</p>
    </section>
</template>

<script setup lang="ts">
import type { HeroBlockData } from '~/types/cms';

defineProps<{ data: HeroBlockData }>();
</script>

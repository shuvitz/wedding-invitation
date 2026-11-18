<template>
    <section class="space-y-6 py-4">
        <h2 v-if="data.title" class="text-center text-2xl font-semibold text-foreground">{{ data.title }}</h2>

        <div v-if="data.items?.length" class="grid grid-cols-2 gap-3 sm:grid-cols-3">
            <img
                v-for="(item, index) in data.items.filter((i) => i.url)"
                :key="index"
                :src="item.url ?? undefined"
                :alt="item.alt_text ?? ''"
                :width="item.width ?? undefined"
                :height="item.height ?? undefined"
                loading="lazy"
                class="aspect-square w-full rounded-lg object-cover"
            >
        </div>
    </section>
</template>

<script setup lang="ts">
import type { GalleryBlockData } from '~/types/cms';

defineProps<{ data: GalleryBlockData }>();
</script>

<template>
    <section class="space-y-6 py-4">
        <h2 v-if="data.title" class="text-center text-2xl font-semibold text-foreground">{{ data.title }}</h2>

        <div v-if="data.items?.length" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div v-for="(item, index) in data.items" :key="index" class="flex gap-4 rounded-lg border border-border bg-card p-4">
                <img
                    v-if="item.image_url"
                    :src="item.image_url"
                    :alt="item.name ?? ''"
                    :width="item.width ?? undefined"
                    :height="item.height ?? undefined"
                    loading="lazy"
                    class="h-20 w-20 shrink-0 rounded-md object-cover"
                >
                <div class="flex-1 space-y-1">
                    <div class="flex items-baseline justify-between gap-2">
                        <h3 class="font-semibold text-foreground">{{ item.name }}</h3>
                        <span class="whitespace-nowrap font-semibold text-foreground">{{ item.price }}</span>
                    </div>
                    <p v-if="item.description" class="text-sm text-muted-foreground">{{ item.description }}</p>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import type { MenuBlockData } from '~/types/cms';

defineProps<{ data: MenuBlockData }>();
</script>

<!--
    `instagram` was already validated by Laravel (BlockRegistry) to a bare
    username of [A-Za-z0-9._], so it's safe to build the profile link here.
-->
<template>
    <section class="space-y-6 py-4">
        <h2 v-if="data.title" class="text-center text-2xl font-semibold text-foreground">{{ data.title }}</h2>

        <div v-if="data.items?.length" class="grid grid-cols-1 items-center gap-6 sm:grid-cols-[1fr_auto_1fr]">
            <template v-for="(item, index) in data.items" :key="index">
                <p v-if="index > 0" class="text-center font-script text-5xl text-primary" aria-hidden="true">&amp;</p>
                <div class="space-y-3 text-center">
                    <img
                        v-if="item.image_url"
                        :src="item.image_url"
                        :alt="item.name ?? ''"
                        :width="item.width ?? undefined"
                        :height="item.height ?? undefined"
                        loading="lazy"
                        class="mx-auto h-40 w-40 rounded-full object-cover"
                    >
                    <h3 class="font-script text-4xl font-normal text-foreground">{{ item.name }}</h3>
                    <p v-if="item.parents" class="text-sm text-muted-foreground">{{ item.parents }}</p>
                    <a
                        v-if="item.instagram"
                        :href="`https://instagram.com/${item.instagram}`"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-block text-sm font-medium text-primary hover:underline"
                    >@{{ item.instagram }}</a>
                </div>
            </template>
        </div>
    </section>
</template>

<script setup lang="ts">
import type { CoupleBlockData } from '~/types/cms';

defineProps<{ data: CoupleBlockData }>();
</script>

<template>
    <section v-if="data.url" class="py-4">
        <img
            :src="data.url"
            :alt="data.alt_text ?? ''"
            :width="data.width ?? undefined"
            :height="data.height ?? undefined"
            loading="lazy"
            class="w-full rounded-lg object-cover"
        >
    </section>
</template>

<script setup lang="ts">
import type { ImageBlockData } from '~/types/cms';

defineProps<{ data: ImageBlockData }>();
</script>

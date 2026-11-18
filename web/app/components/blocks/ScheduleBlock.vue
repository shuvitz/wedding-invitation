<!--
    `maps_url` was already validated by Laravel (BlockRegistry) to only ever
    be an http(s) URL, so it's safe to use directly as an href here.
-->
<template>
    <section class="space-y-6 py-4">
        <h2 v-if="data.title" class="text-center text-2xl font-semibold text-foreground">{{ data.title }}</h2>

        <div v-if="data.items?.length" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div v-for="(item, index) in data.items" :key="index" class="space-y-2 rounded-lg border border-border bg-card p-6 text-center">
                <h3 class="text-xl font-semibold text-foreground">{{ item.title }}</h3>
                <p v-if="item.date" class="font-medium text-foreground">{{ formatDate(item.date) }}</p>
                <p v-if="item.start_time" class="text-muted-foreground">
                    {{ item.start_time }}<template v-if="item.end_time"> – {{ item.end_time }}</template>
                </p>
                <p v-if="item.venue" class="font-medium text-foreground">{{ item.venue }}</p>
                <p v-if="item.address" class="text-sm text-muted-foreground">{{ item.address }}</p>
                <a
                    v-if="item.maps_url"
                    :href="item.maps_url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow transition hover:bg-primary/90"
                >
                    Open in Maps
                </a>
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import type { ScheduleBlockData } from '~/types/cms';

defineProps<{ data: ScheduleBlockData }>();

const dateFormat = new Intl.DateTimeFormat(useRuntimeConfig().public.locale, {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    timeZone: 'UTC',
});

// Parsed and formatted in UTC so the wall-clock date never shifts by the
// server's or visitor's timezone.
function formatDate(date: string): string {
    return dateFormat.format(new Date(`${date}T00:00:00Z`));
}
</script>

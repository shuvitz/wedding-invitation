<!--
    `maps_url` was already validated by Laravel (BlockRegistry) to only ever
    be an http(s) URL, so it's safe to use directly as an href here.
-->
<template>
    <section class="space-y-6 py-4">
        <h2 v-if="data.title" class="text-center text-2xl font-semibold text-foreground">{{ data.title }}</h2>

        <!-- Client-only (`remaining` is null until mounted) so SSR and hydration agree; hidden once the event starts. -->
        <div v-if="remaining" class="flex justify-center gap-3" role="timer" aria-label="Hitung mundur menuju acara">
            <div v-for="unit in remaining" :key="unit.label" class="w-20 rounded-lg border border-border bg-card p-3 text-center">
                <p class="text-2xl font-bold tabular-nums text-foreground">{{ unit.value }}</p>
                <p class="text-xs text-muted-foreground">{{ unit.label }}</p>
            </div>
        </div>

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

const props = defineProps<{ data: ScheduleBlockData }>();

// shortcut: the wall-clock date/time is read in the visitor's timezone, so
// a guest in another zone sees it off by the difference. Store the event's
// UTC offset on the block if out-of-town guests matter.
const target = computed(() => {
    const first = props.data.items?.[0];
    if (!props.data.show_countdown || !first?.date) return null;
    return new Date(`${first.date}T${first.start_time || '00:00'}:00`).getTime();
});

const now = ref<number | null>(null);
let timer: ReturnType<typeof setInterval> | undefined;

onMounted(() => {
    now.value = Date.now();
    timer = setInterval(() => (now.value = Date.now()), 1000);
});
onBeforeUnmount(() => clearInterval(timer));

const remaining = computed(() => {
    if (target.value === null || now.value === null) return null;
    const seconds = Math.floor((target.value - now.value) / 1000);
    if (seconds <= 0) return null;
    return [
        { label: 'Hari', value: Math.floor(seconds / 86400) },
        { label: 'Jam', value: Math.floor(seconds / 3600) % 24 },
        { label: 'Menit', value: Math.floor(seconds / 60) % 60 },
        { label: 'Detik', value: seconds % 60 },
    ];
});

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

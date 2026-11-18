<!--
    Full-screen overlay over the whole invitation until the guest taps the
    button. Rendered on the server too, so the invitation never flashes
    before the cover. The guest's name comes from `?to=` (e.g.
    ?to=Budi+Santoso) and is only ever rendered as escaped text.
-->
<template>
    <Transition leave-active-class="transition-opacity duration-700" leave-to-class="opacity-0">
        <div
            v-if="!opened"
            role="dialog"
            aria-modal="true"
            aria-labelledby="cover-names"
            class="fixed inset-0 z-50 flex flex-col items-center justify-center gap-4 overflow-y-auto bg-background bg-cover bg-center px-6 py-10 text-center"
            :style="data.image_url ? { backgroundImage: `url(${JSON.stringify(data.image_url)})` } : undefined"
        >
            <!-- Keeps text readable over any photo. -->
            <div v-if="data.image_url" class="absolute inset-0 bg-background/70" aria-hidden="true" />
            <div class="relative space-y-4">
                <p v-if="data.title" class="text-sm uppercase tracking-widest text-muted-foreground">{{ data.title }}</p>
                <h1 id="cover-names" class="text-4xl font-bold tracking-tight text-foreground sm:text-5xl">{{ data.names }}</h1>
                <div v-if="guest" class="space-y-1 pt-4">
                    <p v-if="data.greeting" class="text-sm text-muted-foreground">{{ data.greeting }}</p>
                    <p class="text-xl font-semibold text-foreground">{{ guest }}</p>
                </div>
                <button
                    ref="openButton"
                    type="button"
                    class="inline-flex items-center justify-center rounded-md bg-primary px-6 py-3 text-sm font-medium text-primary-foreground shadow transition hover:bg-primary/90"
                    @click="opened = true"
                >
                    {{ data.button_label || 'Buka Undangan' }}
                </button>
            </div>
        </div>
    </Transition>
</template>

<script setup lang="ts">
import type { CoverBlockData } from '~/types/cms';

defineProps<{ data: CoverBlockData }>();

const route = useRoute();
const opened = ref(false);
const openButton = ref<HTMLButtonElement | null>(null);

// `?to=a&to=b` arrives as an array; take the first. Capped so a long link
// can't blow up the layout.
const guest = computed(() => {
    const to = route.query.to;
    const value = Array.isArray(to) ? to[0] : to;
    return (value ?? '').trim().slice(0, 100);
});

// Lock page scroll behind the overlay (SSR-safe, unlike touching
// document.body directly).
useHead(() => ({ bodyAttrs: { class: opened.value ? '' : 'overflow-hidden' } }));

onMounted(() => openButton.value?.focus());
</script>

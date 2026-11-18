<template>
    <section class="space-y-6 py-4">
        <h2 v-if="data.title" class="text-center text-2xl font-semibold text-foreground">{{ data.title }}</h2>
        <p v-if="data.description" class="mx-auto max-w-prose whitespace-pre-line text-center text-muted-foreground">{{ data.description }}</p>

        <div v-if="data.items?.length" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div v-for="(item, index) in data.items" :key="index" class="space-y-2 rounded-lg border border-border bg-card p-6 text-center">
                <h3 class="text-lg font-semibold text-foreground">{{ item.bank }}</h3>
                <p class="select-all font-mono text-xl text-foreground">{{ item.account_number }}</p>
                <p class="text-sm text-muted-foreground">a.n. {{ item.account_name }}</p>
                <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow transition hover:bg-primary/90"
                    @click="copy(item.account_number, index)"
                >
                    {{ copiedIndex === index ? 'Tersalin' : 'Salin Nomor' }}
                </button>
            </div>
        </div>
        <!-- Announces the copy result to screen readers. -->
        <p class="sr-only" aria-live="polite">{{ copiedIndex !== null ? 'Nomor rekening tersalin' : '' }}</p>
    </section>
</template>

<script setup lang="ts">
import type { GiftBlockData } from '~/types/cms';

defineProps<{ data: GiftBlockData }>();

const copiedIndex = ref<number | null>(null);
let resetTimer: ReturnType<typeof setTimeout> | undefined;

// Spaces/dashes are only for readability; banking apps want bare digits.
// If the clipboard is unavailable (non-HTTPS, denied), the number stays
// select-all so it can still be copied by hand.
async function copy(accountNumber: string | null, index: number) {
    try {
        await navigator.clipboard.writeText((accountNumber ?? '').replace(/\D/g, ''));
    } catch {
        return;
    }
    copiedIndex.value = index;
    clearTimeout(resetTimer);
    resetTimer = setTimeout(() => (copiedIndex.value = null), 2000);
}

onBeforeUnmount(() => clearTimeout(resetTimer));
</script>

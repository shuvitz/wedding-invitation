<!--
    `body` is plain text from Laravel (see the Laravel rich_text block
    docblock — it was never HTML to begin with). Vue's `{{ }}` text
    interpolation already escapes it, and `whitespace-pre-line` preserves
    line breaks visually via CSS alone — no v-html, no unescaped markup
    anywhere, so there is nothing here for stored content to break out of.
-->
<template>
    <section class="space-y-3 py-4">
        <h2 v-if="data.title" class="text-2xl font-semibold text-foreground">{{ data.title }}</h2>
        <p class="whitespace-pre-line leading-relaxed text-foreground">{{ data.body }}</p>
    </section>
</template>

<script setup lang="ts">
import type { RichTextBlockData } from '~/types/cms';

defineProps<{ data: RichTextBlockData }>();
</script>

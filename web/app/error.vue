<template>
    <div class="flex min-h-screen flex-col items-center justify-center gap-4 bg-background px-6 text-center text-foreground">
        <h1 class="text-6xl font-bold">{{ error.statusCode }}</h1>
        <p class="text-lg text-muted-foreground">
            {{ error.statusCode === 404 ? "The page you're looking for doesn't exist." : 'Something went wrong.' }}
        </p>
        <button
            class="inline-flex items-center justify-center rounded-md bg-primary px-6 py-2 text-sm font-medium text-primary-foreground shadow transition hover:bg-primary/90"
            @click="clearError({ redirect: '/' })"
        >
            Back to homepage
        </button>
    </div>
</template>

<script setup lang="ts">
import type { NuxtError } from '#app';

defineProps<{ error: NuxtError }>();
</script>

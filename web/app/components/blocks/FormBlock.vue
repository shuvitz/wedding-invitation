<!--
    Posts straight from the browser to Laravel's one public write endpoint
    (POST /pages/{slug}/forms/{form_key}). Laravel validates against the
    field list stored on the published page, so nothing here is trusted —
    the `required`/`type` attributes below are only for a nicer UX.
-->
<template>
    <section class="mx-auto max-w-xl space-y-6 py-4">
        <h2 v-if="data.title" class="text-center text-2xl font-semibold text-foreground">{{ data.title }}</h2>

        <p v-if="successMessage" class="rounded-lg border border-border bg-card p-6 text-center text-foreground" role="status">
            {{ successMessage }}
        </p>

        <form v-else class="space-y-4" novalidate @submit.prevent="submit">
            <div v-for="field in data.fields" :key="field.name" class="space-y-1">
                <label :for="inputId(field.name)" class="block text-sm font-medium text-foreground">
                    {{ field.label }}<span v-if="field.required" aria-hidden="true"> *</span>
                </label>

                <textarea
                    v-if="field.type === 'textarea'"
                    :id="inputId(field.name)"
                    v-model="values[field.name]"
                    :required="field.required"
                    :aria-invalid="!!errors[field.name]"
                    rows="4"
                    class="w-full rounded-md border border-border bg-background px-3 py-2 text-foreground"
                />
                <select
                    v-else-if="field.type === 'select'"
                    :id="inputId(field.name)"
                    v-model="values[field.name]"
                    :required="field.required"
                    :aria-invalid="!!errors[field.name]"
                    class="w-full rounded-md border border-border bg-background px-3 py-2 text-foreground"
                >
                    <option value="">—</option>
                    <option v-for="option in field.options" :key="option" :value="option">{{ option }}</option>
                </select>
                <input
                    v-else
                    :id="inputId(field.name)"
                    v-model="values[field.name]"
                    :type="field.type === 'number' ? 'number' : 'text'"
                    :required="field.required"
                    :aria-invalid="!!errors[field.name]"
                    class="w-full rounded-md border border-border bg-background px-3 py-2 text-foreground"
                >

                <p v-if="errors[field.name]" class="text-sm text-red-600">{{ errors[field.name]?.[0] }}</p>
            </div>

            <!-- Honeypot: hidden from people and screen readers; bots that fill it are dropped server-side. -->
            <div class="absolute -left-[9999px]" aria-hidden="true">
                <label :for="inputId('_hp')">Biarkan kosong</label>
                <input :id="inputId('_hp')" v-model="honeypot" type="text" tabindex="-1" autocomplete="off">
            </div>

            <p v-if="generalError" class="text-sm text-red-600" role="alert">{{ generalError }}</p>

            <button
                type="submit"
                :disabled="isSubmitting"
                class="inline-flex w-full items-center justify-center rounded-md bg-primary px-6 py-2 text-sm font-medium text-primary-foreground shadow transition hover:bg-primary/90 disabled:opacity-50"
            >
                {{ data.submit_label || 'Kirim' }}
            </button>
        </form>
    </section>
</template>

<script setup lang="ts">
import type { FormBlockData } from '~/types/cms';

const props = defineProps<{ data: FormBlockData; pageSlug: string }>();

const api = useApi();

const values = reactive<Record<string, string>>(Object.fromEntries(props.data.fields.map((field) => [field.name, ''])));
const honeypot = ref('');
const errors = ref<Record<string, string[]>>({});
const generalError = ref('');
const successMessage = ref('');
const isSubmitting = ref(false);

// The guest's name from their invitation link (`?to=`), so the admin's guest list can show who replied.
const route = useRoute();
const invitedAs = computed(() => {
    const to = route.query.to;
    return (Array.isArray(to) ? to[0] : to) ?? '';
});

function inputId(name: string): string {
    return `form-${props.data.form_key}-${name}`;
}

async function submit() {
    isSubmitting.value = true;
    errors.value = {};
    generalError.value = '';

    try {
        const response = await api<{ message: string }>(`/pages/${props.pageSlug}/forms/${props.data.form_key}`, {
            method: 'POST',
            headers: { Accept: 'application/json' },
            body: { ...values, _hp: honeypot.value, _to: invitedAs.value },
        });
        successMessage.value = response.message;
    } catch (error: any) {
        const status = error?.response?.status;
        if (status === 422) {
            errors.value = error.data?.errors ?? {};
        } else if (status === 429) {
            generalError.value = 'Terlalu banyak percobaan. Silakan coba lagi sebentar lagi.';
        } else {
            generalError.value = 'Terjadi kesalahan. Silakan coba lagi.';
        }
    } finally {
        isSubmitting.value = false;
    }
}
</script>

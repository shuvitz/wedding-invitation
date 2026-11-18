<template>
    <div class="space-y-2">
        <Label v-if="label">{{ label }}</Label>

        <!-- Single-select mode: bound value + preview thumbnail -->
        <div v-if="!multiple" class="flex items-center gap-3">
            <div class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-md border border-border bg-muted">
                <img v-if="resolvedPreviewUrl" :src="resolvedPreviewUrl" :alt="label" class="h-full w-full object-cover">
                <span v-else class="text-xs text-muted-foreground">None</span>
            </div>

            <div class="flex gap-2">
                <Button type="button" variant="outline" size="sm" :disabled="disabled" @click="open">
                    {{ resolvedPreviewUrl ? 'Change' : 'Choose Image' }}
                </Button>
                <Button v-if="resolvedPreviewUrl" type="button" variant="outline" size="sm" :disabled="disabled" @click="clear">
                    Clear
                </Button>
            </div>
        </div>

        <!-- Multi-select mode: no single bound value, just a launcher — the
             caller owns the resulting list (see GalleryBlock editor in
             PageEditorPage.vue) via the `select` event. -->
        <Button v-else type="button" variant="outline" size="sm" :disabled="disabled" @click="open">
            Add Images
        </Button>

        <!-- Picker modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="close">
            <Card class="flex max-h-[80vh] w-full max-w-2xl flex-col">
                <CardHeader>
                    <CardTitle>{{ label ? `Choose ${label}` : 'Choose Media' }}</CardTitle>
                </CardHeader>
                <CardContent class="flex flex-1 flex-col gap-4 overflow-hidden">
                    <Input v-model="search" type="search" placeholder="Search by filename…" @input="onSearchInput" />

                    <div class="flex-1 overflow-y-auto">
                        <div v-if="isLoading" class="py-10 text-center text-sm text-muted-foreground">Loading…</div>
                        <div v-else-if="items.length === 0" class="py-10 text-center text-sm text-muted-foreground">No media found.</div>
                        <div v-else class="grid grid-cols-3 gap-3 sm:grid-cols-4">
                            <button
                                v-for="item in items"
                                :key="item.id"
                                type="button"
                                class="relative overflow-hidden rounded-lg border border-border transition hover:border-primary"
                                :class="multiple && isSelected(item) ? 'ring-2 ring-primary' : ''"
                                @click="handleItemClick(item)"
                            >
                                <div class="aspect-square overflow-hidden bg-muted">
                                    <img :src="item.url" :alt="item.alt_text || item.original_name" class="h-full w-full object-cover">
                                </div>
                                <span
                                    v-if="multiple && isSelected(item)"
                                    class="absolute right-1 top-1 flex h-5 w-5 items-center justify-center rounded-full bg-primary text-xs font-bold text-primary-foreground"
                                >
                                    ✓
                                </span>
                            </button>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2">
                        <Button type="button" variant="outline" @click="close">Cancel</Button>
                        <Button v-if="multiple" type="button" :disabled="selectedItems.length === 0" @click="confirmMultiple">
                            Add {{ selectedItems.length }} selected
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue';
import { mediaApi } from '@/api/media.api';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps({
    modelValue: { type: [Number, String, null], default: null },
    label: { type: String, default: '' },
    previewUrl: { type: String, default: null },
    disabled: { type: Boolean, default: false },
    // When true, the picker becomes a bulk launcher: clicking items toggles
    // selection instead of selecting-and-closing, and confirming emits
    // `select` with the whole array. modelValue/previewUrl are unused in
    // this mode — there's no single "current" value for a multi-add picker.
    multiple: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue', 'update:previewUrl', 'select']);

const showModal = ref(false);
const items = ref([]);
const isLoading = ref(false);
const search = ref('');
const selectedItems = ref([]); // multi-select mode only: [{id, url}]

// If the caller already knows the URL (e.g. Settings, via SettingResource's
// resolved response), use it directly. Otherwise — e.g. a page block that
// only stores a media ID — resolve it ourselves so the picker still shows
// a preview for existing selections.
const resolvedPreviewUrl = ref(props.previewUrl);

watch(() => props.previewUrl, (value) => {
    resolvedPreviewUrl.value = value;
});

watch(() => props.modelValue, (id) => {
    if (! id) {
        resolvedPreviewUrl.value = null;
    } else if (! props.previewUrl) {
        resolveFromId(id);
    }
});

async function resolveFromId(id) {
    try {
        const { data } = await mediaApi.show(id);
        resolvedPreviewUrl.value = data.data.url;
    } catch {
        resolvedPreviewUrl.value = null;
    }
}

onMounted(() => {
    if (!props.multiple && props.modelValue && !props.previewUrl) {
        resolveFromId(props.modelValue);
    }
});

async function fetchItems() {
    isLoading.value = true;
    try {
        const { data } = await mediaApi.list({ search: search.value || undefined, per_page: 24 });
        items.value = data.data;
    } finally {
        isLoading.value = false;
    }
}

let searchTimeout = null;
function onSearchInput() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(fetchItems, 350);
}

function open() {
    selectedItems.value = [];
    showModal.value = true;
    fetchItems();
}

function close() {
    showModal.value = false;
}

function handleItemClick(item) {
    if (props.multiple) {
        toggleSelect(item);
    } else {
        select(item);
    }
}

function isSelected(item) {
    return selectedItems.value.some((i) => i.id === item.id);
}

function toggleSelect(item) {
    const index = selectedItems.value.findIndex((i) => i.id === item.id);
    if (index === -1) {
        selectedItems.value.push({ id: item.id, url: item.url });
    } else {
        selectedItems.value.splice(index, 1);
    }
}

function confirmMultiple() {
    emit('select', selectedItems.value);
    selectedItems.value = [];
    close();
}

function select(item) {
    resolvedPreviewUrl.value = item.url;
    emit('update:modelValue', item.id);
    emit('update:previewUrl', item.url);
    close();
}

function clear() {
    resolvedPreviewUrl.value = null;
    emit('update:modelValue', null);
    emit('update:previewUrl', null);
}
</script>

<template>
    <div class="space-y-6">
        <!-- Page header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Media Library</h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    <span v-if="isAdmin">Upload and manage images used across your site.</span>
                    <span v-else>Images used across your site.</span>
                </p>
            </div>
            <div v-if="isAdmin">
                <input ref="fileInput" type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="hidden" @change="onFileSelected">
                <Button :disabled="mediaStore.isUploading" @click="fileInput.click()">
                    <span v-if="mediaStore.isUploading">Uploading… {{ mediaStore.uploadProgress }}%</span>
                    <span v-else>Upload Image</span>
                </Button>
            </div>
        </div>

        <div v-if="uploadErrorMessage" class="rounded-lg bg-destructive/10 p-3 text-sm text-destructive">
            {{ uploadErrorMessage }}
        </div>

        <!-- Search -->
        <Card>
            <CardContent class="pt-6">
                <Input
                    v-model="mediaStore.search"
                    type="search"
                    placeholder="Search by filename…"
                    class="sm:max-w-xs"
                    @input="onSearchInput"
                />
            </CardContent>
        </Card>

        <!-- Grid -->
        <div v-if="mediaStore.isLoading && mediaStore.items.length === 0" class="py-10 text-center text-sm text-muted-foreground">
            Loading media…
        </div>

        <div v-else-if="mediaStore.items.length === 0" class="py-10 text-center text-sm text-muted-foreground">
            No media yet.<span v-if="isAdmin"> Upload an image to get started.</span>
        </div>

        <div v-else class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
            <button
                v-for="item in mediaStore.items"
                :key="item.id"
                type="button"
                class="group overflow-hidden rounded-lg border border-border bg-card text-left transition hover:border-primary/50"
                @click="openDetails(item)"
            >
                <div class="aspect-square overflow-hidden bg-muted">
                    <img :src="item.url" :alt="item.alt_text || item.original_name" class="h-full w-full object-cover transition group-hover:scale-105">
                </div>
                <div class="p-2">
                    <p class="truncate text-xs font-medium text-foreground">{{ item.original_name }}</p>
                    <p class="text-xs text-muted-foreground">
                        <span v-if="item.width && item.height">{{ item.width }}×{{ item.height }} · </span>{{ formatBytes(item.size) }}
                    </p>
                </div>
            </button>
        </div>

        <!-- Pagination -->
        <div v-if="mediaStore.meta && mediaStore.meta.last_page > 1" class="flex items-center justify-between text-sm text-muted-foreground">
            <span>Page {{ mediaStore.meta.current_page }} of {{ mediaStore.meta.last_page }} · {{ mediaStore.meta.total }} items</span>
            <div class="flex gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="mediaStore.meta.current_page <= 1"
                    @click="mediaStore.goToPage(mediaStore.meta.current_page - 1)"
                >
                    Previous
                </Button>
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="mediaStore.meta.current_page >= mediaStore.meta.last_page"
                    @click="mediaStore.goToPage(mediaStore.meta.current_page + 1)"
                >
                    Next
                </Button>
            </div>
        </div>

        <!-- Details modal -->
        <div v-if="selectedItem" class="fixed inset-0 z-40 flex items-center justify-center bg-black/40 p-4" @click.self="closeDetails">
            <Card class="w-full max-w-lg">
                <CardHeader>
                    <CardTitle class="truncate">{{ selectedItem.original_name }}</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <img :src="selectedItem.url" :alt="selectedItem.alt_text || selectedItem.original_name" class="max-h-64 w-full rounded-md object-contain bg-muted">

                    <dl class="grid grid-cols-2 gap-2 text-sm">
                        <div>
                            <dt class="text-muted-foreground">Dimensions</dt>
                            <dd class="text-foreground">{{ selectedItem.width && selectedItem.height ? `${selectedItem.width}×${selectedItem.height}` : '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Size</dt>
                            <dd class="text-foreground">{{ formatBytes(selectedItem.size) }}</dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Uploaded</dt>
                            <dd class="text-foreground">{{ formatDate(selectedItem.created_at) }}</dd>
                        </div>
                        <div v-if="selectedItem.uploader_name">
                            <dt class="text-muted-foreground">Uploaded by</dt>
                            <dd class="text-foreground">{{ selectedItem.uploader_name }}</dd>
                        </div>
                    </dl>

                    <div class="space-y-2">
                        <Label for="alt-text">Alt text</Label>
                        <Input id="alt-text" v-model="altTextDraft" :disabled="!isAdmin" placeholder="Describe this image…" />
                    </div>

                    <div class="flex justify-between gap-2 pt-2">
                        <Button v-if="isAdmin" type="button" variant="outline" class="text-destructive" @click="confirmDelete(selectedItem)">
                            Delete
                        </Button>
                        <div class="ml-auto flex gap-2">
                            <Button type="button" variant="outline" @click="closeDetails">Close</Button>
                            <Button v-if="isAdmin" type="button" :disabled="isSavingAltText" @click="saveAltText">
                                <span v-if="isSavingAltText">Saving…</span>
                                <span v-else>Save</span>
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useAuthStore } from '@/stores/auth.store';
import { useMediaStore } from '@/stores/media.store';
import { useUiStore } from '@/stores/ui.store';
import { formatBytes } from '@/lib/utils';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const auth = useAuthStore();
const mediaStore = useMediaStore();
const ui = useUiStore();

const isAdmin = computed(() => auth.user?.role === 'admin');

onMounted(() => {
    mediaStore.fetchMedia();
});

function formatDate(value) {
    return value ? new Date(value).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' }) : '—';
}

// ── Search ─────────────────────────────────────────────
let searchTimeout = null;
function onSearchInput() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => mediaStore.applyFilters(), 350);
}

// ── Upload ─────────────────────────────────────────────
const fileInput = ref(null);
const uploadErrorMessage = ref('');

async function onFileSelected(event) {
    const file = event.target.files[0];
    event.target.value = '';
    if (!file) {
        return;
    }

    uploadErrorMessage.value = '';

    try {
        await mediaStore.upload(file);
        ui.showToast('Image uploaded.', 'success');
    } catch (error) {
        uploadErrorMessage.value = error.response?.data?.errors?.file?.[0]
            ?? error.response?.data?.message
            ?? 'Could not upload this image.';
    }
}

// ── Details / alt text / delete ───────────────────────
const selectedItem = ref(null);
const altTextDraft = ref('');
const isSavingAltText = ref(false);

function openDetails(item) {
    selectedItem.value = item;
    altTextDraft.value = item.alt_text ?? '';
}

function closeDetails() {
    selectedItem.value = null;
}

async function saveAltText() {
    isSavingAltText.value = true;
    try {
        const updated = await mediaStore.updateAltText(selectedItem.value.id, altTextDraft.value);
        selectedItem.value = updated;
        ui.showToast('Alt text saved.', 'success');
    } catch (error) {
        ui.showToast(error.response?.data?.message ?? 'Could not save alt text.', 'error');
    } finally {
        isSavingAltText.value = false;
    }
}

async function confirmDelete(item) {
    if (! window.confirm(`Delete "${item.original_name}"? This cannot be undone.`)) {
        return;
    }

    try {
        await mediaStore.remove(item.id);
        closeDetails();
        ui.showToast('Media deleted.', 'success');
    } catch (error) {
        ui.showToast(error.response?.data?.message ?? 'Could not delete this media item.', 'error');
    }
}
</script>

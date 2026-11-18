import { defineStore } from 'pinia';
import { ref } from 'vue';
import { mediaApi } from '../api/media.api';

export const useMediaStore = defineStore('media', () => {
    // ── State ──────────────────────────────────────────
    const items = ref([]);
    const meta = ref(null);
    const isLoading = ref(false);
    const isUploading = ref(false);
    const uploadProgress = ref(0);
    const errors = ref({});
    const search = ref('');
    const page = ref(1);

    // ── Actions ────────────────────────────────────────

    async function fetchMedia() {
        isLoading.value = true;
        try {
            const { data } = await mediaApi.list({
                search: search.value || undefined,
                page: page.value,
            });
            items.value = data.data;
            meta.value = data.meta;
        } finally {
            isLoading.value = false;
        }
    }

    function applyFilters() {
        page.value = 1;
        return fetchMedia();
    }

    function goToPage(newPage) {
        page.value = newPage;
        return fetchMedia();
    }

    async function upload(file, altText) {
        errors.value = {};
        isUploading.value = true;
        uploadProgress.value = 0;

        try {
            const { data } = await mediaApi.upload(file, altText, (event) => {
                if (event.total) {
                    uploadProgress.value = Math.round((event.loaded / event.total) * 100);
                }
            });
            await fetchMedia();
            return data.data;
        } catch (error) {
            if (error.response?.status === 422) {
                errors.value = error.response.data.errors ?? {};
            }
            throw error;
        } finally {
            isUploading.value = false;
        }
    }

    async function updateAltText(id, altText) {
        const { data } = await mediaApi.update(id, { alt_text: altText });
        const index = items.value.findIndex((item) => item.id === id);
        if (index !== -1) {
            items.value[index] = data.data;
        }
        return data.data;
    }

    async function remove(id) {
        await mediaApi.remove(id);
        await fetchMedia();
    }

    return {
        items,
        meta,
        isLoading,
        isUploading,
        uploadProgress,
        errors,
        search,
        page,
        fetchMedia,
        applyFilters,
        goToPage,
        upload,
        updateAltText,
        remove,
    };
});

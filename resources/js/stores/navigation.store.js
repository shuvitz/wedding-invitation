import { defineStore } from 'pinia';
import { ref } from 'vue';
import { navigationApi } from '../api/navigation.api';

export const useNavigationStore = defineStore('navigation', () => {
    // ── State ──────────────────────────────────────────
    const items = ref([]);
    const isLoading = ref(false);
    const errors = ref({});

    // ── Actions ────────────────────────────────────────

    async function fetchItems() {
        isLoading.value = true;
        try {
            const { data } = await navigationApi.list();
            items.value = data.data;
        } finally {
            isLoading.value = false;
        }
    }

    async function createItem(payload) {
        errors.value = {};
        try {
            const { data } = await navigationApi.create(payload);
            await fetchItems();
            return data.data;
        } catch (error) {
            if (error.response?.status === 422) {
                errors.value = error.response.data.errors ?? {};
            }
            throw error;
        }
    }

    async function updateItem(id, payload) {
        errors.value = {};
        try {
            const { data } = await navigationApi.update(id, payload);
            await fetchItems();
            return data.data;
        } catch (error) {
            if (error.response?.status === 422) {
                errors.value = error.response.data.errors ?? {};
            }
            throw error;
        }
    }

    async function setVisibility(id, visible) {
        const { data } = await navigationApi.setVisibility(id, visible);
        await fetchItems();
        return data.data;
    }

    async function deleteItem(id) {
        await navigationApi.remove(id);
        await fetchItems();
    }

    return {
        items,
        isLoading,
        errors,
        fetchItems,
        createItem,
        updateItem,
        setVisibility,
        deleteItem,
    };
});

import { defineStore } from 'pinia';
import { ref } from 'vue';
import { pagesApi } from '../api/pages.api';

export const usePagesStore = defineStore('pages', () => {
    // ── State ──────────────────────────────────────────
    const pages = ref([]);
    const meta = ref(null);
    const isLoading = ref(false);
    const errors = ref({});
    const search = ref('');
    const statusFilter = ref('');
    const page = ref(1);

    // ── Actions ────────────────────────────────────────

    async function fetchPages() {
        isLoading.value = true;
        try {
            const { data } = await pagesApi.list({
                search: search.value || undefined,
                status: statusFilter.value || undefined,
                page: page.value,
            });
            pages.value = data.data;
            meta.value = data.meta;
        } finally {
            isLoading.value = false;
        }
    }

    function applyFilters() {
        page.value = 1;
        return fetchPages();
    }

    function goToPage(newPage) {
        page.value = newPage;
        return fetchPages();
    }

    async function fetchPage(id) {
        const { data } = await pagesApi.show(id);
        return data.data;
    }

    async function createPage(payload) {
        errors.value = {};
        try {
            const { data } = await pagesApi.create(payload);
            return data.data;
        } catch (error) {
            if (error.response?.status === 422) {
                errors.value = error.response.data.errors ?? {};
            }
            throw error;
        }
    }

    async function updatePage(id, payload) {
        errors.value = {};
        try {
            const { data } = await pagesApi.update(id, payload);
            return data.data;
        } catch (error) {
            if (error.response?.status === 422) {
                errors.value = error.response.data.errors ?? {};
            }
            throw error;
        }
    }

    async function setStatus(id, status) {
        const { data } = await pagesApi.setStatus(id, status);
        await fetchPages();
        return data.data;
    }

    async function setHome(id, isHome) {
        const { data } = await pagesApi.setHome(id, isHome);
        await fetchPages();
        return data.data;
    }

    async function deletePage(id) {
        await pagesApi.remove(id);
        await fetchPages();
    }

    return {
        pages,
        meta,
        isLoading,
        errors,
        search,
        statusFilter,
        page,
        fetchPages,
        applyFilters,
        goToPage,
        fetchPage,
        createPage,
        updatePage,
        setStatus,
        setHome,
        deletePage,
    };
});

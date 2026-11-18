import { defineStore } from 'pinia';
import { ref } from 'vue';
import { usersApi } from '../api/users.api';

export const useUsersStore = defineStore('users', () => {
    // ── State ──────────────────────────────────────────
    const users = ref([]);
    const meta = ref(null);
    const isLoading = ref(false);
    const errors = ref({});
    const search = ref('');
    const roleFilter = ref('');
    const page = ref(1);

    // ── Actions ────────────────────────────────────────

    /**
     * Fetch users using the current search/role/page state.
     */
    async function fetchUsers() {
        isLoading.value = true;
        try {
            const { data } = await usersApi.list({
                search: search.value || undefined,
                role: roleFilter.value || undefined,
                page: page.value,
            });
            users.value = data.data;
            meta.value = data.meta;
        } finally {
            isLoading.value = false;
        }
    }

    /**
     * Reset to page 1 and re-fetch — used when filters change.
     */
    function applyFilters() {
        page.value = 1;
        return fetchUsers();
    }

    function goToPage(newPage) {
        page.value = newPage;
        return fetchUsers();
    }

    async function createUser(payload) {
        errors.value = {};
        try {
            const { data } = await usersApi.create(payload);
            await fetchUsers();
            return data.data;
        } catch (error) {
            if (error.response?.status === 422) {
                errors.value = error.response.data.errors ?? {};
            }
            throw error;
        }
    }

    async function updateUser(id, payload) {
        errors.value = {};
        try {
            const { data } = await usersApi.update(id, payload);
            await fetchUsers();
            return data.data;
        } catch (error) {
            if (error.response?.status === 422) {
                errors.value = error.response.data.errors ?? {};
            }
            throw error;
        }
    }

    async function setActive(id, active) {
        const { data } = await usersApi.setActive(id, active);
        await fetchUsers();
        return data.data;
    }

    return {
        users,
        meta,
        isLoading,
        errors,
        search,
        roleFilter,
        page,
        fetchUsers,
        applyFilters,
        goToPage,
        createUser,
        updateUser,
        setActive,
    };
});

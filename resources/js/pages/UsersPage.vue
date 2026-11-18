<template>
    <div class="space-y-6">
        <!-- Page header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Users</h1>
                <p class="mt-1 text-sm text-muted-foreground">Manage who can access Denji CMS.</p>
            </div>
            <Button @click="openCreateModal">New User</Button>
        </div>

        <!-- Filters -->
        <Card>
            <CardContent class="flex flex-col gap-3 pt-6 sm:flex-row sm:items-center">
                <Input
                    v-model="usersStore.search"
                    type="search"
                    placeholder="Search by name or email…"
                    class="sm:max-w-xs"
                    @input="onSearchInput"
                />
                <select
                    v-model="usersStore.roleFilter"
                    class="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring sm:w-40"
                    @change="usersStore.applyFilters()"
                >
                    <option value="">All roles</option>
                    <option v-for="role in roles" :key="role" :value="role">{{ roleLabel(role) }}</option>
                </select>
            </CardContent>
        </Card>

        <!-- Table -->
        <Card>
            <CardContent class="pt-6">
                <div v-if="usersStore.isLoading && usersStore.users.length === 0" class="py-10 text-center text-sm text-muted-foreground">
                    Loading users…
                </div>

                <div v-else-if="usersStore.users.length === 0" class="py-10 text-center text-sm text-muted-foreground">
                    No users found.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-border text-left text-xs uppercase tracking-wide text-muted-foreground">
                                <th class="pb-2 pr-4 font-medium">Name</th>
                                <th class="pb-2 pr-4 font-medium">Email</th>
                                <th class="pb-2 pr-4 font-medium">Role</th>
                                <th class="pb-2 pr-4 font-medium">Status</th>
                                <th class="pb-2 font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="user in usersStore.users" :key="user.id" class="border-b border-border last:border-0">
                                <td class="py-3 pr-4 font-medium text-foreground">{{ user.name }}</td>
                                <td class="py-3 pr-4 text-muted-foreground">{{ user.email }}</td>
                                <td class="py-3 pr-4">
                                    <span
                                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                        :class="user.role === 'admin' ? 'bg-primary/10 text-primary' : 'bg-secondary text-secondary-foreground'"
                                    >
                                        {{ roleLabel(user.role) }}
                                    </span>
                                </td>
                                <td class="py-3 pr-4">
                                    <span
                                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                        :class="user.active ? 'bg-emerald-500/10 text-emerald-600' : 'bg-muted text-muted-foreground'"
                                    >
                                        {{ user.active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="py-3">
                                    <div class="flex items-center gap-2">
                                        <Button variant="outline" size="sm" @click="openEditModal(user)">Edit</Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            :class="user.active ? 'text-destructive' : ''"
                                            @click="toggleActive(user)"
                                        >
                                            {{ user.active ? 'Deactivate' : 'Activate' }}
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="usersStore.meta && usersStore.meta.last_page > 1" class="mt-4 flex items-center justify-between text-sm text-muted-foreground">
                    <span>Page {{ usersStore.meta.current_page }} of {{ usersStore.meta.last_page }} · {{ usersStore.meta.total }} users</span>
                    <div class="flex gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="usersStore.meta.current_page <= 1"
                            @click="usersStore.goToPage(usersStore.meta.current_page - 1)"
                        >
                            Previous
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="usersStore.meta.current_page >= usersStore.meta.last_page"
                            @click="usersStore.goToPage(usersStore.meta.current_page + 1)"
                        >
                            Next
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Create / edit modal -->
        <div v-if="showModal" class="fixed inset-0 z-40 flex items-center justify-center bg-black/40 p-4" @click.self="closeModal">
            <Card class="w-full max-w-md">
                <CardHeader>
                    <CardTitle>{{ editingUser ? 'Edit User' : 'New User' }}</CardTitle>
                    <CardDescription v-if="editingUser">Leave password blank to keep it unchanged.</CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="formErrorMessage" class="mb-4 rounded-lg bg-destructive/10 p-3 text-sm text-destructive">
                        {{ formErrorMessage }}
                    </div>

                    <form class="space-y-4" @submit.prevent="submitForm">
                        <div class="space-y-2">
                            <Label for="user-name">Name</Label>
                            <Input id="user-name" v-model="form.name" required />
                            <p v-if="usersStore.errors?.name" class="text-xs text-destructive">{{ usersStore.errors.name[0] }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="user-email">Email</Label>
                            <Input id="user-email" v-model="form.email" type="email" required />
                            <p v-if="usersStore.errors?.email" class="text-xs text-destructive">{{ usersStore.errors.email[0] }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="user-password">Password</Label>
                            <Input
                                id="user-password"
                                v-model="form.password"
                                type="password"
                                :placeholder="editingUser ? '••••••••' : ''"
                                :required="!editingUser"
                            />
                            <p v-if="usersStore.errors?.password" class="text-xs text-destructive">{{ usersStore.errors.password[0] }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="user-role">Role</Label>
                            <select
                                id="user-role"
                                v-model="form.role"
                                required
                                class="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                            >
                                <option v-for="role in roles" :key="role" :value="role">{{ roleLabel(role) }}</option>
                            </select>
                            <p v-if="usersStore.errors?.role" class="text-xs text-destructive">{{ usersStore.errors.role[0] }}</p>
                        </div>

                        <div class="flex justify-end gap-2 pt-2">
                            <Button type="button" variant="outline" @click="closeModal">Cancel</Button>
                            <Button type="submit" :disabled="isSubmitting">
                                <span v-if="isSubmitting">Saving…</span>
                                <span v-else>{{ editingUser ? 'Save Changes' : 'Create User' }}</span>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useUsersStore } from '@/stores/users.store';
import { useUiStore } from '@/stores/ui.store';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const usersStore = useUsersStore();
const ui = useUiStore();

const roles = ['admin', 'editor'];

function roleLabel(role) {
    return role === 'admin' ? 'Admin' : 'Editor';
}

onMounted(() => {
    usersStore.fetchUsers();
});

// Debounced search — avoid firing a request on every keystroke.
let searchTimeout = null;
function onSearchInput() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => usersStore.applyFilters(), 350);
}

// ── Create / edit modal ──────────────────────────────
const showModal = ref(false);
const editingUser = ref(null);
const isSubmitting = ref(false);
const formErrorMessage = ref('');

const form = reactive({
    name: '',
    email: '',
    password: '',
    role: 'editor',
});

function openCreateModal() {
    editingUser.value = null;
    formErrorMessage.value = '';
    usersStore.errors = {};
    Object.assign(form, { name: '', email: '', password: '', role: 'editor' });
    showModal.value = true;
}

function openEditModal(user) {
    editingUser.value = user;
    formErrorMessage.value = '';
    usersStore.errors = {};
    Object.assign(form, { name: user.name, email: user.email, password: '', role: user.role });
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
}

async function submitForm() {
    isSubmitting.value = true;
    formErrorMessage.value = '';

    try {
        if (editingUser.value) {
            const payload = { name: form.name, email: form.email, role: form.role };
            if (form.password) {
                payload.password = form.password;
            }
            await usersStore.updateUser(editingUser.value.id, payload);
            ui.showToast('User updated.', 'success');
        } else {
            await usersStore.createUser({
                name: form.name,
                email: form.email,
                password: form.password,
                role: form.role,
            });
            ui.showToast('User created.', 'success');
        }

        showModal.value = false;
    } catch (error) {
        formErrorMessage.value = error.response?.data?.message ?? 'Something went wrong. Please try again.';
    } finally {
        isSubmitting.value = false;
    }
}

// ── Activate / deactivate ────────────────────────────
async function toggleActive(user) {
    const action = user.active ? 'deactivate' : 'activate';
    if (! window.confirm(`Are you sure you want to ${action} ${user.name}?`)) {
        return;
    }

    try {
        await usersStore.setActive(user.id, !user.active);
        ui.showToast(`${user.name} ${action}d.`, 'success');
    } catch (error) {
        ui.showToast(error.response?.data?.message ?? `Could not ${action} this user.`, 'error');
    }
}
</script>

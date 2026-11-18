<template>
    <div class="space-y-6">
        <!-- Page header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Navigation</h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    <span v-if="isAdmin">Manage the links shown in your site's navigation menu.</span>
                    <span v-else>The links shown in your site's navigation menu.</span>
                </p>
            </div>
            <Button v-if="isAdmin" @click="openCreateModal">New Item</Button>
        </div>

        <!-- Table -->
        <Card>
            <CardContent class="pt-6">
                <div v-if="navStore.isLoading && navStore.items.length === 0" class="py-10 text-center text-sm text-muted-foreground">
                    Loading navigation…
                </div>

                <div v-else-if="navStore.items.length === 0" class="py-10 text-center text-sm text-muted-foreground">
                    No navigation items yet.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-border text-left text-xs uppercase tracking-wide text-muted-foreground">
                                <th class="pb-2 pr-4 font-medium">Order</th>
                                <th class="pb-2 pr-4 font-medium">Label</th>
                                <th class="pb-2 pr-4 font-medium">URL</th>
                                <th class="pb-2 pr-4 font-medium">Visible</th>
                                <th v-if="isAdmin" class="pb-2 font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in navStore.items" :key="item.id" class="border-b border-border last:border-0">
                                <td class="py-3 pr-4">
                                    <input
                                        type="number"
                                        min="0"
                                        class="h-8 w-16 rounded-md border border-input bg-transparent px-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                                        :value="item.order"
                                        :disabled="!isAdmin"
                                        @change="onOrderChange(item, $event.target.value)"
                                    >
                                </td>
                                <td class="py-3 pr-4 font-medium text-foreground">{{ item.label }}</td>
                                <td class="py-3 pr-4 text-muted-foreground">{{ item.url }}</td>
                                <td class="py-3 pr-4">
                                    <span
                                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                        :class="item.visible ? 'bg-emerald-500/10 text-emerald-600' : 'bg-muted text-muted-foreground'"
                                    >
                                        {{ item.visible ? 'Visible' : 'Hidden' }}
                                    </span>
                                </td>
                                <td v-if="isAdmin" class="py-3">
                                    <div class="flex items-center gap-2">
                                        <Button variant="outline" size="sm" @click="openEditModal(item)">Edit</Button>
                                        <Button variant="outline" size="sm" @click="toggleVisibility(item)">
                                            {{ item.visible ? 'Hide' : 'Show' }}
                                        </Button>
                                        <Button variant="outline" size="sm" class="text-destructive" @click="confirmDelete(item)">Delete</Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>

        <!-- Create / edit modal -->
        <div v-if="showModal" class="fixed inset-0 z-40 flex items-center justify-center bg-black/40 p-4" @click.self="closeModal">
            <Card class="w-full max-w-md">
                <CardHeader>
                    <CardTitle>{{ editingItem ? 'Edit Navigation Item' : 'New Navigation Item' }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="formErrorMessage" class="mb-4 rounded-lg bg-destructive/10 p-3 text-sm text-destructive">
                        {{ formErrorMessage }}
                    </div>

                    <form class="space-y-4" @submit.prevent="submitForm">
                        <div class="space-y-2">
                            <Label for="item-label">Label</Label>
                            <Input id="item-label" v-model="form.label" required />
                            <p v-if="navStore.errors?.label" class="text-xs text-destructive">{{ navStore.errors.label[0] }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="item-url">URL / path</Label>
                            <Input id="item-url" v-model="form.url" placeholder="/about or https://example.com" required />
                            <p v-if="navStore.errors?.url" class="text-xs text-destructive">{{ navStore.errors.url[0] }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="item-order">Order</Label>
                            <Input id="item-order" v-model.number="form.order" type="number" min="0" />
                            <p v-if="navStore.errors?.order" class="text-xs text-destructive">{{ navStore.errors.order[0] }}</p>
                        </div>

                        <div class="flex justify-end gap-2 pt-2">
                            <Button type="button" variant="outline" @click="closeModal">Cancel</Button>
                            <Button type="submit" :disabled="isSubmitting">
                                <span v-if="isSubmitting">Saving…</span>
                                <span v-else>{{ editingItem ? 'Save Changes' : 'Create Item' }}</span>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useAuthStore } from '@/stores/auth.store';
import { useNavigationStore } from '@/stores/navigation.store';
import { useUiStore } from '@/stores/ui.store';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const auth = useAuthStore();
const navStore = useNavigationStore();
const ui = useUiStore();

const isAdmin = computed(() => auth.user?.role === 'admin');

onMounted(() => {
    navStore.fetchItems();
});

// ── Inline order edit ─────────────────────────────────
async function onOrderChange(item, value) {
    const order = Number(value);
    if (Number.isNaN(order) || order === item.order) {
        return;
    }

    try {
        await navStore.updateItem(item.id, { order });
    } catch (error) {
        ui.showToast(error.response?.data?.message ?? 'Could not update order.', 'error');
    }
}

// ── Create / edit modal ──────────────────────────────
const showModal = ref(false);
const editingItem = ref(null);
const isSubmitting = ref(false);
const formErrorMessage = ref('');

const form = reactive({ label: '', url: '', order: 0 });

function openCreateModal() {
    editingItem.value = null;
    formErrorMessage.value = '';
    navStore.errors = {};
    Object.assign(form, { label: '', url: '', order: 0 });
    showModal.value = true;
}

function openEditModal(item) {
    editingItem.value = item;
    formErrorMessage.value = '';
    navStore.errors = {};
    Object.assign(form, { label: item.label, url: item.url, order: item.order });
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
}

async function submitForm() {
    isSubmitting.value = true;
    formErrorMessage.value = '';

    try {
        const payload = { label: form.label, url: form.url, order: form.order };

        if (editingItem.value) {
            await navStore.updateItem(editingItem.value.id, payload);
            ui.showToast('Navigation item updated.', 'success');
        } else {
            await navStore.createItem(payload);
            ui.showToast('Navigation item created.', 'success');
        }

        showModal.value = false;
    } catch (error) {
        formErrorMessage.value = error.response?.data?.message ?? 'Something went wrong. Please try again.';
    } finally {
        isSubmitting.value = false;
    }
}

// ── Visibility & delete ───────────────────────────────
async function toggleVisibility(item) {
    try {
        await navStore.setVisibility(item.id, !item.visible);
    } catch (error) {
        ui.showToast(error.response?.data?.message ?? 'Could not update visibility.', 'error');
    }
}

async function confirmDelete(item) {
    if (! window.confirm(`Delete "${item.label}" from the navigation? This cannot be undone.`)) {
        return;
    }

    try {
        await navStore.deleteItem(item.id);
        ui.showToast('Navigation item deleted.', 'success');
    } catch (error) {
        ui.showToast(error.response?.data?.message ?? 'Could not delete this item.', 'error');
    }
}
</script>

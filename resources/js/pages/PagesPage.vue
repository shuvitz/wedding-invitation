<template>
    <div class="space-y-6">
        <!-- Page header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Pages</h1>
                <p class="mt-1 text-sm text-muted-foreground">Manage your website's pages.</p>
            </div>
            <Button @click="router.push({ name: 'admin.pages.new' })">New Page</Button>
        </div>

        <!-- Filters -->
        <Card>
            <CardContent class="flex flex-col gap-3 pt-6 sm:flex-row sm:items-center">
                <Input
                    v-model="pagesStore.search"
                    type="search"
                    placeholder="Search by title…"
                    class="sm:max-w-xs"
                    @input="onSearchInput"
                />
                <select
                    v-model="pagesStore.statusFilter"
                    class="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring sm:w-40"
                    @change="pagesStore.applyFilters()"
                >
                    <option value="">All statuses</option>
                    <option value="draft">Draft</option>
                    <option value="published">Published</option>
                </select>
            </CardContent>
        </Card>

        <!-- Table -->
        <Card>
            <CardContent class="pt-6">
                <div v-if="pagesStore.isLoading && pagesStore.pages.length === 0" class="py-10 text-center text-sm text-muted-foreground">
                    Loading pages…
                </div>

                <div v-else-if="pagesStore.pages.length === 0" class="py-10 text-center text-sm text-muted-foreground">
                    No pages found.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-border text-left text-xs uppercase tracking-wide text-muted-foreground">
                                <th class="pb-2 pr-4 font-medium">Title</th>
                                <th class="pb-2 pr-4 font-medium">Slug</th>
                                <th class="pb-2 pr-4 font-medium">Status</th>
                                <th class="pb-2 pr-4 font-medium">Updated</th>
                                <th class="pb-2 font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in pagesStore.pages" :key="item.id" class="border-b border-border last:border-0">
                                <td class="py-3 pr-4 font-medium text-foreground">{{ item.title }}</td>
                                <td class="py-3 pr-4 text-muted-foreground">/{{ item.slug }}</td>
                                <td class="py-3 pr-4">
                                    <span
                                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                        :class="item.status === 'published' ? 'bg-emerald-500/10 text-emerald-600' : 'bg-muted text-muted-foreground'"
                                    >
                                        {{ item.status === 'published' ? 'Published' : 'Draft' }}
                                    </span>
                                    <span
                                        v-if="item.is_home"
                                        class="ml-1 inline-flex items-center rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-medium text-primary"
                                    >
                                        Home
                                    </span>
                                </td>
                                <td class="py-3 pr-4 text-muted-foreground">{{ formatDate(item.updated_at) }}</td>
                                <td class="py-3">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <Button variant="outline" size="sm" @click="router.push({ name: 'admin.pages.edit', params: { id: item.id } })">
                                            Edit
                                        </Button>
                                        <Button
                                            v-if="isAdmin && item.content?.blocks?.some((block) => block.type === 'form')"
                                            variant="outline"
                                            size="sm"
                                            @click="router.push({ name: 'admin.pages.submissions', params: { id: item.id } })"
                                        >
                                            Submissions
                                        </Button>
                                        <a v-if="item.public_url" :href="item.public_url" target="_blank" rel="noopener" class="text-xs text-primary hover:underline">
                                            View
                                        </a>
                                        <Button v-if="isAdmin" variant="outline" size="sm" @click="togglePublish(item)">
                                            {{ item.status === 'published' ? 'Unpublish' : 'Publish' }}
                                        </Button>
                                        <Button
                                            v-if="isAdmin && item.status === 'published' && !item.is_home"
                                            variant="outline"
                                            size="sm"
                                            @click="makeHome(item)"
                                        >
                                            Set as Homepage
                                        </Button>
                                        <Button v-if="isAdmin && item.is_home" variant="outline" size="sm" @click="removeHome(item)">
                                            Unset Homepage
                                        </Button>
                                        <Button v-if="isAdmin" variant="outline" size="sm" class="text-destructive" @click="confirmDelete(item)">
                                            Delete
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="pagesStore.meta && pagesStore.meta.last_page > 1" class="mt-4 flex items-center justify-between text-sm text-muted-foreground">
                    <span>Page {{ pagesStore.meta.current_page }} of {{ pagesStore.meta.last_page }} · {{ pagesStore.meta.total }} pages</span>
                    <div class="flex gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="pagesStore.meta.current_page <= 1"
                            @click="pagesStore.goToPage(pagesStore.meta.current_page - 1)"
                        >
                            Previous
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="pagesStore.meta.current_page >= pagesStore.meta.last_page"
                            @click="pagesStore.goToPage(pagesStore.meta.current_page + 1)"
                        >
                            Next
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth.store';
import { usePagesStore } from '@/stores/pages.store';
import { useUiStore } from '@/stores/ui.store';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

const router = useRouter();
const auth = useAuthStore();
const pagesStore = usePagesStore();
const ui = useUiStore();

const isAdmin = computed(() => auth.user?.role === 'admin');

onMounted(() => {
    pagesStore.fetchPages();
});

function formatDate(value) {
    return value ? new Date(value).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' }) : '—';
}

let searchTimeout = null;
function onSearchInput() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => pagesStore.applyFilters(), 350);
}

async function togglePublish(item) {
    const newStatus = item.status === 'published' ? 'draft' : 'published';
    try {
        await pagesStore.setStatus(item.id, newStatus);
        ui.showToast(newStatus === 'published' ? 'Page published.' : 'Page unpublished.', 'success');
    } catch (error) {
        ui.showToast(error.response?.data?.message ?? 'Could not update status.', 'error');
    }
}

async function makeHome(item) {
    try {
        await pagesStore.setHome(item.id, true);
        ui.showToast(`"${item.title}" is now the homepage.`, 'success');
    } catch (error) {
        ui.showToast(error.response?.data?.message ?? 'Could not set this page as the homepage.', 'error');
    }
}

async function removeHome(item) {
    try {
        await pagesStore.setHome(item.id, false);
        ui.showToast(`"${item.title}" is no longer the homepage.`, 'success');
    } catch (error) {
        ui.showToast(error.response?.data?.message ?? 'Could not update the homepage.', 'error');
    }
}

async function confirmDelete(item) {
    if (! window.confirm(`Delete "${item.title}"? This cannot be undone.`)) {
        return;
    }

    try {
        await pagesStore.deletePage(item.id);
        ui.showToast('Page deleted.', 'success');
    } catch (error) {
        ui.showToast(error.response?.data?.message ?? 'Could not delete this page.', 'error');
    }
}
</script>

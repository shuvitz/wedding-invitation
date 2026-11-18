<template>
    <div class="space-y-6">
        <!-- Page header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Submissions</h1>
                <p class="mt-1 text-sm text-muted-foreground">What visitors sent through this page's forms.</p>
            </div>
            <div class="flex gap-2">
                <Button variant="outline" @click="router.push({ name: 'admin.pages.edit', params: { id: pageId } })">Back to Page</Button>
                <Button v-if="total > 0" as="a" variant="outline" :href="submissionsApi.exportUrl(pageId, formKey)" download>Export CSV</Button>
            </div>
        </div>

        <Card>
            <CardContent class="space-y-4 pt-6">
                <div v-if="formKeys.length > 1" class="flex items-center gap-2 text-sm">
                    <span class="text-muted-foreground">Form:</span>
                    <select
                        v-model="formKey"
                        class="h-8 rounded-md border border-input bg-transparent px-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                        @change="load(1)"
                    >
                        <option value="">All</option>
                        <option v-for="key in formKeys" :key="key" :value="key">{{ key }}</option>
                    </select>
                </div>

                <div v-if="isLoading && submissions.length === 0" class="py-10 text-center text-sm text-muted-foreground">
                    Loading submissions…
                </div>

                <div v-else-if="submissions.length === 0" class="py-10 text-center text-sm text-muted-foreground">
                    No submissions yet.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-border text-left text-xs uppercase tracking-wide text-muted-foreground">
                                <th class="pb-2 pr-4 font-medium">Submitted</th>
                                <th v-if="formKeys.length > 1" class="pb-2 pr-4 font-medium">Form</th>
                                <th v-for="column in columns" :key="column" class="pb-2 pr-4 font-medium">{{ column }}</th>
                                <th class="pb-2 font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="submission in submissions" :key="submission.id" class="border-b border-border align-top last:border-0">
                                <td class="whitespace-nowrap py-3 pr-4 text-muted-foreground">{{ new Date(submission.created_at).toLocaleString() }}</td>
                                <td v-if="formKeys.length > 1" class="py-3 pr-4 text-muted-foreground">{{ submission.form_key }}</td>
                                <td v-for="column in columns" :key="column" class="max-w-xs whitespace-pre-line break-words py-3 pr-4 text-foreground">{{ submission.data[column] ?? '' }}</td>
                                <td class="py-3">
                                    <Button variant="outline" size="sm" class="text-destructive" @click="confirmDelete(submission)">Delete</Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="lastPage > 1" class="flex items-center justify-between text-sm text-muted-foreground">
                    <span>{{ total }} submissions · page {{ currentPage }} of {{ lastPage }}</span>
                    <div class="flex gap-2">
                        <Button variant="outline" size="sm" :disabled="currentPage === 1" @click="load(currentPage - 1)">Previous</Button>
                        <Button variant="outline" size="sm" :disabled="currentPage === lastPage" @click="load(currentPage + 1)">Next</Button>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useUiStore } from '@/stores/ui.store';
import { pagesApi } from '@/api/pages.api';
import { submissionsApi } from '@/api/submissions.api';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';

const route = useRoute();
const router = useRouter();
const ui = useUiStore();

const pageId = Number(route.params.id);

const submissions = ref([]);
const formKeys = ref([]);
const formKey = ref('');
const isLoading = ref(false);
const currentPage = ref(1);
const lastPage = ref(1);
const total = ref(0);

// Union of keys across the rows shown, so submissions made before a
// form's fields changed still show every value they have.
const columns = computed(() => [...new Set(submissions.value.flatMap((submission) => Object.keys(submission.data)))]);

async function load(page = currentPage.value) {
    isLoading.value = true;
    try {
        const { data } = await submissionsApi.list(pageId, { page, form_key: formKey.value || undefined });
        submissions.value = data.data;
        currentPage.value = data.meta.current_page;
        lastPage.value = data.meta.last_page;
        total.value = data.meta.total;
    } catch (error) {
        ui.showToast(error.response?.data?.message ?? 'Could not load submissions.', 'error');
    } finally {
        isLoading.value = false;
    }
}

async function confirmDelete(submission) {
    if (! window.confirm('Delete this submission? This cannot be undone.')) {
        return;
    }

    try {
        await submissionsApi.remove(submission.id);
        ui.showToast('Submission deleted.', 'success');
        await load();
    } catch (error) {
        ui.showToast(error.response?.data?.message ?? 'Could not delete submission.', 'error');
    }
}

onMounted(async () => {
    const { data } = await pagesApi.show(pageId);
    formKeys.value = (data.data.content?.blocks ?? [])
        .filter((block) => block.type === 'form')
        .map((block) => block.data.form_key);

    await load(1);
});
</script>

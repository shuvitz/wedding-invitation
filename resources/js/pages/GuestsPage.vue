<template>
    <div class="space-y-6">
        <!-- Page header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Guests</h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Each guest gets a personal invitation link that greets them by name on the cover.
                </p>
            </div>
            <Button @click="openModal(null)">New Guest</Button>
        </div>

        <Card>
            <CardContent class="space-y-4 pt-6">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex flex-1 items-center gap-2">
                        <Input v-model="search" type="search" placeholder="Search by name…" class="max-w-xs" @input="onSearchInput" />
                        <select
                            v-model="replyFilter"
                            aria-label="Filter by RSVP"
                            class="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                        >
                            <option value="all">All</option>
                            <option value="replied">Replied</option>
                            <option value="waiting">Not replied yet</option>
                        </select>
                    </div>
                    <p class="text-sm text-muted-foreground">
                        {{ repliedCount }} of {{ guests.length }} replied
                    </p>
                </div>

                <div v-if="isLoading && guests.length === 0" class="py-10 text-center text-sm text-muted-foreground">
                    Loading guests…
                </div>

                <div v-else-if="visibleGuests.length === 0" class="py-10 text-center text-sm text-muted-foreground">
                    {{ search || replyFilter !== 'all' ? 'No guests match your filters.' : 'No guests yet.' }}
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-border text-left text-xs uppercase tracking-wide text-muted-foreground">
                                <th class="pb-2 pr-4 font-medium">Name</th>
                                <th class="pb-2 pr-4 font-medium">Phone</th>
                                <th class="pb-2 pr-4 font-medium">Notes</th>
                                <th class="pb-2 pr-4 font-medium">RSVP</th>
                                <th class="pb-2 font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="guest in visibleGuests" :key="guest.id" class="border-b border-border last:border-0">
                                <td class="py-3 pr-4 font-medium text-foreground">{{ guest.name }}</td>
                                <td class="py-3 pr-4 text-muted-foreground">{{ guest.phone || '—' }}</td>
                                <td class="py-3 pr-4 text-muted-foreground">{{ guest.notes || '—' }}</td>
                                <td class="max-w-xs py-3 pr-4">
                                    <template v-if="guest.reply">
                                        <span class="inline-flex items-center rounded-full bg-emerald-500/10 px-2.5 py-0.5 text-xs font-medium text-emerald-600">
                                            Replied {{ formatDate(guest.reply.created_at) }}
                                        </span>
                                        <p class="mt-1 truncate text-xs text-muted-foreground" :title="replySummary(guest)">{{ replySummary(guest) }}</p>
                                    </template>
                                    <span v-else class="inline-flex items-center rounded-full bg-muted px-2.5 py-0.5 text-xs font-medium text-muted-foreground">
                                        Not yet
                                    </span>
                                </td>
                                <td class="py-3">
                                    <div class="flex items-center gap-2">
                                        <Button variant="outline" size="sm" @click="copyLink(guest)">Copy Link</Button>
                                        <Button as="a" :href="guest.whatsapp_url" target="_blank" rel="noopener noreferrer" variant="outline" size="sm">
                                            WhatsApp
                                        </Button>
                                        <Button variant="outline" size="sm" @click="openModal(guest)">Edit</Button>
                                        <Button variant="outline" size="sm" class="text-destructive" @click="confirmDelete(guest)">Delete</Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>

        <!-- WhatsApp message template -->
        <Card>
            <CardHeader>
                <CardTitle>WhatsApp Message</CardTitle>
            </CardHeader>
            <CardContent>
                <form class="space-y-3" @submit.prevent="saveMessage(messageDraft)">
                    <Textarea id="guest-message" v-model="messageDraft" rows="8" maxlength="2000" aria-describedby="guest-message-help" />
                    <p id="guest-message-help" class="text-xs text-muted-foreground">
                        <code>{nama}</code> becomes the guest's name and <code>{link}</code> their personal invitation link. <code>{link}</code> is required.
                    </p>
                    <p v-if="messageError" class="text-xs text-destructive">{{ messageError }}</p>
                    <div class="flex justify-end gap-2">
                        <Button type="button" variant="outline" :disabled="isSavingMessage" @click="resetMessage">Reset to Default</Button>
                        <Button type="submit" :disabled="isSavingMessage || messageDraft === savedMessage">
                            {{ isSavingMessage ? 'Saving…' : 'Save Message' }}
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>

        <!-- Create / edit modal -->
        <div v-if="showModal" class="fixed inset-0 z-40 flex items-center justify-center bg-black/40 p-4" @click.self="closeModal">
            <Card class="w-full max-w-md">
                <CardHeader>
                    <CardTitle>{{ editingGuest ? 'Edit Guest' : 'New Guest' }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="formErrorMessage" class="mb-4 rounded-lg bg-destructive/10 p-3 text-sm text-destructive">
                        {{ formErrorMessage }}
                    </div>

                    <form class="space-y-4" @submit.prevent="submitForm">
                        <div class="space-y-2">
                            <Label for="guest-name">Name</Label>
                            <Input id="guest-name" v-model="form.name" maxlength="100" placeholder="Budi Santoso & Keluarga" required />
                            <p class="text-xs text-muted-foreground">Shown on the cover exactly as written.</p>
                            <p v-if="errors.name" class="text-xs text-destructive">{{ errors.name[0] }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="guest-phone">Phone (optional)</Label>
                            <Input id="guest-phone" v-model="form.phone" type="tel" maxlength="30" placeholder="+62 812 3456 7890" />
                            <p v-if="errors.phone" class="text-xs text-destructive">{{ errors.phone[0] }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="guest-notes">Notes (optional)</Label>
                            <Input id="guest-notes" v-model="form.notes" maxlength="255" placeholder="Family, office friends…" />
                            <p v-if="errors.notes" class="text-xs text-destructive">{{ errors.notes[0] }}</p>
                        </div>

                        <div class="flex justify-end gap-2 pt-2">
                            <Button type="button" variant="outline" @click="closeModal">Cancel</Button>
                            <Button type="submit" :disabled="isSubmitting">
                                <span v-if="isSubmitting">Saving…</span>
                                <span v-else>{{ editingGuest ? 'Save Changes' : 'Add Guest' }}</span>
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
import { guestsApi } from '@/api/guests.api';
import { useUiStore } from '@/stores/ui.store';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

const ui = useUiStore();

const guests = ref([]);
const isLoading = ref(false);
const search = ref('');

async function fetchGuests() {
    isLoading.value = true;
    try {
        const { data } = await guestsApi.list(search.value ? { search: search.value } : {});
        guests.value = data.data;
        savedMessage.value = data.meta.message_template;
        messageDraft.value ??= savedMessage.value;
    } catch (error) {
        ui.showToast(error.response?.data?.message ?? 'Could not load guests.', 'error');
    } finally {
        isLoading.value = false;
    }
}

let searchTimer;
function onSearchInput() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(fetchGuests, 300);
}

onMounted(fetchGuests);

// ── WhatsApp message ─────────────────────────────────
const savedMessage = ref('');
const messageDraft = ref(null);
const messageError = ref('');
const isSavingMessage = ref(false);

async function saveMessage(message) {
    isSavingMessage.value = true;
    messageError.value = '';
    try {
        await guestsApi.updateMessage(message);
        messageDraft.value = null; // take the saved text from the refetch
        await fetchGuests(); // every guest's WhatsApp link carries the new text
        ui.showToast('WhatsApp message saved.', 'success');
    } catch (error) {
        messageError.value = error.response?.data?.errors?.message?.[0] ?? error.response?.data?.message ?? 'Could not save the message.';
    } finally {
        isSavingMessage.value = false;
    }
}

function resetMessage() {
    if (window.confirm('Replace the message with the default text?')) {
        saveMessage(null);
    }
}

// ── RSVP ─────────────────────────────────────────────
const replyFilter = ref('all');
const repliedCount = computed(() => guests.value.filter((g) => g.reply).length);
const visibleGuests = computed(() => {
    if (replyFilter.value === 'all') return guests.value;
    return guests.value.filter((g) => (replyFilter.value === 'replied') === !!g.reply);
});

// The reply's answers in form order, minus the guest's own name (already in the Name column).
function replySummary(guest) {
    return Object.values(guest.reply.data)
        .filter((value) => value !== null && value !== '' && String(value).toLowerCase() !== guest.name.toLowerCase())
        .join(' · ');
}

const dateFormat = new Intl.DateTimeFormat(undefined, { day: 'numeric', month: 'short' });
const formatDate = (iso) => dateFormat.format(new Date(iso));

async function copyLink(guest) {
    try {
        await navigator.clipboard.writeText(guest.invitation_url);
        ui.showToast(`Link for ${guest.name} copied.`, 'success');
    } catch {
        // Clipboard is blocked (e.g. plain http on a non-localhost host) — let the admin copy it by hand.
        window.prompt('Copy this link:', guest.invitation_url);
    }
}

// ── Create / edit modal ──────────────────────────────
const showModal = ref(false);
const editingGuest = ref(null);
const isSubmitting = ref(false);
const formErrorMessage = ref('');
const errors = ref({});

const form = reactive({ name: '', phone: '', notes: '' });

function openModal(guest) {
    editingGuest.value = guest;
    formErrorMessage.value = '';
    errors.value = {};
    Object.assign(form, { name: guest?.name ?? '', phone: guest?.phone ?? '', notes: guest?.notes ?? '' });
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
}

async function submitForm() {
    isSubmitting.value = true;
    formErrorMessage.value = '';
    errors.value = {};

    try {
        const payload = { name: form.name, phone: form.phone || null, notes: form.notes || null };

        if (editingGuest.value) {
            await guestsApi.update(editingGuest.value.id, payload);
            ui.showToast('Guest updated.', 'success');
        } else {
            await guestsApi.create(payload);
            ui.showToast('Guest added.', 'success');
        }

        showModal.value = false;
        await fetchGuests();
    } catch (error) {
        errors.value = error.response?.data?.errors ?? {};
        formErrorMessage.value = error.response?.data?.message ?? 'Something went wrong. Please try again.';
    } finally {
        isSubmitting.value = false;
    }
}

async function confirmDelete(guest) {
    if (! window.confirm(`Delete "${guest.name}" from the guest list? Their link keeps working, but they won't be listed here.`)) {
        return;
    }

    try {
        await guestsApi.remove(guest.id);
        guests.value = guests.value.filter((g) => g.id !== guest.id);
        ui.showToast('Guest deleted.', 'success');
    } catch (error) {
        ui.showToast(error.response?.data?.message ?? 'Could not delete this guest.', 'error');
    }
}
</script>

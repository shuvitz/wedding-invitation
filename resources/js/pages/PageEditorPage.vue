<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-foreground">{{ isNew ? 'New Page' : 'Edit Page' }}</h1>
                <p v-if="!isNew" class="mt-1 flex items-center gap-2 text-sm text-muted-foreground">
                    <span
                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                        :class="currentStatus === 'published' ? 'bg-emerald-500/10 text-emerald-600' : 'bg-muted text-muted-foreground'"
                    >
                        {{ currentStatus === 'published' ? 'Published' : 'Draft' }}
                    </span>
                    <a v-if="publicUrl" :href="publicUrl" target="_blank" rel="noopener" class="text-primary hover:underline">View live</a>
                </p>
            </div>
            <div class="flex gap-2">
                <Button variant="outline" @click="router.push({ name: 'admin.pages' })">Back to Pages</Button>
                <Button v-if="isAdmin && !isNew && hasFormBlock" variant="outline" @click="router.push({ name: 'admin.pages.submissions', params: { id: pageId } })">Submissions</Button>
                <Button v-if="isAdmin && !isNew" variant="outline" @click="togglePublish">
                    {{ currentStatus === 'published' ? 'Unpublish' : 'Publish' }}
                </Button>
            </div>
        </div>

        <div v-if="isLoading" class="py-10 text-center text-sm text-muted-foreground">Loading page…</div>

        <template v-else>
            <div v-if="!canEdit" class="rounded-lg bg-muted p-3 text-sm text-muted-foreground">
                This page is published — only an admin can edit it. You can still view its content below.
            </div>
            <div v-if="formErrorMessage" class="rounded-lg bg-destructive/10 p-3 text-sm text-destructive">
                {{ formErrorMessage }}
            </div>

            <form class="space-y-6" @submit.prevent="handleSave">
                <!-- Page details -->
                <Card>
                    <CardHeader>
                        <CardTitle>Page Details</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="space-y-2">
                            <Label for="page-title">Title</Label>
                            <Input id="page-title" v-model="form.title" required :disabled="!canEdit" />
                            <p v-if="fieldError('title')" class="text-xs text-destructive">{{ fieldError('title') }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="page-slug">Slug</Label>
                            <div class="flex items-center gap-1 text-sm text-muted-foreground">
                                <span>/</span>
                                <Input id="page-slug" v-model="form.slug" :placeholder="isNew ? 'auto-generated from title if left blank' : ''" :disabled="!canEdit" />
                            </div>
                            <p v-if="fieldError('slug')" class="text-xs text-destructive">{{ fieldError('slug') }}</p>
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="page-meta-title">Meta title</Label>
                                <Input id="page-meta-title" v-model="form.meta_title" placeholder="Falls back to the title" :disabled="!canEdit" />
                                <p v-if="fieldError('meta_title')" class="text-xs text-destructive">{{ fieldError('meta_title') }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label for="page-meta-description">Meta description</Label>
                                <Input id="page-meta-description" v-model="form.meta_description" :disabled="!canEdit" />
                                <p v-if="fieldError('meta_description')" class="text-xs text-destructive">{{ fieldError('meta_description') }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Blocks -->
                <Card>
                    <CardHeader>
                        <CardTitle>Blocks</CardTitle>
                        <CardDescription>The sections that make up this page, in order.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div v-if="form.blocks.length === 0" class="rounded-lg border border-dashed border-border p-6 text-center text-sm text-muted-foreground">
                            No blocks yet.
                        </div>

                        <div v-for="(block, index) in form.blocks" :key="index" class="space-y-3 rounded-lg border border-border p-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-semibold text-foreground">{{ blockTypeLabels[block.type] ?? block.type }}</span>
                                <div v-if="canEdit" class="flex gap-1">
                                    <Button type="button" variant="outline" size="xs" :disabled="index === 0" @click="moveBlock(index, -1)">↑</Button>
                                    <Button type="button" variant="outline" size="xs" :disabled="index === form.blocks.length - 1" @click="moveBlock(index, 1)">↓</Button>
                                    <Button type="button" variant="outline" size="xs" class="text-destructive" @click="removeBlock(index)">Remove</Button>
                                </div>
                            </div>

                            <!-- Hero -->
                            <template v-if="block.type === 'hero'">
                                <div class="space-y-2">
                                    <Label>Title</Label>
                                    <Input v-model="block.data.title" :disabled="!canEdit" />
                                    <p v-if="fieldError(`content.blocks.${index}.data.title`)" class="text-xs text-destructive">{{ fieldError(`content.blocks.${index}.data.title`) }}</p>
                                </div>
                                <div class="space-y-2">
                                    <Label>Subtitle</Label>
                                    <Input v-model="block.data.subtitle" :disabled="!canEdit" />
                                </div>
                                <MediaPicker v-model="block.data.image_media_id" label="Image" :disabled="!canEdit" />
                            </template>

                            <!-- Rich Text -->
                            <template v-else-if="block.type === 'rich_text'">
                                <div class="space-y-2">
                                    <Label>Title</Label>
                                    <Input v-model="block.data.title" :disabled="!canEdit" />
                                </div>
                                <div class="space-y-2">
                                    <Label>Body</Label>
                                    <Textarea v-model="block.data.body" rows="5" :disabled="!canEdit" />
                                    <p class="text-xs text-muted-foreground">Plain text only — line breaks are preserved, HTML is not interpreted.</p>
                                    <p v-if="fieldError(`content.blocks.${index}.data.body`)" class="text-xs text-destructive">{{ fieldError(`content.blocks.${index}.data.body`) }}</p>
                                </div>
                            </template>

                            <!-- Image -->
                            <template v-else-if="block.type === 'image'">
                                <MediaPicker v-model="block.data.media_id" label="Image" :disabled="!canEdit" />
                                <p v-if="fieldError(`content.blocks.${index}.data.media_id`)" class="text-xs text-destructive">{{ fieldError(`content.blocks.${index}.data.media_id`) }}</p>
                                <div class="space-y-2">
                                    <Label>Alt text</Label>
                                    <Input v-model="block.data.alt_text" :disabled="!canEdit" />
                                </div>
                            </template>

                            <!-- CTA -->
                            <template v-else-if="block.type === 'cta'">
                                <div class="space-y-2">
                                    <Label>Title</Label>
                                    <Input v-model="block.data.title" :disabled="!canEdit" />
                                </div>
                                <div class="space-y-2">
                                    <Label>Description</Label>
                                    <Input v-model="block.data.description" :disabled="!canEdit" />
                                </div>
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div class="space-y-2">
                                        <Label>Button label</Label>
                                        <Input v-model="block.data.button_label" :disabled="!canEdit" />
                                    </div>
                                    <div class="space-y-2">
                                        <Label>URL</Label>
                                        <Input v-model="block.data.url" placeholder="/contact or https://…" :disabled="!canEdit" />
                                        <p v-if="fieldError(`content.blocks.${index}.data.url`)" class="text-xs text-destructive">{{ fieldError(`content.blocks.${index}.data.url`) }}</p>
                                    </div>
                                </div>
                            </template>

                            <!-- Menu -->
                            <template v-else-if="block.type === 'menu'">
                                <div class="space-y-2">
                                    <Label>Title</Label>
                                    <Input v-model="block.data.title" placeholder="Menu" :disabled="!canEdit" />
                                </div>

                                <div v-for="(item, itemIndex) in block.data.items" :key="itemIndex" class="space-y-3 rounded-md border border-border p-3">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Item {{ itemIndex + 1 }}</span>
                                        <Button v-if="canEdit" type="button" variant="outline" size="xs" class="text-destructive" @click="removeMenuItem(block, itemIndex)">Remove</Button>
                                    </div>
                                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                        <div class="space-y-2">
                                            <Label>Name</Label>
                                            <Input v-model="item.name" :disabled="!canEdit" />
                                            <p v-if="fieldError(`content.blocks.${index}.data.items.${itemIndex}.name`)" class="text-xs text-destructive">{{ fieldError(`content.blocks.${index}.data.items.${itemIndex}.name`) }}</p>
                                        </div>
                                        <div class="space-y-2">
                                            <Label>Price</Label>
                                            <Input v-model="item.price" placeholder="15000" :disabled="!canEdit" />
                                            <p v-if="fieldError(`content.blocks.${index}.data.items.${itemIndex}.price`)" class="text-xs text-destructive">{{ fieldError(`content.blocks.${index}.data.items.${itemIndex}.price`) }}</p>
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <Label>Description</Label>
                                        <Input v-model="item.description" :disabled="!canEdit" />
                                    </div>
                                    <MediaPicker v-model="item.image_media_id" label="Image" :disabled="!canEdit" />
                                </div>

                                <Button v-if="canEdit" type="button" variant="outline" size="sm" @click="addMenuItem(block)">+ Add Menu Item</Button>
                            </template>

                            <!-- Gallery -->
                            <template v-else-if="block.type === 'gallery'">
                                <div class="space-y-2">
                                    <Label>Title</Label>
                                    <Input v-model="block.data.title" placeholder="Gallery" :disabled="!canEdit" />
                                </div>

                                <div v-if="block.data.items.length > 0" class="grid grid-cols-3 gap-3 sm:grid-cols-4">
                                    <div v-for="(item, itemIndex) in block.data.items" :key="itemIndex" class="space-y-1">
                                        <div class="aspect-square overflow-hidden rounded-md border border-border bg-muted">
                                            <img v-if="item._previewUrl" :src="item._previewUrl" :alt="item.alt_text || ''" class="h-full w-full object-cover">
                                        </div>
                                        <Input v-model="item.alt_text" placeholder="Alt text" class="text-xs" :disabled="!canEdit" />
                                        <Button v-if="canEdit" type="button" variant="outline" size="xs" class="w-full text-destructive" @click="removeGalleryItem(block, itemIndex)">Remove</Button>
                                    </div>
                                </div>

                                <MediaPicker v-if="canEdit" multiple label="Images" @select="(selected) => addGalleryItems(block, selected)" />
                                <p v-if="fieldError(`content.blocks.${index}.data.items`)" class="text-xs text-destructive">{{ fieldError(`content.blocks.${index}.data.items`) }}</p>
                            </template>

                            <!-- Schedule -->
                            <template v-else-if="block.type === 'schedule'">
                                <div class="space-y-2">
                                    <Label>Title</Label>
                                    <Input v-model="block.data.title" placeholder="Schedule" :disabled="!canEdit" />
                                </div>

                                <div v-for="(item, itemIndex) in block.data.items" :key="itemIndex" class="space-y-3 rounded-md border border-border p-3">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Event {{ itemIndex + 1 }}</span>
                                        <Button v-if="canEdit" type="button" variant="outline" size="xs" class="text-destructive" @click="removeScheduleItem(block, itemIndex)">Remove</Button>
                                    </div>
                                    <div class="space-y-2">
                                        <Label>Event Title</Label>
                                        <Input v-model="item.title" placeholder="Ceremony" :disabled="!canEdit" />
                                        <p v-if="fieldError(`content.blocks.${index}.data.items.${itemIndex}.title`)" class="text-xs text-destructive">{{ fieldError(`content.blocks.${index}.data.items.${itemIndex}.title`) }}</p>
                                    </div>
                                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                        <div class="space-y-2">
                                            <Label>Date</Label>
                                            <Input v-model="item.date" type="date" :disabled="!canEdit" />
                                            <p v-if="fieldError(`content.blocks.${index}.data.items.${itemIndex}.date`)" class="text-xs text-destructive">{{ fieldError(`content.blocks.${index}.data.items.${itemIndex}.date`) }}</p>
                                        </div>
                                        <div class="space-y-2">
                                            <Label>Start Time</Label>
                                            <Input v-model="item.start_time" type="time" :disabled="!canEdit" />
                                            <p v-if="fieldError(`content.blocks.${index}.data.items.${itemIndex}.start_time`)" class="text-xs text-destructive">{{ fieldError(`content.blocks.${index}.data.items.${itemIndex}.start_time`) }}</p>
                                        </div>
                                        <div class="space-y-2">
                                            <Label>End Time</Label>
                                            <Input v-model="item.end_time" type="time" :disabled="!canEdit" />
                                            <p v-if="fieldError(`content.blocks.${index}.data.items.${itemIndex}.end_time`)" class="text-xs text-destructive">{{ fieldError(`content.blocks.${index}.data.items.${itemIndex}.end_time`) }}</p>
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <Label>Venue</Label>
                                        <Input v-model="item.venue" :disabled="!canEdit" />
                                    </div>
                                    <div class="space-y-2">
                                        <Label>Address</Label>
                                        <Input v-model="item.address" :disabled="!canEdit" />
                                    </div>
                                    <div class="space-y-2">
                                        <Label>Maps URL</Label>
                                        <Input v-model="item.maps_url" placeholder="https://maps.app.goo.gl/…" :disabled="!canEdit" />
                                        <p v-if="fieldError(`content.blocks.${index}.data.items.${itemIndex}.maps_url`)" class="text-xs text-destructive">{{ fieldError(`content.blocks.${index}.data.items.${itemIndex}.maps_url`) }}</p>
                                    </div>
                                </div>

                                <Button v-if="canEdit" type="button" variant="outline" size="sm" @click="addScheduleItem(block)">+ Add Event</Button>
                            </template>

                            <!-- Form -->
                            <template v-else-if="block.type === 'form'">
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <div class="space-y-2">
                                        <Label>Form Key</Label>
                                        <Input v-model="block.data.form_key" placeholder="rsvp" :disabled="!canEdit" />
                                        <p class="text-xs text-muted-foreground">Lowercase, used in the submit URL. Changing it later splits old and new submissions.</p>
                                        <p v-if="fieldError(`content.blocks.${index}.data.form_key`)" class="text-xs text-destructive">{{ fieldError(`content.blocks.${index}.data.form_key`) }}</p>
                                    </div>
                                    <div class="space-y-2">
                                        <Label>Title</Label>
                                        <Input v-model="block.data.title" placeholder="RSVP" :disabled="!canEdit" />
                                    </div>
                                    <div class="space-y-2">
                                        <Label>Submit Button Label</Label>
                                        <Input v-model="block.data.submit_label" placeholder="Send" :disabled="!canEdit" />
                                    </div>
                                    <div class="space-y-2">
                                        <Label>Success Message</Label>
                                        <Input v-model="block.data.success_message" placeholder="Thank you!" :disabled="!canEdit" />
                                    </div>
                                </div>

                                <div v-for="(field, fieldIndex) in block.data.fields" :key="fieldIndex" class="space-y-3 rounded-md border border-border p-3">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Field {{ fieldIndex + 1 }}</span>
                                        <Button v-if="canEdit" type="button" variant="outline" size="xs" class="text-destructive" @click="removeFormField(block, fieldIndex)">Remove</Button>
                                    </div>
                                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                        <div class="space-y-2">
                                            <Label>Label</Label>
                                            <Input v-model="field.label" placeholder="Full name" :disabled="!canEdit" />
                                            <p v-if="fieldError(`content.blocks.${index}.data.fields.${fieldIndex}.label`)" class="text-xs text-destructive">{{ fieldError(`content.blocks.${index}.data.fields.${fieldIndex}.label`) }}</p>
                                        </div>
                                        <div class="space-y-2">
                                            <Label>Name</Label>
                                            <Input v-model="field.name" placeholder="full_name" :disabled="!canEdit" />
                                            <p v-if="fieldError(`content.blocks.${index}.data.fields.${fieldIndex}.name`)" class="text-xs text-destructive">{{ fieldError(`content.blocks.${index}.data.fields.${fieldIndex}.name`) }}</p>
                                        </div>
                                        <div class="space-y-2">
                                            <Label>Type</Label>
                                            <select
                                                v-model="field.type"
                                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                                                :disabled="!canEdit"
                                            >
                                                <option value="text">Text</option>
                                                <option value="textarea">Long text</option>
                                                <option value="number">Number</option>
                                                <option value="select">Dropdown</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div v-if="field.type === 'select'" class="space-y-2">
                                        <Label>Options (one per line)</Label>
                                        <Textarea
                                            :model-value="(field.options || []).join('\n')"
                                            rows="3"
                                            :disabled="!canEdit"
                                            @change="field.options = $event.target.value.split('\n').map((option) => option.trim()).filter(Boolean)"
                                        />
                                        <p v-if="fieldError(`content.blocks.${index}.data.fields.${fieldIndex}.options`)" class="text-xs text-destructive">{{ fieldError(`content.blocks.${index}.data.fields.${fieldIndex}.options`) }}</p>
                                    </div>
                                    <label class="flex items-center gap-2 text-sm">
                                        <Checkbox v-model="field.required" :disabled="!canEdit" />
                                        Required
                                    </label>
                                </div>

                                <p v-if="fieldError(`content.blocks.${index}.data.fields`)" class="text-xs text-destructive">{{ fieldError(`content.blocks.${index}.data.fields`) }}</p>
                                <Button v-if="canEdit" type="button" variant="outline" size="sm" @click="addFormField(block)">+ Add Field</Button>
                            </template>

                            <!-- Couple (always exactly two people, in display order) -->
                            <template v-else-if="block.type === 'couple'">
                                <div class="space-y-2">
                                    <Label>Title</Label>
                                    <Input v-model="block.data.title" placeholder="Mempelai" :disabled="!canEdit" />
                                </div>

                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <div v-for="(item, itemIndex) in block.data.items" :key="itemIndex" class="space-y-3 rounded-md border border-border p-3">
                                        <span class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Person {{ itemIndex + 1 }}</span>
                                        <div class="space-y-2">
                                            <Label>Name</Label>
                                            <Input v-model="item.name" placeholder="Raka Pratama, S.T." :disabled="!canEdit" />
                                            <p v-if="fieldError(`content.blocks.${index}.data.items.${itemIndex}.name`)" class="text-xs text-destructive">{{ fieldError(`content.blocks.${index}.data.items.${itemIndex}.name`) }}</p>
                                        </div>
                                        <div class="space-y-2">
                                            <Label>Parents</Label>
                                            <Textarea v-model="item.parents" rows="2" placeholder="Putra pertama dari Bapak … & Ibu …" :disabled="!canEdit" />
                                        </div>
                                        <div class="space-y-2">
                                            <Label>Instagram username</Label>
                                            <Input v-model="item.instagram" placeholder="rakapratama (without @)" :disabled="!canEdit" />
                                            <p v-if="fieldError(`content.blocks.${index}.data.items.${itemIndex}.instagram`)" class="text-xs text-destructive">{{ fieldError(`content.blocks.${index}.data.items.${itemIndex}.instagram`) }}</p>
                                        </div>
                                        <MediaPicker v-model="item.image_media_id" label="Photo" :disabled="!canEdit" />
                                    </div>
                                </div>
                                <p v-if="fieldError(`content.blocks.${index}.data.items`)" class="text-xs text-destructive">{{ fieldError(`content.blocks.${index}.data.items`) }}</p>
                            </template>
                        </div>

                        <div v-if="canEdit" class="flex flex-wrap gap-2 border-t border-border pt-4">
                            <span class="self-center text-sm text-muted-foreground">Add block:</span>
                            <Button type="button" variant="outline" size="sm" @click="addBlock('hero')">Hero</Button>
                            <Button type="button" variant="outline" size="sm" @click="addBlock('rich_text')">Rich Text</Button>
                            <Button type="button" variant="outline" size="sm" @click="addBlock('image')">Image</Button>
                            <Button type="button" variant="outline" size="sm" @click="addBlock('cta')">CTA</Button>
                            <Button type="button" variant="outline" size="sm" @click="addBlock('menu')">Menu</Button>
                            <Button type="button" variant="outline" size="sm" @click="addBlock('gallery')">Gallery</Button>
                            <Button type="button" variant="outline" size="sm" @click="addBlock('schedule')">Schedule</Button>
                            <Button type="button" variant="outline" size="sm" @click="addBlock('form')">Form</Button>
                            <Button type="button" variant="outline" size="sm" @click="addBlock('couple')">Couple</Button>
                        </div>
                    </CardContent>
                </Card>

                <div v-if="canEdit" class="flex justify-end">
                    <Button type="submit" :disabled="isSaving">
                        <span v-if="isSaving">Saving…</span>
                        <span v-else>{{ isNew ? 'Create Page' : 'Save Changes' }}</span>
                    </Button>
                </div>
            </form>
        </template>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth.store';
import { usePagesStore } from '@/stores/pages.store';
import { useUiStore } from '@/stores/ui.store';
import { mediaApi } from '@/api/media.api';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Checkbox } from '@/components/ui/checkbox';
import MediaPicker from '@/components/MediaPicker.vue';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();
const pagesStore = usePagesStore();
const ui = useUiStore();

const pageId = computed(() => (route.params.id ? Number(route.params.id) : null));
const isNew = computed(() => pageId.value === null);
const isAdmin = computed(() => auth.user?.role === 'admin');

const isLoading = ref(!isNew.value);
const isSaving = ref(false);
const currentStatus = ref('draft');
const publicUrl = ref(null);
const formErrorMessage = ref('');

const canEdit = computed(() => isAdmin.value || currentStatus.value === 'draft');
const hasFormBlock = computed(() => form.blocks.some((block) => block.type === 'form'));

const form = reactive({
    title: '',
    slug: '',
    meta_title: '',
    meta_description: '',
    blocks: [],
});

const blockTypeLabels = {
    hero: 'Hero',
    rich_text: 'Rich Text',
    image: 'Image',
    cta: 'CTA',
    menu: 'Menu',
    gallery: 'Gallery',
    schedule: 'Schedule',
    form: 'Form',
    couple: 'Couple',
};

function defaultBlockData(type) {
    switch (type) {
        case 'hero':
            return { title: '', subtitle: '', image_media_id: null };
        case 'rich_text':
            return { title: '', body: '' };
        case 'image':
            return { media_id: null, alt_text: '' };
        case 'cta':
            return { title: '', description: '', button_label: '', url: '' };
        case 'menu':
            return { title: '', items: [] };
        case 'gallery':
            return { title: '', items: [] };
        case 'schedule':
            return { title: '', items: [] };
        case 'form':
            return { form_key: '', title: '', submit_label: '', success_message: '', fields: [] };
        case 'couple': {
            const person = () => ({ name: '', parents: '', instagram: '', image_media_id: null });
            return { title: '', items: [person(), person()] };
        }
        default:
            return {};
    }
}

function addBlock(type) {
    form.blocks.push({ type, data: defaultBlockData(type) });
}

// ── Menu items ─────────────────────────────────────────
function addMenuItem(block) {
    block.data.items.push({ name: '', description: '', price: '', image_media_id: null });
}

function removeMenuItem(block, itemIndex) {
    block.data.items.splice(itemIndex, 1);
}

// ── Schedule items ─────────────────────────────────────
function addScheduleItem(block) {
    block.data.items.push({ title: '', date: '', start_time: '', end_time: '', venue: '', address: '', maps_url: '' });
}

function removeScheduleItem(block, itemIndex) {
    block.data.items.splice(itemIndex, 1);
}

// ── Form fields ────────────────────────────────────────
function addFormField(block) {
    block.data.fields.push({ name: '', label: '', type: 'text', required: false, options: [] });
}

function removeFormField(block, fieldIndex) {
    block.data.fields.splice(fieldIndex, 1);
}

// ── Gallery items ──────────────────────────────────────
// `_previewUrl` is a UI-only convenience (not part of the validated
// schema — the backend simply ignores it) so already-picked thumbnails
// render without a second round-trip per item.
function addGalleryItems(block, selected) {
    for (const item of selected) {
        block.data.items.push({ media_id: item.id, alt_text: '', _previewUrl: item.url });
    }
}

function removeGalleryItem(block, itemIndex) {
    block.data.items.splice(itemIndex, 1);
}

async function resolveGalleryPreviews() {
    for (const block of form.blocks) {
        if (block.type !== 'gallery') {
            continue;
        }
        for (const item of block.data.items ?? []) {
            if (item.media_id && !item._previewUrl) {
                try {
                    const { data } = await mediaApi.show(item.media_id);
                    item._previewUrl = data.data.url;
                } catch {
                    item._previewUrl = null;
                }
            }
        }
    }
}

function removeBlock(index) {
    form.blocks.splice(index, 1);
}

function moveBlock(index, direction) {
    const newIndex = index + direction;
    if (newIndex < 0 || newIndex >= form.blocks.length) {
        return;
    }
    const [block] = form.blocks.splice(index, 1);
    form.blocks.splice(newIndex, 0, block);
}

function fieldError(key) {
    return pagesStore.errors?.[key]?.[0];
}

onMounted(async () => {
    if (isNew.value) {
        return;
    }

    isLoading.value = true;
    try {
        const page = await pagesStore.fetchPage(pageId.value);
        form.title = page.title;
        form.slug = page.slug;
        form.meta_title = page.meta_title ?? '';
        form.meta_description = page.meta_description ?? '';
        form.blocks = page.content?.blocks ?? [];
        currentStatus.value = page.status;
        publicUrl.value = page.public_url;
        await resolveGalleryPreviews();
    } finally {
        isLoading.value = false;
    }
});

async function handleSave() {
    isSaving.value = true;
    formErrorMessage.value = '';

    const payload = {
        title: form.title,
        slug: form.slug || undefined,
        meta_title: form.meta_title || null,
        meta_description: form.meta_description || null,
        content: { blocks: form.blocks },
    };

    try {
        if (isNew.value) {
            const created = await pagesStore.createPage(payload);
            ui.showToast('Page created.', 'success');
            router.push({ name: 'admin.pages.edit', params: { id: created.id } });
        } else {
            const updated = await pagesStore.updatePage(pageId.value, payload);
            form.slug = updated.slug;
            currentStatus.value = updated.status;
            ui.showToast('Page saved.', 'success');
        }
    } catch (error) {
        formErrorMessage.value = error.response?.data?.message ?? 'Something went wrong. Please try again.';
    } finally {
        isSaving.value = false;
    }
}

async function togglePublish() {
    const newStatus = currentStatus.value === 'published' ? 'draft' : 'published';

    try {
        const updated = await pagesStore.setStatus(pageId.value, newStatus);
        currentStatus.value = updated.status;
        publicUrl.value = updated.public_url;
        ui.showToast(newStatus === 'published' ? 'Page published.' : 'Page unpublished.', 'success');
    } catch (error) {
        ui.showToast(error.response?.data?.message ?? 'Could not update status.', 'error');
    }
}
</script>

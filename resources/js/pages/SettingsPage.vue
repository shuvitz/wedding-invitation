<template>
    <div class="space-y-6">
        <!-- Page header -->
        <div>
            <h1 class="text-2xl font-bold text-foreground">Settings</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                <span v-if="isAdmin">Website-wide configuration for the whole site.</span>
                <span v-else>Website-wide configuration. Only admins can make changes here.</span>
            </p>
        </div>

        <div v-if="settingsStore.isLoading" class="py-10 text-center text-sm text-muted-foreground">
            Loading settings…
        </div>

        <form v-else class="space-y-6" @submit.prevent="handleSave">
            <!-- Site Identity -->
            <Card>
                <CardHeader>
                    <CardTitle>Site Identity</CardTitle>
                    <CardDescription>How your website identifies itself.</CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="space-y-2">
                        <Label for="site-name">Site name</Label>
                        <Input id="site-name" v-model="settingsStore.siteIdentity.name" :disabled="!isAdmin" />
                        <p v-if="fieldError('site_identity.name')" class="text-xs text-destructive">{{ fieldError('site_identity.name') }}</p>
                    </div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <MediaPicker
                            v-model="settingsStore.siteIdentity.logo_media_id"
                            v-model:preview-url="settingsStore.siteIdentity.logo_url"
                            label="Logo"
                            :disabled="!isAdmin"
                        />
                        <MediaPicker
                            v-model="settingsStore.siteIdentity.favicon_media_id"
                            v-model:preview-url="settingsStore.siteIdentity.favicon_url"
                            label="Favicon"
                            :disabled="!isAdmin"
                        />
                    </div>
                    <p v-if="fieldError('site_identity.logo_media_id')" class="text-xs text-destructive">{{ fieldError('site_identity.logo_media_id') }}</p>
                    <p v-if="fieldError('site_identity.favicon_media_id')" class="text-xs text-destructive">{{ fieldError('site_identity.favicon_media_id') }}</p>
                </CardContent>
            </Card>

            <!-- Contact -->
            <Card>
                <CardHeader>
                    <CardTitle>Contact</CardTitle>
                    <CardDescription>How visitors can reach you.</CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="contact-email">Email</Label>
                            <Input id="contact-email" v-model="settingsStore.contact.email" type="email" :disabled="!isAdmin" />
                            <p v-if="fieldError('contact.email')" class="text-xs text-destructive">{{ fieldError('contact.email') }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="contact-phone">Phone</Label>
                            <Input id="contact-phone" v-model="settingsStore.contact.phone" :disabled="!isAdmin" />
                            <p v-if="fieldError('contact.phone')" class="text-xs text-destructive">{{ fieldError('contact.phone') }}</p>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <Label for="contact-address">Address</Label>
                        <Input id="contact-address" v-model="settingsStore.contact.address" :disabled="!isAdmin" />
                        <p v-if="fieldError('contact.address')" class="text-xs text-destructive">{{ fieldError('contact.address') }}</p>
                    </div>
                </CardContent>
            </Card>

            <!-- Social -->
            <Card>
                <CardHeader>
                    <CardTitle>Social Media</CardTitle>
                    <CardDescription>Handles or links for your social profiles.</CardDescription>
                </CardHeader>
                <CardContent class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="social-instagram">Instagram</Label>
                        <Input id="social-instagram" v-model="settingsStore.social.instagram" :disabled="!isAdmin" />
                        <p v-if="fieldError('social.instagram')" class="text-xs text-destructive">{{ fieldError('social.instagram') }}</p>
                    </div>
                    <div class="space-y-2">
                        <Label for="social-facebook">Facebook</Label>
                        <Input id="social-facebook" v-model="settingsStore.social.facebook" :disabled="!isAdmin" />
                        <p v-if="fieldError('social.facebook')" class="text-xs text-destructive">{{ fieldError('social.facebook') }}</p>
                    </div>
                    <div class="space-y-2">
                        <Label for="social-tiktok">TikTok</Label>
                        <Input id="social-tiktok" v-model="settingsStore.social.tiktok" :disabled="!isAdmin" />
                        <p v-if="fieldError('social.tiktok')" class="text-xs text-destructive">{{ fieldError('social.tiktok') }}</p>
                    </div>
                    <div class="space-y-2">
                        <Label for="social-linkedin">LinkedIn</Label>
                        <Input id="social-linkedin" v-model="settingsStore.social.linkedin" :disabled="!isAdmin" />
                        <p v-if="fieldError('social.linkedin')" class="text-xs text-destructive">{{ fieldError('social.linkedin') }}</p>
                    </div>
                    <div class="space-y-2">
                        <Label for="social-whatsapp">WhatsApp</Label>
                        <Input id="social-whatsapp" v-model="settingsStore.social.whatsapp" placeholder="https://wa.me/62812xxxxxxx" :disabled="!isAdmin" />
                        <p v-if="fieldError('social.whatsapp')" class="text-xs text-destructive">{{ fieldError('social.whatsapp') }}</p>
                    </div>
                </CardContent>
            </Card>

            <!-- SEO -->
            <Card>
                <CardHeader>
                    <CardTitle>SEO</CardTitle>
                    <CardDescription>Defaults used by search engines and link previews.</CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="space-y-2">
                        <Label for="seo-title">Meta title</Label>
                        <Input id="seo-title" v-model="settingsStore.seo.meta_title" :disabled="!isAdmin" />
                        <p v-if="fieldError('seo.meta_title')" class="text-xs text-destructive">{{ fieldError('seo.meta_title') }}</p>
                    </div>
                    <div class="space-y-2">
                        <Label for="seo-description">Meta description</Label>
                        <Input id="seo-description" v-model="settingsStore.seo.meta_description" :disabled="!isAdmin" />
                        <p v-if="fieldError('seo.meta_description')" class="text-xs text-destructive">{{ fieldError('seo.meta_description') }}</p>
                    </div>
                </CardContent>
            </Card>

            <div v-if="isAdmin" class="flex justify-end">
                <Button type="submit" :disabled="settingsStore.isSaving">
                    <span v-if="settingsStore.isSaving">Saving…</span>
                    <span v-else>Save Settings</span>
                </Button>
            </div>
        </form>
    </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth.store';
import { useSettingsStore } from '@/stores/settings.store';
import { useUiStore } from '@/stores/ui.store';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import MediaPicker from '@/components/MediaPicker.vue';

const auth = useAuthStore();
const settingsStore = useSettingsStore();
const ui = useUiStore();

const isAdmin = computed(() => auth.user?.role === 'admin');

// Validation errors come back keyed by dot-path (e.g. "contact.email"),
// matching the nested rule names in UpdateSettingRequest.
function fieldError(key) {
    return settingsStore.errors?.[key]?.[0];
}

onMounted(() => {
    settingsStore.fetchSettings();
});

async function handleSave() {
    try {
        await settingsStore.saveSettings();
        ui.showToast('Settings saved.', 'success');
    } catch (error) {
        ui.showToast(error.response?.data?.message ?? 'Could not save settings.', 'error');
    }
}
</script>

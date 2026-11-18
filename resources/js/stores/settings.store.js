import { defineStore } from 'pinia';
import { ref } from 'vue';
import { settingsApi } from '../api/settings.api';

export const useSettingsStore = defineStore('settings', () => {
    // ── State ──────────────────────────────────────────
    // logo/favicon are Media references: `*_media_id` is what gets saved,
    // `*_url` is only for the MediaPicker preview and is never sent back.
    const siteIdentity = ref({
        name: '',
        logo_media_id: null,
        logo_url: null,
        favicon_media_id: null,
        favicon_url: null,
    });
    const contact = ref({ email: '', phone: '', address: '' });
    const social = ref({ instagram: '', facebook: '', tiktok: '', linkedin: '', whatsapp: '' });
    const seo = ref({ meta_title: '', meta_description: '' });

    const isLoading = ref(false);
    const isSaving = ref(false);
    const errors = ref({});

    // ── Actions ────────────────────────────────────────

    function applyGroups(data) {
        siteIdentity.value = {
            name: data.site_identity?.name ?? '',
            logo_media_id: data.site_identity?.logo?.id ?? null,
            logo_url: data.site_identity?.logo?.url ?? null,
            favicon_media_id: data.site_identity?.favicon?.id ?? null,
            favicon_url: data.site_identity?.favicon?.url ?? null,
        };
        contact.value = { email: '', phone: '', address: '', ...data.contact };
        social.value = { instagram: '', facebook: '', tiktok: '', linkedin: '', whatsapp: '', ...data.social };
        seo.value = { meta_title: '', meta_description: '', ...data.seo };
    }

    async function fetchSettings() {
        isLoading.value = true;
        try {
            const { data } = await settingsApi.show();
            applyGroups(data.data);
        } finally {
            isLoading.value = false;
        }
    }

    async function saveSettings() {
        isSaving.value = true;
        errors.value = {};
        try {
            const { data } = await settingsApi.update({
                site_identity: {
                    name: siteIdentity.value.name,
                    logo_media_id: siteIdentity.value.logo_media_id,
                    favicon_media_id: siteIdentity.value.favicon_media_id,
                },
                contact: contact.value,
                social: social.value,
                seo: seo.value,
            });
            applyGroups(data.data);
        } catch (error) {
            if (error.response?.status === 422) {
                errors.value = error.response.data.errors ?? {};
            }
            throw error;
        } finally {
            isSaving.value = false;
        }
    }

    return {
        siteIdentity,
        contact,
        social,
        seo,
        isLoading,
        isSaving,
        errors,
        fetchSettings,
        saveSettings,
    };
});

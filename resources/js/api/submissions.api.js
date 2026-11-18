import http from './http';

export const submissionsApi = {
    list(pageId, params = {}) {
        return http.get(`/pages/${pageId}/submissions`, { params });
    },

    // A plain link (not XHR) so the browser handles the file download;
    // the session cookie authenticates it like any admin API call.
    exportUrl(pageId, formKey = '') {
        const query = formKey ? `?form_key=${encodeURIComponent(formKey)}` : '';
        return `/api/pages/${pageId}/submissions/export${query}`;
    },

    remove(id) {
        return http.delete(`/submissions/${id}`);
    },
};

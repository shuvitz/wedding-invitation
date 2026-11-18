import http from './http';

export const guestsApi = {
    list(params = {}) {
        return http.get('/guests', { params });
    },

    create(data) {
        return http.post('/guests', data);
    },

    update(id, data) {
        return http.put(`/guests/${id}`, data);
    },

    remove(id) {
        return http.delete(`/guests/${id}`);
    },

    // A plain link (not XHR) so the browser handles the download; the session cookie authenticates it.
    exportUrl: '/api/guests/export',

    import(file) {
        const form = new FormData();
        form.append('file', file);
        return http.post('/guests/import', form);
    },

    // null restores the default text.
    updateMessage(message) {
        return http.put('/guest-message', { message });
    },
};

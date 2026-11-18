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

    // null restores the default text.
    updateMessage(message) {
        return http.put('/guest-message', { message });
    },
};

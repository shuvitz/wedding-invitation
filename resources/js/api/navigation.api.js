import http from './http';

export const navigationApi = {
    list() {
        return http.get('/navigation');
    },

    create(data) {
        return http.post('/navigation', data);
    },

    update(id, data) {
        return http.put(`/navigation/${id}`, data);
    },

    setVisibility(id, visible) {
        return http.patch(`/navigation/${id}/visibility`, { visible });
    },

    remove(id) {
        return http.delete(`/navigation/${id}`);
    },
};

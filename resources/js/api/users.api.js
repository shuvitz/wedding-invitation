import http from './http';

export const usersApi = {
    /**
     * List users — supports { search, role, page, per_page }.
     */
    list(params = {}) {
        return http.get('/users', { params });
    },

    create(data) {
        return http.post('/users', data);
    },

    update(id, data) {
        return http.put(`/users/${id}`, data);
    },

    setActive(id, active) {
        return http.patch(`/users/${id}/status`, { active });
    },
};

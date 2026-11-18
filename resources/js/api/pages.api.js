import http from './http';

export const pagesApi = {
    list(params = {}) {
        return http.get('/pages', { params });
    },

    create(data) {
        return http.post('/pages', data);
    },

    show(id) {
        return http.get(`/pages/${id}`);
    },

    update(id, data) {
        return http.put(`/pages/${id}`, data);
    },

    setStatus(id, status) {
        return http.patch(`/pages/${id}/status`, { status });
    },

    setHome(id, isHome) {
        return http.patch(`/pages/${id}/home`, { is_home: isHome });
    },

    remove(id) {
        return http.delete(`/pages/${id}`);
    },
};

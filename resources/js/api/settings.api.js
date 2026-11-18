import http from './http';

export const settingsApi = {
    show() {
        return http.get('/settings');
    },

    update(data) {
        return http.put('/settings', data);
    },
};

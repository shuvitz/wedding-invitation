import http from './http';

export const mediaApi = {
    list(params = {}) {
        return http.get('/media', { params });
    },

    upload(file, altText, onUploadProgress) {
        const formData = new FormData();
        formData.append('file', file);
        if (altText) {
            formData.append('alt_text', altText);
        }

        return http.post('/media', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
            onUploadProgress,
        });
    },

    show(id) {
        return http.get(`/media/${id}`);
    },

    update(id, data) {
        return http.patch(`/media/${id}`, data);
    },

    remove(id) {
        return http.delete(`/media/${id}`);
    },
};

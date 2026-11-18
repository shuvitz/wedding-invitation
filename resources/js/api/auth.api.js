import http from './http';

export const authApi = {
    /**
     * Log in with email & password.
     */
    login(credentials) {
        return http.post('/login', credentials);
    },

    /**
     * Log out the current user.
     */
    logout() {
        return http.post('/logout');
    },

    /**
     * Fetch the authenticated user.
     */
    getUser() {
        return http.get('/me');
    },
};

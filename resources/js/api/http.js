import axios from 'axios';

const http = axios.create({
    baseURL: '/api',
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
    },
    withCredentials: true,
    withXSRFToken: true,
});

/**
 * Response interceptor:
 * - 401 → redirect to login
 * - 419 → CSRF token expired, fetch new cookie & retry
 */
http.interceptors.response.use(
    (response) => response,
    async (error) => {
        const originalRequest = error.config;

        // CSRF token mismatch — fetch cookie and retry once
        if (error.response?.status === 419 && !originalRequest._retried) {
            originalRequest._retried = true;
            await axios.get('/sanctum/csrf-cookie');
            return http(originalRequest);
        }

        // Unauthenticated — avoid hard reload loops on SPA
        if (error.response?.status === 401) {
            const path = window.location?.pathname || '';
            const isOnLogin = path === '/login';
            const isMeEndpoint = originalRequest?.url?.endsWith('/me');
            if (!isOnLogin && !isMeEndpoint) {
                window.location.href = '/login';
            }
        }

        return Promise.reject(error);
    }
);

export default http;

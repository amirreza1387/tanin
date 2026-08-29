import axios from 'axios';

const api = axios.create({
    baseURL: '/api/v1',
    headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
    timeout: 15000,
});

api.interceptors.request.use((config) => {
    const token = localStorage.getItem('tanin_token');
    if (token) config.headers.Authorization = `Bearer ${token}`;
    return config;
});

api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            localStorage.removeItem('tanin_token');
            window.dispatchEvent(new CustomEvent('tanin:auth-expired'));
        }
        return Promise.reject(error);
    },
);

export const firstError = (error, fallback = 'عملیات ناموفق بود.') => {
    const errors = error.response?.data?.errors || {};
    return Object.values(errors)[0]?.[0] || errors.message || error.response?.data?.message || fallback;
};

export const notify = (message, type = 'success') => {
    window.dispatchEvent(new CustomEvent('tanin:toast', { detail: { message, type } }));
};

export const unwrap = (response) => response.data;
export default api;

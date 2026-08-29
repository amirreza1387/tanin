import { defineStore } from 'pinia';
import api from '../lib/api';

export const useAuthStore = defineStore('auth', {
    state: () => ({ user: null, loading: false }),
    getters: {
        isAuthenticated: (state) => Boolean(state.user || localStorage.getItem('tanin_token')),
        isAdmin: (state) => state.user?.role === 'admin',
    },
    actions: {
        async me() {
            if (!localStorage.getItem('tanin_token')) return;
            try { this.user = (await api.get('/auth/me')).data.data; } catch { this.logoutLocal(); }
        },
        async login(payload) {
            this.loading = true;
            try {
                const result = (await api.post('/auth/login', payload)).data.data;
                localStorage.setItem('tanin_token', result.token);
                this.user = result.user;
                return result;
            } finally { this.loading = false; }
        },
        async register(payload) {
            this.loading = true;
            try {
                const result = (await api.post('/auth/register', payload)).data.data;
                localStorage.setItem('tanin_token', result.token);
                this.user = result.user;
                return result;
            } finally { this.loading = false; }
        },
        async logout() {
            try { await api.post('/auth/logout'); } finally { this.logoutLocal(); }
        },
        logoutLocal() { localStorage.removeItem('tanin_token'); this.user = null; },
    },
});

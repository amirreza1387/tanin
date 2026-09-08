<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import api from '../lib/api';
import AppSkeleton from '../components/AppSkeleton.vue';

const auth = useAuthStore();
const router = useRouter();
const loading = ref(true);
const saving = ref(false);
const error = ref('');
const message = ref('');
const form = reactive({ current_password: '', password: '', password_confirmation: '' });

onMounted(async () => {
    await auth.me();
    loading.value = false;
    if (!auth.user) router.push('/login');
});

const updatePassword = async () => {
    error.value = '';
    message.value = '';
    saving.value = true;
    try {
        await api.put('/auth/password', form);
        auth.logoutLocal();
        await router.push('/login');
    } catch (requestError) {
        error.value = Object.values(requestError.response?.data?.errors || {})[0]?.[0]
            || requestError.response?.data?.message
            || 'تغییر گذرواژه ناموفق بود.';
    } finally {
        saving.value = false;
    }
};

const logout = async () => {
    await auth.logout();
    router.push('/');
};
</script>

<template>
    <div class="mx-auto max-w-2xl px-4 py-12">
        <AppSkeleton v-if="loading" variant="profile" />
        <div v-else-if="auth.user" class="space-y-5">
            <div class="rounded-2xl bg-white p-7 shadow-sm">
                <h1 class="text-2xl font-black text-navy">پروفایل کاربر</h1>
                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-lg bg-cream p-4"><div class="text-xs text-muted">نام</div><div class="mt-1 font-bold">{{ auth.user.name }}</div></div>
                    <div class="rounded-lg bg-cream p-4"><div class="text-xs text-muted">ایمیل</div><div class="mt-1 font-bold">{{ auth.user.email }}</div></div>
                </div>
                <button class="mt-6 rounded-lg bg-brand-red px-5 py-3 text-sm font-bold text-white" @click="logout">خروج از حساب</button>
            </div>
            <div class="rounded-2xl bg-white p-7 shadow-sm">
                <h2 class="text-xl font-black text-navy">تغییر گذرواژه</h2>
                <p class="mt-2 text-sm text-muted">بعد از تغییر، همه نشست‌های قبلی بسته می‌شوند.</p>
                <div v-if="error" class="ds-alert ds-alert-error mt-4" role="alert">{{ error }}</div>
                <div v-if="message" class="ds-alert ds-alert-success mt-4" role="status">{{ message }}</div>
                <form class="mt-5 space-y-4" @submit.prevent="updatePassword">
                    <label for="current-password" class="sr-only">گذرواژه فعلی</label><input id="current-password" v-model="form.current_password" required type="password" autocomplete="current-password" placeholder="گذرواژه فعلی" class="w-full rounded-lg border border-divider px-4 py-3 outline-none focus:border-brand-red">
                    <label for="new-password" class="sr-only">گذرواژه جدید</label><input id="new-password" v-model="form.password" required type="password" minlength="8" autocomplete="new-password" placeholder="گذرواژه جدید" class="w-full rounded-lg border border-divider px-4 py-3 outline-none focus:border-brand-red">
                    <label for="password-confirmation" class="sr-only">تکرار گذرواژه جدید</label><input id="password-confirmation" v-model="form.password_confirmation" required type="password" minlength="8" autocomplete="new-password" placeholder="تکرار گذرواژه جدید" class="w-full rounded-lg border border-divider px-4 py-3 outline-none focus:border-brand-red">
                    <button :disabled="saving" class="rounded-lg bg-navy px-5 py-3 text-sm font-bold text-white disabled:opacity-60">{{ saving ? 'در حال ذخیره...' : 'تغییر گذرواژه' }}</button>
                </form>
            </div>
        </div>
    </div>
</template>

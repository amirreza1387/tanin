<script setup>
import { reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const props = defineProps({ mode: { type: String, default: 'login' } });
const route = useRoute();
const router = useRouter();
const auth = useAuthStore();
const form = reactive({ name: '', email: '', password: '', password_confirmation: '' });
const error = ref('');
const showPassword = ref(false);

const submit = async () => {
    error.value = '';
    try {
        if (props.mode === 'login') await auth.login({ email: form.email, password: form.password });
        else await auth.register(form);
        await router.push(route.query.redirect || '/');
    } catch (requestError) {
        error.value = Object.values(requestError.response?.data?.errors || {})[0]?.[0]
            || requestError.response?.data?.message
            || 'اطلاعات واردشده صحیح نیست.';
    }
};
</script>

<template>
    <div class="mx-auto max-w-md px-4 py-16">
        <div class="rounded-2xl bg-white p-7 shadow-sm">
            <h1 class="text-2xl font-black text-navy">{{ mode === 'login' ? 'ورود به حساب' : 'ساخت حساب کاربری' }}</h1>
            <p class="mt-2 text-sm text-muted">برای ادامه اطلاعات خود را وارد کنید.</p>
            <div v-if="error" class="mt-4 rounded-lg bg-red-50 p-3 text-sm text-red-700" role="alert">{{ error }}</div>
            <form class="mt-6 space-y-4" @submit.prevent="submit">
                <label v-if="mode === 'register'" class="block text-right text-sm font-bold text-navy">نام و نام خانوادگی
                    <input v-model.trim="form.name" required autocomplete="name" placeholder="نام و نام خانوادگی" class="mt-1 w-full rounded-lg border border-divider px-4 py-3 text-right outline-none focus:border-brand-red" />
                </label>
                <label class="block text-right text-sm font-bold text-navy">ایمیل
                    <input v-model.trim="form.email" required type="email" autocomplete="email" placeholder="ایمیل خود را وارد کنید" dir="rtl" class="mt-1 w-full rounded-lg border border-divider px-4 py-3 text-right outline-none focus:border-brand-red" />
                </label>
                <label class="block text-right text-sm font-bold text-navy">گذرواژه
                    <span class="relative mt-1 block">
                        <input v-model="form.password" required :type="showPassword ? 'text' : 'password'" :autocomplete="mode === 'login' ? 'current-password' : 'new-password'" placeholder="گذرواژه خود را وارد کنید" dir="rtl" minlength="8" class="w-full rounded-lg border border-divider px-4 py-3 pl-12 text-right outline-none focus:border-brand-red" />
                        <button type="button" class="absolute left-3 top-1/2 -translate-y-1/2 rounded p-1 text-navy transition hover:bg-cream" :aria-label="showPassword ? 'مخفی کردن گذرواژه' : 'نمایش گذرواژه'" :title="showPassword ? 'مخفی کردن گذرواژه' : 'نمایش گذرواژه'" @click="showPassword = !showPassword">
                            <svg v-if="showPassword" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 3l18 18"/><path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"/><path d="M9.9 4.2A10.8 10.8 0 0 1 12 4c5 0 8.5 4 9.5 6a16 16 0 0 1-3.2 3.7M6.2 6.2C4.4 7.5 3.2 9.2 2.5 10c1 2 4.5 6 9.5 6 1 0 1.9-.1 2.8-.4"/></svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                        </button>
                    </span>
                </label>
                <label v-if="mode === 'register'" class="block text-right text-sm font-bold text-navy">تکرار گذرواژه
                    <input v-model="form.password_confirmation" required type="password" autocomplete="new-password" placeholder="گذرواژه را دوباره وارد کنید" dir="rtl" minlength="8" class="mt-1 w-full rounded-lg border border-divider px-4 py-3 text-right outline-none focus:border-brand-red" />
                </label>
                <button :disabled="auth.loading" class="w-full rounded-lg bg-brand-red py-3 font-bold text-white disabled:cursor-not-allowed disabled:opacity-60">{{ auth.loading ? 'در حال ارسال...' : mode === 'login' ? 'ورود' : 'ثبت‌نام' }}</button>
            </form>
            <RouterLink :to="mode === 'login' ? '/register' : '/login'" class="mt-5 block text-center text-sm text-brand-red">{{ mode === 'login' ? 'حساب ندارید؟ ثبت‌نام کنید' : 'حساب دارید؟ وارد شوید' }}</RouterLink>
        </div>
    </div>
</template>

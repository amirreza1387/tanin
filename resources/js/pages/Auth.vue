<script setup>
import { computed, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import Button from '../components/Button.vue';
import Card from '../components/Card.vue';
import Input from '../components/Input.vue';
import { firstError } from '../lib/api';

const props = defineProps({ mode: { type: String, default: 'login' } });
const route = useRoute();
const router = useRouter();
const auth = useAuthStore();
const form = reactive({ name: '', email: '', password: '', password_confirmation: '' });
const error = ref('');
const fieldErrors = reactive({ name: '', email: '', password: '', password_confirmation: '' });
const showPassword = ref(false);
const passwordStrength = computed(() => {
    const value = form.password;
    if (!value) return { score: 0, label: 'هنوز گذرواژه‌ای وارد نشده است.', tone: 'bg-divider' };
    let score = value.length >= 8 ? 1 : 0;
    if (/[a-zA-Z]/.test(value) && /\d/.test(value)) score += 1;
    if (/[^a-zA-Z\d]/.test(value)) score += 1;
    if (value.length >= 12) score += 1;
    const labels = ['خیلی ضعیف', 'ضعیف', 'متوسط', 'خوب', 'قوی'];
    const tones = ['bg-danger', 'bg-danger', 'bg-warning', 'bg-info', 'bg-success'];
    return { score, label: labels[score], tone: tones[score] };
});
const clearErrors = () => { error.value = ''; Object.keys(fieldErrors).forEach((key) => { fieldErrors[key] = ''; }); };
const validate = () => {
    if (props.mode !== 'register') return true;
    if (form.password.length < 8) fieldErrors.password = 'گذرواژه باید حداقل ۸ نویسه باشد.';
    if (form.password !== form.password_confirmation) fieldErrors.password_confirmation = 'تکرار گذرواژه با آن یکسان نیست.';
    return !fieldErrors.password && !fieldErrors.password_confirmation;
};

const submit = async () => {
    clearErrors();
    if (!validate()) { error.value = 'لطفاً خطاهای فرم را اصلاح کنید.'; return; }
    try {
        if (props.mode === 'login') await auth.login({ email: form.email, password: form.password });
        else await auth.register(form);
        await router.push(route.query.redirect || '/');
    } catch (requestError) {
        const errors = requestError.response?.data?.errors || {};
        Object.keys(fieldErrors).forEach((key) => { fieldErrors[key] = errors[key]?.[0] || ''; });
        error.value = firstError(requestError, 'اطلاعات واردشده صحیح نیست.');
    }
};
</script>

<template>
    <div class="mx-auto max-w-md px-4 py-12 sm:py-16">
        <Card class="p-5 sm:p-7" :class="mode === 'register' ? 'auth-register-card' : ''">
            <h1 class="text-2xl font-black text-navy">{{ mode === 'login' ? 'ورود به حساب' : 'ساخت حساب کاربری' }}</h1>
            <p class="mt-2 text-sm text-muted">برای ادامه اطلاعات خود را وارد کنید.</p>
            <div v-if="error" class="ds-alert ds-alert-error mt-4" role="alert" aria-live="assertive">{{ error }}</div>
            <form class="mt-6 space-y-4" @submit.prevent="submit">
                <Input v-if="mode === 'register'" id="auth-name" v-model.trim="form.name" label="نام و نام خانوادگی" required autocomplete="name" placeholder="نام و نام خانوادگی" :error="fieldErrors.name" @update:model-value="clearErrors" />
                <Input id="auth-email" v-model.trim="form.email" label="ایمیل" required type="email" autocomplete="email" placeholder="ایمیل خود را وارد کنید" :error="fieldErrors.email" @update:model-value="clearErrors" />
                <label class="block text-right text-sm font-bold text-navy">گذرواژه
                    <span class="relative mt-1 block">
                        <input v-model="form.password" required :type="showPassword ? 'text' : 'password'" :autocomplete="mode === 'login' ? 'current-password' : 'new-password'" placeholder="گذرواژه خود را وارد کنید" dir="rtl" minlength="8" class="ds-control px-4 py-3 pl-12 text-right" :class="fieldErrors.password ? 'border-danger' : ''" :aria-invalid="Boolean(fieldErrors.password)" @input="clearErrors" />
                        <button type="button" class="absolute left-3 top-1/2 -translate-y-1/2 rounded p-1 text-navy transition hover:bg-cream" :aria-label="showPassword ? 'مخفی کردن گذرواژه' : 'نمایش گذرواژه'" :title="showPassword ? 'مخفی کردن گذرواژه' : 'نمایش گذرواژه'" @click="showPassword = !showPassword">
                            <svg v-if="showPassword" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 3l18 18"/><path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"/><path d="M9.9 4.2A10.8 10.8 0 0 1 12 4c5 0 8.5 4 9.5 6a16 16 0 0 1-3.2 3.7M6.2 6.2C4.4 7.5 3.2 9.2 2.5 10c1 2 4.5 6 9.5 6 1 0 1.9-.1 2.8-.4"/></svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                        </button>
                    </span>
                    <p v-if="fieldErrors.password" class="mt-1 text-xs text-danger" role="alert">{{ fieldErrors.password }}</p>
                </label>
                <div v-if="mode === 'register'" class="rounded-xl bg-cream p-3" aria-live="polite"><div class="flex items-center justify-between text-xs"><span class="font-bold text-navy">قدرت گذرواژه</span><span class="text-muted">{{ passwordStrength.label }}</span></div><div class="mt-2 grid grid-cols-4 gap-1"><span v-for="segment in 4" :key="segment" class="h-1.5 rounded-full transition-all duration-300" :class="segment <= passwordStrength.score ? passwordStrength.tone : 'bg-divider'"></span></div><p class="mt-2 text-[11px] text-muted">حداقل ۸ نویسه؛ ترکیب حروف، عدد و نشانه‌ها امن‌تر است.</p></div>
                <label v-if="mode === 'register'" class="block text-right text-sm font-bold text-navy">تکرار گذرواژه
                    <input v-model="form.password_confirmation" required type="password" autocomplete="new-password" placeholder="گذرواژه را دوباره وارد کنید" dir="rtl" minlength="8" class="ds-control mt-1 px-4 py-3 text-right" :class="fieldErrors.password_confirmation ? 'border-danger' : ''" :aria-invalid="Boolean(fieldErrors.password_confirmation)" @input="clearErrors" />
                    <p v-if="fieldErrors.password_confirmation" class="mt-1 text-xs text-danger" role="alert">{{ fieldErrors.password_confirmation }}</p>
                </label>
                <Button type="submit" :loading="auth.loading" class="w-full py-3">{{ mode === 'login' ? 'ورود' : 'ثبت‌نام' }}</Button>
            </form>
            <RouterLink :to="mode === 'login' ? '/register' : '/login'" class="mt-5 block text-center text-sm text-brand-red">{{ mode === 'login' ? 'حساب ندارید؟ ثبت‌نام کنید' : 'حساب دارید؟ وارد شوید' }}</RouterLink>
        </Card>
    </div>
</template>

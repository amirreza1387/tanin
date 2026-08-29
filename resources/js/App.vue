<script setup>
import { onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from './stores/auth';
import TheNavbar from './components/TheNavbar.vue';
import Toast from './components/Toast.vue';

const auth = useAuthStore();
const router = useRouter();
const handleAuthExpired = () => {
    auth.logoutLocal();
    if (router.currentRoute.value.path !== '/login') {
        router.push({ path: '/login', query: { redirect: router.currentRoute.value.fullPath } });
    }
};

onMounted(async () => {
    await auth.me();
    window.addEventListener('tanin:auth-expired', handleAuthExpired);
});
onUnmounted(() => window.removeEventListener('tanin:auth-expired', handleAuthExpired));
</script>

<template>
    <div dir="rtl" class="min-h-screen bg-cream text-[#1A1A1A]">
        <TheNavbar />
        <main><RouterView /></main>
        <Toast />
        <footer class="mt-16 bg-navy text-[#F7F4EF]">
            <div class="mx-auto grid max-w-7xl gap-8 px-4 py-12 md:grid-cols-4">
                <div>
                    <div class="mb-3 text-xl font-black">طنین جنوب</div>
                    <p class="text-sm leading-8 text-[#D9D8D2]">روایت معتبر و نزدیک از خبرهای جنوب ایران.</p>
                </div>
                <div><h3 class="mb-4 font-bold">دسترسی سریع</h3><div class="space-y-2 text-sm text-[#D9D8D2]"><RouterLink class="block hover:text-white" to="/">صفحه اصلی</RouterLink><RouterLink class="block hover:text-white" to="/breaking-news">اخبار فوری</RouterLink><RouterLink class="block hover:text-white" to="/most-viewed">پربازدیدترین‌ها</RouterLink></div></div>
                <div><h3 class="mb-4 font-bold">استان‌ها</h3><p class="text-sm leading-8 text-[#D9D8D2]">خوزستان · بوشهر · فارس · هرمزگان</p></div>
                <div><h3 class="mb-4 font-bold">خبرنامه</h3><p class="mb-3 text-sm text-[#D9D8D2]">از مهم‌ترین خبرها باخبر شوید.</p><label class="sr-only" for="newsletter-email">ایمیل</label><input id="newsletter-email" type="email" class="w-full rounded bg-white/10 px-3 py-2 text-sm outline-none placeholder:text-white/50" placeholder="ایمیل شما"></div>
            </div>
            <div class="border-t border-white/15 py-4 text-center text-xs text-white/60">© ۱۴۰۵ طنین جنوب؛ تمامی حقوق محفوظ است.</div>
        </footer>
    </div>
</template>

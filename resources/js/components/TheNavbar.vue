<script setup>
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../lib/api';
import { useAuthStore } from '../stores/auth';

const router = useRouter();
const route = useRoute();
const auth = useAuthStore();
const query = ref('');
const breaking = ref([]);
const categories = ref([]);
const mobileOpen = ref(false);

const search = () => {
    if (!query.value.trim()) return;
    mobileOpen.value = false;
    router.push({ path: '/search', query: { q: query.value.trim() } });
};

const isActive = (path) => route.path === path || (path !== '/' && route.path.startsWith(path));
const tickerItems = () => [...breaking.value, ...breaking.value];

onMounted(async () => {
    const [breakingResponse, categoriesResponse] = await Promise.allSettled([
        api.get('/breaking-news'),
        api.get('/categories'),
    ]);
    if (breakingResponse.status === 'fulfilled') breaking.value = breakingResponse.value.data.data || [];
    if (categoriesResponse.status === 'fulfilled') categories.value = (categoriesResponse.value.data.data || []).slice(0, 6);
});
</script>

<template>
    <header class="sticky top-0 z-50 border-b border-divider bg-white shadow-sm">
        <div class="relative mx-auto flex max-w-7xl items-center gap-3 px-4 py-3">
            <button class="rounded-lg border border-divider px-3 py-2 text-xl lg:hidden" aria-label="باز کردن منو" :aria-expanded="mobileOpen" @click="mobileOpen = !mobileOpen">☰</button>
            <RouterLink to="/" class="shrink-0 text-2xl font-black tracking-tight text-brand-red">طنین <span class="text-navy">جنوب</span></RouterLink>
            <nav class="hidden flex-1 items-center justify-center gap-5 text-sm font-medium lg:flex" aria-label="ناوبری اصلی">
                <RouterLink to="/" :class="isActive('/') ? 'font-black text-brand-red' : 'hover:text-brand-red'">خانه</RouterLink>
                <RouterLink v-for="category in categories" :key="category.id" :to="`/categories/${category.slug}`" :class="isActive(`/categories/${category.slug}`) ? 'font-black text-brand-red' : 'hover:text-brand-red'">{{ category.name }}</RouterLink>
            </nav>
            <form class="hidden items-center rounded-full bg-[#F7F4EF] px-3 py-2 sm:flex" @submit.prevent="search">
                <input v-model="query" class="w-32 bg-transparent text-xs outline-none placeholder:text-muted" placeholder="جست‌وجوی خبر..." aria-label="جست‌وجوی خبر">
                <button class="text-muted" aria-label="جست‌وجو">⌕</button>
            </form>
            <RouterLink v-if="!auth.isAuthenticated" to="/login" class="mobile-auth-action rounded-lg bg-brand-red px-3 py-2 text-xs font-bold text-white">ورود / عضویت</RouterLink>
            <div v-else class="flex items-center gap-2 max-sm:absolute max-sm:left-4">
                <RouterLink v-if="auth.isAdmin" to="/admin" class="hidden rounded-lg bg-navy px-3 py-2 text-xs font-bold text-white sm:block">پنل مدیریت</RouterLink>
                <RouterLink to="/profile" class="rounded-lg border border-divider px-3 py-2 text-xs font-bold text-navy">{{ auth.user?.name || 'پروفایل' }}</RouterLink>
            </div>
        </div>
        <div v-if="mobileOpen" class="border-t border-divider bg-white p-4 lg:hidden">
            <nav class="grid gap-2 text-sm font-bold" aria-label="ناوبری موبایل">
                <RouterLink to="/" class="rounded-lg px-3 py-2" :class="isActive('/') ? 'bg-red-50 text-brand-red' : ''" @click="mobileOpen = false">خانه</RouterLink>
                <RouterLink v-for="category in categories" :key="category.id" :to="`/categories/${category.slug}`" class="rounded-lg px-3 py-2" :class="isActive(`/categories/${category.slug}`) ? 'bg-red-50 text-brand-red' : ''" @click="mobileOpen = false">{{ category.name }}</RouterLink>
                <form class="mt-2 flex rounded-lg bg-cream p-2 sm:hidden" @submit.prevent="search">
                    <input v-model="query" class="min-w-0 flex-1 bg-transparent px-2 text-sm outline-none" placeholder="جست‌وجوی خبر..." aria-label="جست‌وجوی خبر">
                    <button class="px-2 text-brand-red" aria-label="جست‌وجو">⌕</button>
                </form>
            </nav>
        </div>
        <div v-if="breaking.length" class="breaking-ticker overflow-hidden bg-brand-red px-4 py-2 text-sm font-semibold text-white" aria-label="اخبار فوری">
            <div class="relative mx-auto max-w-7xl overflow-hidden">
                <span class="absolute right-0 top-1/2 z-10 -translate-y-1/2 rounded bg-white px-2 text-brand-red">فوری</span>
                <div class="ticker-track mr-14 flex min-w-max items-center gap-8" @mouseenter="$event.currentTarget.classList.add('paused')" @mouseleave="$event.currentTarget.classList.remove('paused')">
                    <template v-for="(item, index) in tickerItems()" :key="`${item.id}-${index}`">
                        <RouterLink class="ticker-item" :to="`/articles/${item.slug}`">{{ item.title }}</RouterLink>
                        <span class="text-white/50" aria-hidden="true">•</span>
                    </template>
                </div>
            </div>
        </div>
    </header>
</template>

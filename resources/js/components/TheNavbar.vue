<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../lib/api';
import { useAuthStore } from '../stores/auth';
import Icon from './Icon.vue';

const router = useRouter();
const route = useRoute();
const auth = useAuthStore();
const query = ref('');
const breaking = ref([]);
const categories = ref([]);
const mobileOpen = ref(false);
const mobileNav = ref(null);
const scrolled = ref(false);
const navigationLoading = ref(true);

const closeMobileNavigation = () => { mobileOpen.value = false; };
const toggleMobileNavigation = async () => {
    mobileOpen.value = !mobileOpen.value;
    if (mobileOpen.value) {
        await nextTick();
        mobileNav.value?.querySelector('a, input, button')?.focus();
    }
};

const search = () => {
    if (!query.value.trim()) return;
    mobileOpen.value = false;
    router.push({ path: '/search', query: { q: query.value.trim() } });
};

const isActive = (path) => route.path === path || (path !== '/' && route.path.startsWith(path));
const tickerItems = () => [...breaking.value, ...breaking.value];
const updateScrollState = () => { scrolled.value = window.scrollY > 18; };
const goTo = async (path) => { mobileOpen.value = false; await router.push(path); };

onMounted(async () => {
    updateScrollState();
    window.addEventListener('scroll', updateScrollState, { passive: true });
    const [breakingResponse, categoriesResponse] = await Promise.allSettled([
        api.get('/breaking-news'),
        api.get('/categories'),
    ]);
    if (breakingResponse.status === 'fulfilled') breaking.value = breakingResponse.value.data.data || [];
    if (categoriesResponse.status === 'fulfilled') categories.value = (categoriesResponse.value.data.data || []).slice(0, 6);
    navigationLoading.value = false;
});
onBeforeUnmount(() => window.removeEventListener('scroll', updateScrollState));
</script>

<template>
    <header class="site-header sticky top-0 z-50" :class="{ 'site-header-scrolled': scrolled }">
        <div class="site-header-inner relative mx-auto flex max-w-7xl items-center gap-2 px-3 py-3 sm:gap-3 sm:px-4">
            <button type="button" class="header-action ds-btn ds-btn-secondary px-3 py-2 xl:hidden" :aria-label="mobileOpen ? 'بستن منو' : 'باز کردن منو'" aria-controls="mobile-navigation" :aria-expanded="mobileOpen" @click.stop="toggleMobileNavigation"><Icon :name="mobileOpen ? 'close' : 'menu'" /></button>
            <RouterLink to="/" class="shrink-0 whitespace-nowrap text-xl font-black tracking-tight text-brand-red sm:text-2xl">طنین <span class="text-navy">جنوب</span></RouterLink>
            <nav class="hidden flex-1 items-center justify-center gap-5 text-sm font-medium xl:flex" aria-label="ناوبری اصلی">
                <span v-if="navigationLoading" v-for="item in 5" :key="item" class="ds-skeleton h-4 w-12"></span>
                <RouterLink to="/" :aria-current="isActive('/') ? 'page' : undefined" :class="isActive('/') ? 'font-black text-brand-red' : 'hover:text-brand-red'">خانه</RouterLink>
                <RouterLink v-for="category in categories" :key="category.id" :to="`/categories/${category.slug}`" :aria-current="isActive(`/categories/${category.slug}`) ? 'page' : undefined" :class="isActive(`/categories/${category.slug}`) ? 'font-black text-brand-red' : 'hover:text-brand-red'">{{ category.name }}</RouterLink>
            </nav>
            <form class="hidden items-center rounded-full bg-[#F7F4EF] px-3 py-2 md:flex" @submit.prevent="search">
                <input v-model="query" class="w-32 bg-transparent text-xs outline-none placeholder:text-muted" placeholder="جست‌وجوی خبر..." aria-label="جست‌وجوی خبر">
                <button class="text-muted" aria-label="جست‌وجو"><Icon name="search" :size="16" /></button>
            </form>
            <button v-if="!auth.isAuthenticated" type="button" class="header-action mr-auto whitespace-nowrap rounded-lg bg-brand-red px-3 py-2 text-xs font-bold text-white" @click="goTo('/login')">ورود / عضویت</button>
            <div v-else class="mr-auto flex items-center gap-2">
                <button v-if="auth.isAdmin" type="button" class="header-action hidden rounded-lg bg-navy px-3 py-2 text-xs font-bold text-white sm:block" @click="goTo('/admin')">پنل مدیریت</button>
                <button type="button" class="header-action max-w-28 truncate rounded-lg border border-divider px-3 py-2 text-xs font-bold text-navy sm:max-w-none" @click="goTo('/profile')">{{ auth.user?.name || 'پروفایل' }}</button>
            </div>
        </div>
        <div v-if="mobileOpen" id="mobile-navigation" ref="mobileNav" class="border-t border-divider bg-white p-4 xl:hidden" @keydown.esc="closeMobileNavigation">
            <nav class="grid gap-2 text-sm font-bold" aria-label="ناوبری موبایل">
                <RouterLink to="/" class="rounded-lg px-3 py-3" :class="isActive('/') ? 'bg-red-50 text-brand-red' : ''" @click="closeMobileNavigation">خانه</RouterLink>
                <RouterLink v-if="auth.isAdmin" to="/admin" class="rounded-lg bg-navy px-3 py-3 text-white" @click="closeMobileNavigation">پنل مدیریت</RouterLink>
                <RouterLink v-for="category in categories" :key="category.id" :to="`/categories/${category.slug}`" class="rounded-lg px-3 py-3" :class="isActive(`/categories/${category.slug}`) ? 'bg-red-50 text-brand-red' : ''" @click="closeMobileNavigation">{{ category.name }}</RouterLink>
                <form class="mt-2 flex rounded-lg bg-cream p-2 sm:hidden" @submit.prevent="search">
                    <input v-model="query" class="min-w-0 flex-1 bg-transparent px-2 text-sm outline-none" placeholder="جست‌وجوی خبر..." aria-label="جست‌وجوی خبر">
                    <button class="px-2 text-brand-red" aria-label="جست‌وجو"><Icon name="search" :size="18" /></button>
                </form>
            </nav>
        </div>
        <div v-if="breaking.length" class="breaking-ticker overflow-hidden bg-brand-red px-4 py-2 text-sm font-semibold text-white" aria-label="اخبار فوری">
            <div class="relative mx-auto max-w-7xl overflow-hidden">
                <span class="absolute right-0 top-1/2 z-10 -translate-y-1/2 rounded bg-white px-2 text-brand-red">فوری</span>
                <div class="ticker-track mr-14 flex min-w-max items-center gap-8" @mouseenter="$event.currentTarget.classList.add('paused')" @mouseleave="$event.currentTarget.classList.remove('paused')">
                    <template v-for="(item, index) in tickerItems()" :key="`${item.id}-${index}`">
                        <RouterLink class="ticker-item" :to="`/articles/${item.slug}`">{{ item.title }}</RouterLink>
                        <span class="text-white/50" aria-hidden="true">·</span>
                    </template>
                </div>
            </div>
        </div>
    </header>
</template>

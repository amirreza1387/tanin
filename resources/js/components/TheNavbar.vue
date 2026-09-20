<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
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
const megaOpen = ref(false);
const megaMenu = ref(null);
const scrolled = ref(false);
const navigationLoading = ref(true);

const primaryCategories = computed(() => categories.value.slice(0, 5));
const flattenCategories = (items) => items.flatMap((category) => [
    { ...category, children: [] },
    ...flattenCategories(category.children || []),
]);

watch(mobileOpen, (isOpen) => {
    document.body.classList.toggle('mobile-navigation-open', isOpen);
});

const closeMobileNavigation = () => { mobileOpen.value = false; };
const closeMegaMenu = () => { megaOpen.value = false; };
const toggleMobileNavigation = async () => {
    mobileOpen.value = !mobileOpen.value;
    megaOpen.value = false;
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
const goTo = async (path) => { mobileOpen.value = false; megaOpen.value = false; await router.push(path); };
const toggleMegaMenu = () => { megaOpen.value = !megaOpen.value; mobileOpen.value = false; };
const handleDocumentPointerDown = (event) => {
    if (megaOpen.value && !megaMenu.value?.contains(event.target)) closeMegaMenu();
};
const handleDocumentKeydown = (event) => {
    if (event.key === 'Escape') {
        closeMegaMenu();
        closeMobileNavigation();
    }
};

onMounted(async () => {
    updateScrollState();
    window.addEventListener('scroll', updateScrollState, { passive: true });
    const [breakingResponse, categoriesResponse] = await Promise.allSettled([
        api.get('/breaking-news'),
        api.get('/categories'),
    ]);
    if (breakingResponse.status === 'fulfilled') breaking.value = breakingResponse.value.data.data || [];
    if (categoriesResponse.status === 'fulfilled') categories.value = flattenCategories(categoriesResponse.value.data.data || []);
    navigationLoading.value = false;
    document.addEventListener('pointerdown', handleDocumentPointerDown);
    document.addEventListener('keydown', handleDocumentKeydown);
});
onBeforeUnmount(() => {
    window.removeEventListener('scroll', updateScrollState);
    document.removeEventListener('pointerdown', handleDocumentPointerDown);
    document.removeEventListener('keydown', handleDocumentKeydown);
    document.body.classList.remove('mobile-navigation-open');
});
</script>

<template>
    <header class="site-header sticky top-0 z-50" :class="{ 'site-header-scrolled': scrolled }">
        <div class="site-header-inner relative mx-auto flex max-w-7xl items-center gap-2 px-3 py-3 sm:gap-3 sm:px-4">
            <button type="button" class="header-action ds-btn ds-btn-secondary px-3 py-2 xl:hidden" :aria-label="mobileOpen ? 'بستن منو' : 'باز کردن منو'" aria-controls="mobile-navigation" :aria-expanded="mobileOpen" @click.stop="toggleMobileNavigation"><Icon :name="mobileOpen ? 'close' : 'menu'" /></button>
            <RouterLink to="/" class="shrink-0 whitespace-nowrap text-xl font-black tracking-tight text-brand-red sm:text-2xl">طنین <span class="text-navy">جنوب</span></RouterLink>
            <nav class="hidden flex-1 items-center justify-center gap-5 text-sm font-medium xl:flex" aria-label="ناوبری اصلی">
                <span v-if="navigationLoading" v-for="item in 5" :key="item" class="ds-skeleton h-4 w-12"></span>
                <RouterLink to="/" :aria-current="isActive('/') ? 'page' : undefined" :class="isActive('/') ? 'font-black text-brand-red' : 'hover:text-brand-red'">خانه</RouterLink>
                <RouterLink v-for="category in primaryCategories" :key="category.id" :to="`/categories/${category.slug}`" :aria-current="isActive(`/categories/${category.slug}`) ? 'page' : undefined" :class="isActive(`/categories/${category.slug}`) ? 'font-black text-brand-red' : 'hover:text-brand-red'">{{ category.name }}</RouterLink>
                <div ref="megaMenu" class="relative">
                    <button type="button" class="inline-flex items-center gap-1 py-2 text-sm font-bold text-navy transition hover:text-brand-red" :aria-expanded="megaOpen" aria-controls="categories-mega-menu" @click="toggleMegaMenu">
                        همه دسته‌بندی‌ها
                        <Icon name="chevron-down" :size="16" :class="megaOpen ? 'rotate-180' : ''" />
                    </button>
                    <section v-if="megaOpen" id="categories-mega-menu" class="absolute right-0 top-full z-50 mt-3 w-[min(52rem,calc(100vw-2rem))] rounded-2xl border border-divider bg-white p-5 text-right shadow-2xl" aria-label="همه دسته‌بندی‌ها">
                        <div class="mb-4 flex items-center justify-between border-b border-divider pb-3">
                            <div>
                                <p class="text-base font-black text-navy">همه دسته‌بندی‌ها</p>
                                <p class="mt-1 text-xs text-muted">دستهٔ موردنظر خود را انتخاب کنید</p>
                            </div>
                            <button type="button" class="rounded-lg p-2 text-muted transition hover:bg-cream hover:text-navy" aria-label="بستن دسته‌بندی‌ها" @click="closeMegaMenu"><Icon name="close" :size="18" /></button>
                        </div>
                        <div class="grid max-h-[60vh] grid-cols-2 gap-x-8 gap-y-5 overflow-y-auto pl-2 sm:grid-cols-3">
                            <div v-for="category in categories" :key="category.id" class="min-w-0">
                                <RouterLink :to="`/categories/${category.slug}`" class="block font-black text-navy transition hover:text-brand-red" @click="closeMegaMenu">{{ category.name }}</RouterLink>
                            </div>
                        </div>
                    </section>
                </div>
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
        <div v-if="mobileOpen" id="mobile-navigation" ref="mobileNav" class="max-h-[calc(100dvh-4.5rem)] overflow-y-auto overscroll-contain border-t border-divider bg-white p-4 xl:hidden" @keydown.esc="closeMobileNavigation">
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

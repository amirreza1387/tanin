<script setup>
import { computed, onMounted, ref } from 'vue';
import api from '../lib/api';
import NewsCard from '../components/NewsCard.vue';
import SidebarBox from '../components/SidebarBox.vue';
import WeatherWidget from '../components/WeatherWidget.vue';

const articles = ref([]);
const most = ref([]);
const categories = ref([]);
const advertisements = ref([]);
const loading = ref(true);
const error = ref('');
const hero = computed(() => articles.value[0]);
const rest = computed(() => articles.value.slice(1));
const image = (article) => article?.featured_media?.variants?.[0] || article?.featured_media?.url;
const ad = (placement) => advertisements.value.find((item) => item.placement === placement);

const load = async () => {
    loading.value = true;
    error.value = '';
    try {
        const [news, popular, cats, ads] = await Promise.all([
            api.get('/articles', { params: { per_page: 12 } }),
            api.get('/most-viewed', { params: { period: 'today' } }),
            api.get('/categories'),
            api.get('/advertisements'),
        ]);
        articles.value = news.data.data || [];
        most.value = popular.data.data || [];
        categories.value = cats.data.data || [];
        advertisements.value = ads.data.data || [];
    } catch {
        error.value = 'دریافت اطلاعات صفحه اصلی ناموفق بود.';
    } finally {
        loading.value = false;
    }
};

onMounted(load);
</script>

<template>
    <div class="mx-auto max-w-7xl px-4 py-6">
        <div v-if="loading" class="py-24 text-center text-muted" aria-live="polite">در حال دریافت تازه‌ترین خبرها...</div>
        <div v-else-if="error" class="mx-auto max-w-xl rounded-2xl bg-white p-10 text-center text-red-700" role="alert">
            {{ error }}
            <button class="mx-auto mt-4 block rounded-lg bg-brand-red px-4 py-2 text-sm font-bold text-white" @click="load">تلاش دوباره</button>
        </div>
        <div v-else class="grid grid-cols-1 items-start gap-6 lg:grid-cols-12">
            <aside class="order-2 flex flex-col gap-6 lg:order-1 lg:col-span-3 lg:sticky lg:top-28">
                <SidebarBox title="پربازدیدترین‌ها">
                    <div v-for="(article, index) in most.slice(0, 5)" :key="article.id" class="flex gap-3 border-b border-divider py-3 last:border-0">
                        <b class="text-2xl text-brand-red">{{ index + 1 }}</b>
                        <RouterLink :to="`/articles/${article.slug}`" class="line-clamp-2 text-sm font-bold leading-6">{{ article.title }}</RouterLink>
                    </div>
                </SidebarBox>
                <SidebarBox title="آخرین اخبار"><NewsCard v-for="article in articles.slice(0, 6)" :key="article.id" :article="article" compact /></SidebarBox>
                <WeatherWidget />
                <a v-if="ad('sidebar')" :href="ad('sidebar').link_url || '#'" target="_blank" rel="noopener noreferrer" class="block overflow-hidden rounded-xl border border-divider bg-white shadow-sm">
                    <img v-if="ad('sidebar').image_url" :src="ad('sidebar').image_url" :alt="ad('sidebar').title" loading="lazy" class="aspect-[4/3] w-full object-cover">
                    <div class="p-3 text-center text-sm font-bold text-navy">{{ ad('sidebar').title }}</div>
                </a>
            </aside>
            <main class="order-1 space-y-6 lg:order-2 lg:col-span-6">
                <article v-if="hero" class="overflow-hidden rounded-xl bg-white shadow-md">
                    <RouterLink :to="`/articles/${hero.slug}`" class="block bg-[#EDE8E0]">
                        <img v-if="image(hero)" :src="image(hero)" :alt="hero.title" class="aspect-video w-full object-cover">
                        <div v-else class="flex aspect-video items-center justify-center text-muted">تصویر اصلی خبر</div>
                    </RouterLink>
                    <div class="p-5">
                        <span class="rounded bg-brand-red px-2 py-1 text-xs font-bold text-white">{{ hero.category?.name || 'خبر ویژه' }}</span>
                        <RouterLink :to="`/articles/${hero.slug}`" class="mt-3 block text-2xl font-black leading-10">{{ hero.title }}</RouterLink>
                        <p v-if="hero.lead" class="mt-2 line-clamp-3 text-sm leading-8 text-[#4A4A4A]">{{ hero.lead }}</p>
                        <div class="mt-4 text-xs text-muted">{{ hero.author?.name || 'تحریریه' }} · {{ hero.published_at_jalali }}</div>
                    </div>
                </article>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2"><NewsCard v-for="article in rest" :key="article.id" :article="article" /></div>
                <a v-if="ad('horizontal')" :href="ad('horizontal').link_url || '#'" target="_blank" rel="noopener noreferrer" class="block overflow-hidden rounded-xl border border-divider bg-white shadow-sm">
                    <img v-if="ad('horizontal').image_url" :src="ad('horizontal').image_url" :alt="ad('horizontal').title" loading="lazy" class="max-h-32 w-full object-cover">
                    <div class="p-3 text-center text-sm font-bold text-navy">{{ ad('horizontal').title }}</div>
                </a>
            </main>
            <aside class="order-3 flex flex-col gap-6 lg:col-span-3 lg:sticky lg:top-28">
                <SidebarBox title="پیشنهاد سردبیر"><NewsCard v-for="article in articles.slice(2, 6)" :key="article.id" :article="article" compact /></SidebarBox>
                <SidebarBox title="اخبار استانی">
                    <div class="mb-3 flex flex-wrap gap-2 text-xs font-bold"><RouterLink v-for="category in categories.slice(0, 4)" :key="category.id" :to="`/categories/${category.slug}`" class="rounded-full bg-white px-2 py-1">{{ category.name }}</RouterLink></div>
                    <NewsCard v-for="article in articles.slice(6, 10)" :key="article.id" :article="article" compact />
                </SidebarBox>
                <a v-if="ad('footer')" :href="ad('footer').link_url || '#'" target="_blank" rel="noopener noreferrer" class="block overflow-hidden rounded-xl border border-divider bg-white shadow-sm">
                    <img v-if="ad('footer').image_url" :src="ad('footer').image_url" :alt="ad('footer').title" loading="lazy" class="aspect-[3/4] w-full object-cover">
                    <div class="p-3 text-center text-sm font-bold text-navy">{{ ad('footer').title }}</div>
                </a>
            </aside>
        </div>
    </div>
</template>

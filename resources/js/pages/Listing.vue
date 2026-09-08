<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import api from '../lib/api';
import { cachedGet } from '../lib/cache';
import NewsCard from '../components/NewsCard.vue';
import AsyncState from '../components/AsyncState.vue';
import Button from '../components/Button.vue';

const props = defineProps({ kind: { type: String, default: 'category' } });
const route = useRoute();
const articles = ref([]);
const loading = ref(true);
const error = ref('');
const title = ref('اخبار');
const page = ref(1);
const lastPage = ref(1);
const total = ref(0);

const load = async () => {
    loading.value = true;
    error.value = '';
    try {
        let response;
        const params = { page: page.value, per_page: 15 };
        if (props.kind === 'search') {
            title.value = `جست‌وجو برای «${route.query.q || ''}»`;
            params.q = route.query.q || 'خبر';
            response = await api.get('/search', { params });
        } else if (props.kind === 'breaking') {
            title.value = 'اخبار فوری';
            response = await api.get('/breaking-news');
        } else if (props.kind === 'most') {
            title.value = 'پربازدیدترین اخبار';
            response = await api.get('/most-viewed', { params: { period: route.query.period || 'today' } });
        } else if (props.kind === 'tag') {
            response = await api.get(`/tags/${route.params.slug}`);
            title.value = response.data.data?.name ? `برچسب ${response.data.data.name}` : 'برچسب';
            articles.value = response.data.data?.articles || [];
            total.value = articles.value.length;
            return;
        } else {
            response = { data: await cachedGet(`articles:${route.params.slug}:${page.value}`, () => api.get('/articles', { params: { ...params, category_slug: route.params.slug } }), { ttl: 60_000 }) };
            title.value = `اخبار ${route.params.slug}`;
        }
        articles.value = response.data.data || [];
        lastPage.value = response.data.meta?.last_page || 1;
        total.value = response.data.meta?.total ?? articles.value.length;
    } catch {
        articles.value = [];
        error.value = 'دریافت اخبار ناموفق بود.';
    } finally {
        loading.value = false;
    }
};

const changePage = (nextPage) => {
    page.value = nextPage;
    load();
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

onMounted(load);
watch(() => [route.fullPath, props.kind], () => { page.value = 1; load(); });
const empty = computed(() => !loading.value && !error.value && !articles.value.length);
</script>

<template>
    <div class="mx-auto max-w-7xl px-4 py-8">
        <div class="mb-6 flex items-center justify-between gap-4">
            <h1 class="border-r-4 border-brand-red pr-3 text-2xl font-black text-navy">{{ title }}</h1>
            <span class="text-sm text-muted" aria-live="polite">{{ total }} خبر</span>
        </div>
        <AsyncState :loading="loading" skeleton="news-grid" :error="error" :empty="empty" :empty-text="kind === 'search' ? 'خبری مطابق جست‌وجوی شما پیدا نشد.' : 'در این بخش هنوز خبری منتشر نشده است.'" @retry="load">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <NewsCard v-for="article in articles" :key="article.id" :article="article" />
            </div>
            <div v-if="lastPage > 1" class="ds-pagination mt-8" aria-label="صفحه‌بندی نتایج">
                <Button v-for="number in lastPage" :key="number" :variant="page === number ? 'primary' : 'secondary'" size="sm" :aria-current="page === number ? 'page' : undefined" @click="changePage(number)">{{ number }}</Button>
            </div>
        </AsyncState>
    </div>
</template>

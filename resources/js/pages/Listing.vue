<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import api from '../lib/api';
import NewsCard from '../components/NewsCard.vue';
import AsyncState from '../components/AsyncState.vue';

const props = defineProps({ kind: { type: String, default: 'category' } });
const route = useRoute();
const articles = ref([]);
const loading = ref(true);
const error = ref('');
const title = ref('اخبار');
const page = ref(1);
const lastPage = ref(1);

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
            return;
        } else {
            response = await api.get('/articles', { params: { ...params, category_slug: route.params.slug } });
            title.value = `اخبار ${route.params.slug}`;
        }
        articles.value = response.data.data || [];
        lastPage.value = response.data.meta?.last_page || 1;
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
            <span class="text-sm text-muted">{{ articles.length }} خبر</span>
        </div>
        <AsyncState :loading="loading" :error="error" :empty="empty" @retry="load">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <NewsCard v-for="article in articles" :key="article.id" :article="article" />
            </div>
            <div v-if="lastPage > 1" class="mt-8 flex flex-wrap items-center justify-center gap-2">
                <button v-for="number in lastPage" :key="number" class="h-9 min-w-9 rounded-lg px-3 text-sm font-bold" :class="page === number ? 'bg-brand-red text-white' : 'bg-white text-navy'" @click="changePage(number)">{{ number }}</button>
            </div>
        </AsyncState>
    </div>
</template>

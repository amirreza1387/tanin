<script setup>
import { computed, onMounted, ref } from 'vue';
import api from '../lib/api';
import SidebarBox from '../components/SidebarBox.vue';
import WeatherWidget from '../components/WeatherWidget.vue';
import ResponsiveImage from '../components/ResponsiveImage.vue';
import AppSkeleton from '../components/AppSkeleton.vue';

const latest = ref([]); const most = ref([]); const editorPicks = ref([]); const sections = ref([]); const featured = ref(null); const advertisements = ref([]);
const loading = ref(true); const error = ref(''); const activeFeed = ref('latest');
const feed = computed(() => activeFeed.value === 'latest' ? latest.value : most.value);
const ad = (placement) => advertisements.value.find((item) => item.placement === placement);
const load = async () => {
  loading.value = true; error.value = '';
  try {
    const [news, popular, picks, homeSections, featuredArticle, ads] = await Promise.all([
      api.get('/articles', { params: { per_page: 8 } }), api.get('/most-viewed', { params: { period: 'today' } }), api.get('/editor-picks'), api.get('/home-sections'), api.get('/featured-article'), api.get('/advertisements'),
    ]);
    latest.value = news.data.data || []; most.value = popular.data.data || []; editorPicks.value = picks.data.data || []; sections.value = homeSections.data.data || []; featured.value = featuredArticle.data.data || null; advertisements.value = ads.data.data || [];
  } catch { error.value = 'دریافت اطلاعات صفحهٔ اصلی ناموفق بود.'; } finally { loading.value = false; }
};
onMounted(load);
</script>

<template>
  <div class="mx-auto max-w-[1440px] px-4 py-6 sm:py-8">
    <AppSkeleton v-if="loading" variant="home" />
    <div v-else-if="error" class="mx-auto max-w-xl rounded-2xl bg-white p-10 text-center text-red-700" role="alert">{{ error }}<button class="mx-auto mt-4 block rounded-lg bg-brand-red px-4 py-2 text-sm font-bold text-white" @click="load">تلاش دوباره</button></div>
    <div v-else class="grid grid-cols-1 items-start gap-8 lg:grid-cols-[repeat(13,minmax(0,1fr))]">
      <aside class="order-2 flex flex-col gap-8 lg:order-1 lg:col-span-3 lg:sticky lg:top-28">
        <SidebarBox title="خبرها">
          <div class="mb-3 flex border-b border-divider text-xs font-bold"><button class="feed-tab" :class="{ 'feed-tab-active': activeFeed === 'latest' }" @click="activeFeed = 'latest'">آخرین خبرها</button><button class="feed-tab" :class="{ 'feed-tab-active': activeFeed === 'most' }" @click="activeFeed = 'most'">پربازدیدترین‌ها</button></div>
          <TransitionGroup name="feed" tag="div" class="space-y-0"><RouterLink v-for="(item, index) in feed.slice(0, 6)" :key="item.id" :to="`/articles/${item.slug}`" class="text-feed-row" :class="{ 'text-feed-ranked': activeFeed === 'most' }"><b v-if="activeFeed === 'most'">{{ String(index + 1).padStart(2, '0') }}</b><span class="line-clamp-2">{{ item.title }}</span><small>{{ item.published_at_jalali }}</small></RouterLink></TransitionGroup>
        </SidebarBox>
        <WeatherWidget />
        <a v-if="ad('sidebar')" :href="ad('sidebar').link_url || '#'" target="_blank" rel="noopener noreferrer" class="block overflow-hidden rounded-xl border border-divider bg-white shadow-sm"><img v-if="ad('sidebar').image_url" :src="ad('sidebar').image_url" :alt="ad('sidebar').title" loading="lazy" class="aspect-[4/3] w-full object-cover"><div class="p-3 text-center text-sm font-bold text-navy">{{ ad('sidebar').title }}</div></a>
      </aside>
      <main class="order-1 space-y-10 lg:order-2 lg:col-span-7">
        <article v-if="featured" class="cinematic-hero overflow-hidden bg-navy shadow-lg">
          <RouterLink :to="`/articles/${featured.slug}`" class="cinematic-hero-media block bg-[#EDE8E0]"><ResponsiveImage v-if="featured.featured_media" :media="featured.featured_media" :alt="featured.title" eager sizes="(min-width: 1024px) 60vw, 100vw" class="aspect-[16/10] w-full object-cover" /><div v-else class="flex aspect-video items-center justify-center text-muted">تصویر خبر منتخب</div></RouterLink>
          <div class="cinematic-hero-copy p-5 sm:p-8"><span class="cinematic-hero-kicker">خبر منتخب</span><RouterLink :to="`/articles/${featured.slug}`" class="mt-4 block text-3xl font-black leading-[1.65] sm:text-4xl">{{ featured.title }}</RouterLink><p v-if="featured.lead" class="mt-3 line-clamp-3 text-sm leading-8 text-white/75 sm:text-base">{{ featured.lead }}</p><div class="mt-5 text-xs text-white/55">{{ featured.author?.name || 'تحریریه' }} · {{ featured.published_at_jalali }}</div></div>
        </article>
        <div v-else class="rounded-2xl border border-dashed border-divider bg-white p-8 text-center text-sm text-muted">هنوز خبر منتخبی از پنل مدیریت انتخاب نشده است.</div>
        <section v-for="section in sections" :key="section.category.slug" class="category-news-row"><div class="mb-4 flex items-center justify-between border-b-2 border-navy pb-2"><RouterLink :to="`/categories/${section.category.slug}`" class="text-xl font-black text-navy">{{ section.category.name }}</RouterLink><RouterLink :to="`/categories/${section.category.slug}`" class="text-xs font-bold text-brand-red">مشاهده همه</RouterLink></div><div class="grid gap-x-6 gap-y-0 sm:grid-cols-2"><RouterLink v-for="item in section.articles" :key="item.id" :to="`/articles/${item.slug}`" class="category-news-item"><ResponsiveImage v-if="item.featured_media" :media="item.featured_media" :alt="item.title" sizes="112px" class="category-news-thumb object-cover" /><div class="min-w-0"><span class="text-[11px] font-bold text-brand-red">{{ item.published_at_jalali }}</span><strong class="mt-1 line-clamp-2">{{ item.title }}</strong><p v-if="item.lead" class="mt-1 line-clamp-2 text-xs leading-6 text-muted">{{ item.lead }}</p></div></RouterLink></div></section>
        <a v-if="ad('horizontal')" :href="ad('horizontal').link_url || '#'" target="_blank" rel="noopener noreferrer" class="block overflow-hidden rounded-xl border border-divider bg-white shadow-sm"><img v-if="ad('horizontal').image_url" :src="ad('horizontal').image_url" :alt="ad('horizontal').title" loading="lazy" class="max-h-32 w-full object-cover"><div class="p-3 text-center text-sm font-bold text-navy">{{ ad('horizontal').title }}</div></a>
      </main>
      <aside class="order-3 flex flex-col gap-8 lg:col-span-3 lg:sticky lg:top-28">
        <SidebarBox title="پیشنهاد سردبیر"><div v-if="editorPicks.length" class="space-y-0"><RouterLink v-for="item in editorPicks" :key="item.id" :to="`/articles/${item.slug}`" class="editor-pick-row"><ResponsiveImage v-if="item.featured_media" :media="item.featured_media" :alt="item.title" sizes="96px" class="editor-pick-thumb object-cover" /><div class="min-w-0"><span>{{ item.category?.name || 'خبر' }}</span><strong class="line-clamp-2">{{ item.title }}</strong></div></RouterLink></div><p v-else class="text-sm leading-7 text-muted">برای نمایش این بخش، به خبرها برچسب «پیشنهاد سردبیر» بدهید.</p></SidebarBox>
        <a v-if="ad('footer')" :href="ad('footer').link_url || '#'" target="_blank" rel="noopener noreferrer" class="block overflow-hidden rounded-xl border border-divider bg-white shadow-sm"><img v-if="ad('footer').image_url" :src="ad('footer').image_url" :alt="ad('footer').title" loading="lazy" class="aspect-[3/4] w-full object-cover"><div class="p-3 text-center text-sm font-bold text-navy">{{ ad('footer').title }}</div></a>
      </aside>
    </div>
  </div>
</template>

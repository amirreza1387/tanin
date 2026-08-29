<script setup>
import { onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import api from '../lib/api';
import AsyncState from '../components/AsyncState.vue';
import { notify } from '../lib/api';

const route = useRoute();
const article = ref(null);
const loading = ref(true);
const error = ref('');
const broken = ref(false);
const image = (item) => item?.featured_media?.variants?.[0] || item?.featured_media?.url;

const setMeta = (name, content, property = false) => {
    if (!content) return;
    const selector = property ? `meta[property="${name}"]` : `meta[name="${name}"]`;
    let tag = document.head.querySelector(selector);
    if (!tag) {
        tag = document.createElement('meta');
        tag.setAttribute(property ? 'property' : 'name', name);
        document.head.appendChild(tag);
    }
    tag.setAttribute('content', content);
};

const share = async () => {
    const url = window.location.href;
    if (navigator.share) await navigator.share({ title: article.value.title, text: article.value.lead, url });
    else {
        await navigator.clipboard.writeText(url);
        notify('لینک خبر کپی شد.');
    }
};

const load = async () => {
    loading.value = true;
    error.value = '';
    try {
        article.value = (await api.get(`/articles/${route.params.slug}`)).data.data;
        document.title = `${article.value.title} | طنین جنوب`;
        setMeta('description', article.value.seo?.meta_description || article.value.lead);
        setMeta('og:title', article.value.title, true);
        setMeta('og:description', article.value.seo?.meta_description || article.value.lead, true);
        setMeta('og:type', 'article', true);
        setMeta('og:url', window.location.href, true);
        if (image(article.value)) setMeta('og:image', new URL(image(article.value), window.location.origin).href, true);
    } catch {
        error.value = 'خبر پیدا نشد.';
    } finally {
        loading.value = false;
    }
};

onMounted(load);
</script>

<template>
    <div class="mx-auto max-w-4xl px-4 py-8">
        <AsyncState :loading="loading" :error="error" @retry="load">
            <article class="rounded-xl bg-white p-5 shadow-sm sm:p-8">
                <div class="mb-4 text-sm font-bold text-brand-red">{{ article.category?.name || 'خبر' }}</div>
                <h1 class="text-2xl font-black leading-[1.8] sm:text-4xl">{{ article.title }}</h1>
                <div class="mt-4 flex flex-wrap items-center gap-3 text-sm text-muted">
                    <span>{{ article.author?.name || 'تحریریه' }} · {{ article.published_at_jalali }}</span>
                    <button class="rounded-lg border border-divider px-3 py-1.5 text-xs font-bold text-navy hover:border-brand-red hover:text-brand-red" @click="share">اشتراک‌گذاری</button>
                </div>
                <div v-if="image(article) && !broken" class="mt-6 overflow-hidden rounded-xl">
                    <img :src="image(article)" :alt="article.title" class="aspect-video w-full object-cover" decoding="async" @error="broken = true">
                </div>
                <div v-else class="mt-6 flex aspect-video items-center justify-center rounded-xl bg-cream text-muted">بدون تصویر</div>
                <p v-if="article.lead" class="mt-7 border-r-4 border-brand-red pr-4 text-base font-bold leading-9 text-[#4A4A4A]">{{ article.lead }}</p>
                <div class="mt-7 whitespace-pre-line text-base leading-[2.2] text-[#242424]">{{ article.body }}</div>
                <div v-if="article.comments?.length" class="mt-10 border-t border-divider pt-6">
                    <h2 class="mb-4 text-xl font-black">نظرات</h2>
                    <div v-for="comment in article.comments" :key="comment.id" class="border-b border-divider py-4">
                        <div class="text-xs font-bold text-navy">{{ comment.user?.name }}</div>
                        <p class="mt-2 text-sm leading-7">{{ comment.body }}</p>
                    </div>
                </div>
            </article>
        </AsyncState>
    </div>
</template>

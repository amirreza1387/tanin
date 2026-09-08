<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import api, { firstError, notify } from '../lib/api';
import AsyncState from '../components/AsyncState.vue';
import Button from '../components/Button.vue';
import Card from '../components/Card.vue';
import ResponsiveImage from '../components/ResponsiveImage.vue';

const route = useRoute();
const auth = useAuthStore();
const article = ref(null); const related = ref([]); const comments = ref([]);
const loading = ref(true); const error = ref(''); const commentsLoading = ref(false); const commentsError = ref(''); const relatedLoading = ref(false);
const commentBody = ref(''); const commentSubmitting = ref(false); const commentError = ref(''); const broken = ref(false);
const readingProgress = ref(0);
const image = (item) => item?.featured_media?.variants?.[0] || item?.featured_media?.url;
const canComment = computed(() => auth.isAuthenticated);
const setMeta = (name, content, property = false) => { if (!content) return; const selector = property ? `meta[property="${name}"]` : `meta[name="${name}"]`; let tag = document.head.querySelector(selector); if (!tag) { tag = document.createElement('meta'); tag.setAttribute(property ? 'property' : 'name', name); document.head.appendChild(tag); } tag.setAttribute('content', content); };
const loadComments = async () => { if (!article.value) return; commentsLoading.value = true; commentsError.value = ''; try { comments.value = (await api.get(`/articles/${article.value.id}/comments`)).data.data || []; } catch (e) { commentsError.value = firstError(e) || 'بارگذاری نظرات ممکن نشد.'; } finally { commentsLoading.value = false; } };
const loadRelated = async () => { if (!article.value?.category?.id) return; relatedLoading.value = true; try { const result = await api.get('/articles', { params: { category_id: article.value.category.id, per_page: 4 } }); related.value = (result.data.data || []).filter((item) => item.id !== article.value.id).slice(0, 3); } catch { related.value = []; } finally { relatedLoading.value = false; } };
const submitComment = async () => { commentError.value = ''; if (!commentBody.value.trim() || commentBody.value.trim().length < 2) { commentError.value = 'متن نظر حداقل باید ۲ نویسه باشد.'; return; } commentSubmitting.value = true; try { await api.post(`/articles/${article.value.id}/comments`, { body: commentBody.value.trim() }); commentBody.value = ''; notify('نظر شما ثبت شد و پس از بررسی نمایش داده می‌شود.'); await loadComments(); } catch (e) { commentError.value = firstError(e) || 'ثبت نظر انجام نشد.'; } finally { commentSubmitting.value = false; } };
const share = async () => { const url = window.location.href; if (navigator.share) await navigator.share({ title: article.value.title, text: article.value.lead, url }); else { await navigator.clipboard.writeText(url); notify('لینک خبر کپی شد.'); } };
const load = async () => { loading.value = true; error.value = ''; broken.value = false; try { article.value = (await api.get(`/articles/${route.params.slug}`)).data.data; comments.value = article.value.comments || []; document.title = `${article.value.title} | طنین جنوب`; setMeta('description', article.value.seo?.meta_description || article.value.lead); setMeta('og:title', article.value.title, true); setMeta('og:description', article.value.seo?.meta_description || article.value.lead, true); setMeta('og:type', 'article', true); setMeta('og:url', window.location.href, true); if (image(article.value)) setMeta('og:image', new URL(image(article.value), window.location.origin).href, true); await Promise.all([loadComments(), loadRelated()]); } catch { error.value = 'خبر پیدا نشد.'; } finally { loading.value = false; } };
const updateReadingProgress = () => { const height = document.documentElement.scrollHeight - window.innerHeight; readingProgress.value = height > 0 ? Math.min(100, Math.round((window.scrollY / height) * 100)) : 0; };
onMounted(() => { load(); updateReadingProgress(); window.addEventListener('scroll', updateReadingProgress, { passive: true }); });
onBeforeUnmount(() => window.removeEventListener('scroll', updateReadingProgress));
</script>

<template>
    <div class="article-page mx-auto max-w-5xl px-4 py-8">
        <div class="reading-progress" :style="{ transform: `scaleX(${readingProgress / 100})` }" aria-hidden="true"></div>
        <AsyncState :loading="loading" skeleton="article" :error="error" @retry="load">
            <div class="article-breadcrumb mb-4 text-xs font-bold text-muted"><RouterLink to="/">خانه</RouterLink><span>/</span><RouterLink v-if="article.category" :to="`/categories/${article.category.slug}`">{{ article.category.name }}</RouterLink><span>/</span><span>{{ article.title }}</span></div>
            <article class="article-surface rounded-xl bg-white p-5 shadow-sm sm:p-8">
                <div class="mb-4 text-sm font-bold text-brand-red">{{ article.category?.name || 'خبر' }}</div>
                <h1 class="text-2xl font-black leading-[1.8] sm:text-4xl">{{ article.title }}</h1>
                <div class="mt-4 flex flex-wrap items-center gap-3 text-sm text-muted"><span>{{ article.author?.name || 'تحریریه' }} · {{ article.published_at_jalali }}</span><Button variant="secondary" size="sm" aria-label="اشتراک‌گذاری خبر" @click="share">اشتراک‌گذاری</Button></div>
                <figure v-if="image(article) && !broken" class="article-figure mt-6 overflow-hidden rounded-xl"><ResponsiveImage :media="article.featured_media" :alt="article.title" sizes="(min-width: 1024px) 1024px, 100vw" eager class="aspect-video w-full object-cover" /></figure>
                <div v-else class="mt-6 flex aspect-video items-center justify-center rounded-xl bg-cream text-muted">بدون تصویر</div>
                <p v-if="article.lead" class="mt-7 border-r-4 border-brand-red pr-4 text-base font-bold leading-9 text-[#4A4A4A]">{{ article.lead }}</p><div class="mt-7 whitespace-pre-line text-base leading-[2.2] text-[#242424]">{{ article.body }}</div>
            </article>
            <section class="mt-6 grid gap-6 lg:grid-cols-[1fr_320px]">
                <Card>
                    <div class="mb-4 flex items-center justify-between"><h2 class="text-xl font-black">نظرات</h2><span class="text-xs text-muted">{{ comments.length }} نظر</span></div>
                    <div v-if="commentsLoading" class="space-y-4 py-2" aria-busy="true"><div v-for="item in 3" :key="item" class="border-b border-divider py-3"><i class="ds-skeleton block h-3 w-24"></i><i class="ds-skeleton mt-3 block h-3 w-full"></i><i class="ds-skeleton mt-2 block h-3 w-3/4"></i></div></div><div v-else-if="commentsError" class="ds-alert ds-alert-error" role="alert">{{ commentsError }}</div><div v-else-if="!comments.length" class="rounded-lg bg-cream p-5 text-center text-sm text-muted">هنوز نظری ثبت نشده است.</div>
                    <div v-else v-for="comment in comments" :key="comment.id" class="border-b border-divider py-4 last:border-0"><div class="text-xs font-bold text-navy">{{ comment.user?.name || 'کاربر' }}</div><p class="mt-2 text-sm leading-7">{{ comment.body }}</p><div v-for="reply in (comment.replies || [])" :key="reply.id" class="mr-5 mt-3 rounded-lg bg-cream p-3 text-sm"><strong>{{ reply.user?.name || 'تحریریه' }}</strong><p class="mt-1">{{ reply.body }}</p></div></div>
                    <form v-if="canComment" class="mt-5 border-t border-divider pt-5" @submit.prevent="submitComment"><label for="comment-body" class="mb-2 block text-sm font-bold text-navy">نظر شما</label><textarea id="comment-body" v-model="commentBody" class="ds-control min-h-28 w-full" maxlength="3000" placeholder="نظر خود را بنویسید..."></textarea><p v-if="commentError" class="mt-2 text-sm text-red-700" role="alert">{{ commentError }}</p><Button class="mt-3" type="submit" :loading="commentSubmitting">ثبت نظر</Button></form><p v-else class="mt-5 rounded-lg bg-cream p-4 text-sm text-muted">برای ثبت نظر وارد حساب کاربری شوید.</p>
                </Card>
                <aside v-if="relatedLoading || related.length"><Card><h2 class="mb-4 text-lg font-black">مطالب مرتبط</h2><div v-if="relatedLoading" class="space-y-4" aria-busy="true"><i v-for="item in 3" :key="item" class="ds-skeleton block h-5 w-full"></i></div><div v-else class="space-y-4"><RouterLink v-for="item in related" :key="item.id" :to="`/articles/${item.slug}`" class="block border-b border-divider pb-3 text-sm font-bold leading-7 last:border-0">{{ item.title }}</RouterLink></div></Card></aside>
            </section>
        </AsyncState>
    </div>
</template>

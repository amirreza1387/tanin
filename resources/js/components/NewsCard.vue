<script setup>
import { ref } from 'vue';

defineProps({ article: { type: Object, required: true }, compact: Boolean });
const broken = ref(false);
const image = (article) => article.featured_media?.variants?.[0] || article.featured_media?.url;
</script>

<template>
    <article :class="compact ? 'flex gap-3 border-b border-divider py-3 last:border-0' : 'rounded-xl border border-[#EDE8E0] bg-white p-3 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md'">
        <RouterLink :to="`/articles/${article.slug}`" :class="compact ? 'h-16 w-24 shrink-0 overflow-hidden rounded-lg bg-[#EDE8E0]' : 'block overflow-hidden rounded-lg bg-[#EDE8E0]'">
            <img v-if="image(article) && !broken" :src="image(article)" :alt="article.title" loading="lazy" decoding="async" class="h-full w-full object-cover" :class="compact ? '' : 'aspect-[16/10]'" @error="broken = true">
            <div v-else class="flex h-full min-h-16 items-center justify-center text-xs text-muted">بدون تصویر</div>
        </RouterLink>
        <div class="min-w-0 flex-1">
            <span class="text-xs font-bold text-brand-red">{{ article.category?.name || 'خبر' }}</span>
            <RouterLink :to="`/articles/${article.slug}`" class="mt-1 block line-clamp-2 text-sm font-bold leading-7">{{ article.title }}</RouterLink>
            <p v-if="!compact && article.lead" class="mt-1 line-clamp-2 text-xs leading-6 text-[#4A4A4A]">{{ article.lead }}</p>
            <div class="mt-2 text-[11px] text-muted">{{ article.published_at_jalali || article.publish_at_jalali || '' }}</div>
        </div>
    </article>
</template>

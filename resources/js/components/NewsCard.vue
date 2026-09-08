<script setup>
import { ref } from 'vue';
import Badge from './Badge.vue';
import ResponsiveImage from './ResponsiveImage.vue';

defineProps({ article: { type: Object, required: true }, compact: Boolean });
const broken = ref(false);
</script>

<template>
    <article :class="compact ? 'news-card-compact flex gap-3 border-b border-divider py-3 last:border-0' : 'news-card ds-card p-3'">
        <RouterLink :to="`/articles/${article.slug}`" :class="compact ? 'h-16 w-24 shrink-0 overflow-hidden rounded-lg bg-[#EDE8E0]' : 'news-card-media block overflow-hidden rounded-lg bg-[#EDE8E0]'">
            <ResponsiveImage v-if="article.featured_media && !broken" :media="article.featured_media" :alt="article.title" :sizes="compact ? '96px' : '(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw'" class="h-full w-full object-cover" />
            <div v-else class="flex h-full min-h-16 items-center justify-center text-xs text-muted">بدون تصویر</div>
        </RouterLink>
        <div class="min-w-0 flex-1">
            <Badge tone="info">{{ article.category?.name || 'خبر' }}</Badge>
            <RouterLink :to="`/articles/${article.slug}`" class="news-card-title mt-1 block line-clamp-2 text-sm font-bold leading-7">{{ article.title }}</RouterLink>
            <p v-if="!compact && article.lead" class="mt-1 line-clamp-2 text-xs leading-6 text-[#4A4A4A]">{{ article.lead }}</p>
            <div class="mt-2 text-[11px] text-muted">{{ article.published_at_jalali || article.publish_at_jalali || '' }}</div>
        </div>
    </article>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({ content: { type: String, default: '' } });
const imagePattern = /^!\[([^\]]*)\]\((\/storage\/[^)\s]+)\)$/;

const blocks = computed(() => props.content.split(/\n{2,}/).filter(Boolean).map((block) => {
    const match = block.trim().match(imagePattern);
    return match ? { type: 'image', alt: match[1] || 'تصویر خبر', url: match[2] } : { type: 'text', content: block };
}));
</script>

<template>
    <div class="space-y-6">
        <template v-for="(block, index) in blocks" :key="index">
            <figure v-if="block.type === 'image'" class="overflow-hidden rounded-xl bg-cream">
                <img :src="block.url" :alt="block.alt" class="max-h-[36rem] w-full object-contain" loading="lazy">
            </figure>
            <p v-else class="whitespace-pre-line">{{ block.content }}</p>
        </template>
    </div>
</template>

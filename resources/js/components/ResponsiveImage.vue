<script setup>
import { computed, ref } from 'vue';
const props = defineProps({ media: { type: Object, default: null }, alt: { type: String, default: '' }, sizes: { type: String, default: '100vw' }, eager: Boolean });
const broken = ref(false);
const src = computed(() => props.media?.variants?.medium || props.media?.url || '');
const srcset = computed(() => [['thumbnail', 320], ['medium', 800], ['large', 1440]].filter(([key]) => props.media?.variants?.[key]).map(([key, width]) => `${props.media.variants[key]} ${width}w`).join(', '));
</script>
<template><img v-if="src && !broken" :src="src" :srcset="srcset || undefined" :sizes="sizes" :alt="alt" :loading="eager ? 'eager' : 'lazy'" decoding="async" @error="broken = true"><slot v-else /></template>

<script setup>
import { computed, ref } from 'vue';
import ResponsiveImage from '../ResponsiveImage.vue';
const props = defineProps({ modelValue: [String, Number], media: { type: Array, default: () => [] }, uploading: Boolean });
const emit = defineEmits(['update:modelValue', 'upload']); const query = ref('');
const items = computed(() => props.media.filter((item) => item.type === 'image' && item.original_name.toLowerCase().includes(query.value.toLowerCase())));
</script>
<template><div class="space-y-3"><div class="flex gap-2"><input v-model="query" class="ds-control flex-1" placeholder="جست‌وجوی تصویر"><label class="ds-btn ds-btn-secondary cursor-pointer text-xs">آپلود<input class="sr-only" type="file" accept="image/*" :disabled="uploading" @change="emit('upload', $event)"></label></div><div class="grid max-h-64 grid-cols-3 gap-2 overflow-y-auto rounded-xl border border-divider p-2 sm:grid-cols-4"><button type="button" class="rounded-lg border p-1 text-xs" :class="!modelValue ? 'border-brand-red bg-red-50' : 'border-divider'" @click="emit('update:modelValue', '')">بدون تصویر</button><button v-for="item in items" :key="item.id" type="button" class="overflow-hidden rounded-lg border text-right" :class="modelValue == item.id ? 'border-2 border-brand-red' : 'border-divider'" @click="emit('update:modelValue', item.id)"><ResponsiveImage :media="item" :alt="item.original_name" class="aspect-square w-full object-cover" /><span class="block truncate p-1 text-xs" :title="item.original_name">{{ item.original_name }}</span></button></div></div></template>

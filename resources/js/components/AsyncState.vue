<script setup>
import Button from './Button.vue';
import Card from './Card.vue';
import AppSkeleton from './AppSkeleton.vue';
defineProps({ loading: Boolean, error: { type: String, default: '' }, empty: Boolean, emptyText: { type: String, default: 'موردی برای نمایش پیدا نشد.' }, skeleton: { type: String, default: 'card' } });
defineEmits(['retry']);
</script>
<template>
  <AppSkeleton v-if="loading" :variant="skeleton === 'list' ? 'table' : skeleton" />
  <Card v-else-if="error" class="p-10 text-center" role="alert"><p class="font-bold text-red-700">{{ error }}</p><Button class="mt-4" @click="$emit('retry')">تلاش دوباره</Button></Card>
  <Card v-else-if="empty" class="p-12 text-center text-muted" role="status">{{ emptyText }}</Card>
  <slot v-else />
</template>

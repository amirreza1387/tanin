<script setup>
import Badge from './Badge.vue';
const props = defineProps({ status: { type: String, required: true }, label: String });
const tone = { published: 'success', approved: 'success', active: 'success', draft: 'neutral', pending: 'warning', scheduled: 'info', rejected: 'danger', inactive: 'neutral' };
</script>
<template><Badge :tone="tone[status] || 'neutral'"><slot>{{ label || status }}</slot></Badge></template>

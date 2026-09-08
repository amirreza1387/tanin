<script setup>
defineProps({ tone: { type: String, default: 'neutral' } });
</script>
<template><span class="ds-badge" :class="`ds-badge-${tone}`"><slot /></span></template>

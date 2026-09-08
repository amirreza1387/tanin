<script setup>
defineProps({ padded: { type: Boolean, default: true } });
</script>
<template><div class="ds-card" :class="padded ? 'p-5' : ''"><slot /></div></template>

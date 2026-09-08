<script setup>
defineProps({ variant: { type: String, default: 'primary' }, size: { type: String, default: 'md' }, loading: Boolean, type: { type: String, default: 'button' } });
</script>
<template><button :type="type" :disabled="loading || $attrs.disabled" class="ds-btn" :class="[`ds-btn-${variant}`, size === 'sm' ? 'px-3 py-2 text-xs' : size === 'lg' ? 'px-5 py-3' : 'px-4 py-2.5 text-sm']"><span v-if="loading" class="h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent" aria-hidden="true"></span><slot /></button></template>

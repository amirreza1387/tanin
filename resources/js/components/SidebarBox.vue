<script setup>
defineProps({ title: String });
</script>
<template><section class="rounded-xl border border-divider bg-[#FAF8F5] p-4"><h2 class="mb-3 border-r-4 border-brand-red pr-2 text-base font-black text-navy"><slot name="title">{{ title }}</slot></h2><slot /></section></template>

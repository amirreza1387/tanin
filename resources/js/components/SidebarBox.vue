<script setup>
defineProps({ title: String });
</script>
<template><section class="editorial-block"><h2 class="editorial-block-title"><slot name="title">{{ title }}</slot></h2><slot /></section></template>

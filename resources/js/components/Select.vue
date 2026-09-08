<script setup>
defineProps({ modelValue: [String, Number], label: String, id: String, options: { type: Array, default: () => [] }, disabled: Boolean });
defineEmits(['update:modelValue']);
</script>
<template><label :for="id" class="ds-label"><span v-if="label">{{ label }}</span><select :id="id" :value="modelValue" :disabled="disabled" class="ds-control ds-select" @change="$emit('update:modelValue', $event.target.value)"><slot><option v-for="option in options" :key="option.value" :value="option.value">{{ option.label }}</option></slot></select></label></template>

<script setup>
defineProps({ modelValue: [String, Number], label: String, id: String, type: { type: String, default: 'text' }, placeholder: String, required: Boolean, disabled: Boolean, autocomplete: String, error: String });
defineEmits(['update:modelValue']);
</script>
<template><label :for="id" class="ds-label"><span v-if="label">{{ label }}<span v-if="required" aria-hidden="true"> *</span></span><input :id="id" :type="type" :value="modelValue" :placeholder="placeholder" :required="required" :disabled="disabled" :autocomplete="autocomplete" :aria-invalid="Boolean(error)" :aria-describedby="error ? `${id}-error` : undefined" class="ds-control" :class="error ? 'border-danger' : ''" @input="$emit('update:modelValue', $event.target.value)"><span v-if="error" :id="`${id}-error`" class="text-xs text-danger" role="alert">{{ error }}</span><slot /></label></template>

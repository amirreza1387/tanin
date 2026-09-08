<script setup>
import { watch } from 'vue';
import Button from './Button.vue';
import Card from './Card.vue';
const props = defineProps({ open: Boolean, title: { type: String, default: 'تأیید عملیات' }, message: { type: String, default: '' } });
const emit = defineEmits(['confirm', 'cancel']);
watch(() => props.open, (open) => { if (open) setTimeout(() => document.getElementById('confirm-dialog-cancel')?.focus(), 0); });
</script>
<template>
  <div v-if="open" class="fixed inset-0 z-[120] grid place-items-center bg-black/50 p-4" role="presentation" @click.self="emit('cancel')">
    <Card role="dialog" aria-modal="true" aria-labelledby="confirm-dialog-title" class="w-full max-w-sm p-6 shadow-lg">
      <h2 id="confirm-dialog-title" class="text-lg font-black text-navy">{{ title }}</h2>
      <p class="mt-3 text-sm leading-7 text-muted">{{ message }}</p>
      <div class="mt-6 flex justify-end gap-2">
        <Button id="confirm-dialog-cancel" variant="secondary" @click="emit('cancel')">انصراف</Button>
        <Button variant="danger" @click="emit('confirm')">تأیید</Button>
      </div>
    </Card>
  </div>
</template>

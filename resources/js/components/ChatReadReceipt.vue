<script setup>
defineProps({ read: { type: Boolean, default: false } });
</script>

<template>
    <svg class="chat-read-receipt" :class="{ read }" viewBox="0 0 19 14" role="img" :aria-label="read ? 'خوانده‌شده' : 'ارسال‌شده'" fill="none">
        <path v-if="read" d="m1.3 7.6 3.6 3.6 7.2-7.4M6.3 7.6l3.6 3.6 7.2-7.4" />
        <path v-else d="m2.2 7.4 3.5 3.6 7.1-7.4" />
    </svg>
</template>

<script setup>
defineProps({
    loading: Boolean,
    error: { type: String, default: '' },
    empty: Boolean,
    emptyText: { type: String, default: 'موردی برای نمایش پیدا نشد.' },
});

defineEmits(['retry']);
</script>

<template>
    <div v-if="loading" class="rounded-2xl bg-white p-16 text-center text-muted" aria-live="polite">
        <div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-divider border-t-brand-red"></div>
        <p class="mt-4">در حال بارگذاری...</p>
    </div>
    <div v-else-if="error" class="rounded-2xl bg-white p-10 text-center" role="alert">
        <p class="font-bold text-red-700">{{ error }}</p>
        <button class="mt-4 rounded-lg bg-brand-red px-4 py-2 text-sm font-bold text-white" @click="$emit('retry')">تلاش دوباره</button>
    </div>
    <div v-else-if="empty" class="rounded-2xl bg-white p-12 text-center text-muted">{{ emptyText }}</div>
    <slot v-else />
</template>

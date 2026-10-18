<script setup>
import { onMounted, onUnmounted, ref, watch } from 'vue';
import api from '../lib/api';

const props = defineProps({ user: { type: Object, required: true } });
const element = ref(null);
const source = ref('');
let objectUrl = null;
let observer;

const fetchPhoto = async () => {
    if (!props.user.avatar_url) return;
    try {
        const response = await api.get(props.user.avatar_url, { responseType: 'blob' });
        objectUrl = URL.createObjectURL(response.data);
        source.value = objectUrl;
    } catch { source.value = ''; }
};

onMounted(() => {
    if (!props.user.avatar_url) return;
    if (!('IntersectionObserver' in window)) { fetchPhoto(); return; }
    observer = new IntersectionObserver((entries) => {
        if (entries.some((entry) => entry.isIntersecting)) {
            observer?.disconnect();
            fetchPhoto();
        }
    }, { rootMargin: '120px' });
    if (element.value) observer.observe(element.value);
});

watch(() => props.user.avatar_url, async (nextUrl) => {
    if (objectUrl) URL.revokeObjectURL(objectUrl);
    objectUrl = null;
    source.value = '';
    if (nextUrl) await fetchPhoto();
});

onUnmounted(() => {
    observer?.disconnect();
    if (objectUrl) URL.revokeObjectURL(objectUrl);
});
</script>

<template>
    <span ref="element" class="chat-avatar">
        <img v-if="source" :src="source" alt="" />
        <i v-if="user.is_online" class="chat-online-indicator" role="img" aria-label="آنلاین"></i>
        <span v-else>{{ (user.name || '؟').slice(0, 1) }}</span>
    </span>
</template>

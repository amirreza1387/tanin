<script setup>
import { onMounted, onUnmounted, ref } from 'vue';

const message = ref('');
const type = ref('success');
let timer;
const show = (event) => {
    message.value = event.detail?.message || '';
    type.value = event.detail?.type || 'success';
    clearTimeout(timer);
    timer = setTimeout(() => { message.value = ''; }, 3500);
};
onMounted(() => window.addEventListener('tanin:toast', show));
onUnmounted(() => {
    window.removeEventListener('tanin:toast', show);
    clearTimeout(timer);
});
</script>

<template>
    <Transition enter-active-class="transition duration-200" enter-from-class="translate-y-4 opacity-0" leave-active-class="transition duration-200" leave-to-class="translate-y-4 opacity-0">
        <div v-if="message" class="fixed bottom-5 left-5 z-[100] max-w-sm ds-alert font-bold shadow-lg" :class="type === 'error' ? 'ds-alert-error' : 'ds-alert-success'" role="status">
            {{ message }}
        </div>
    </Transition>
</template>

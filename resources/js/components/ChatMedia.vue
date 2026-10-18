<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import api from '../lib/api';

const props = defineProps({ attachment: { type: Object, required: true } });
const emit = defineEmits(['download']);
const root = ref(null);
const mediaUrl = ref('');
const loading = ref(false);
const failed = ref(false);
const expanded = ref(false);
const audioElement = ref(null);
const audioPlaying = ref(false);
const audioTime = ref(0);
const audioDuration = ref(0);
const videoNoteElement = ref(null);
const videoNotePlaying = ref(false);
const videoElement = ref(null);
const videoPlaying = ref(false);
const videoTime = ref(0);
const videoDuration = ref(0);
const videoMuted = ref(false);
const waveform = [5, 9, 14, 8, 18, 12, 22, 10, 16, 7, 20, 12, 6, 16, 23, 11, 18, 8, 14, 6, 19, 10, 15, 7];
const toggleAudio = async () => {
    if (!isVoiceMessage.value) {
        window.dispatchEvent(new CustomEvent(audioPlaying.value ? 'private-chat:music-toggle' : 'private-chat:music-play', { detail: { ...props.attachment, src: mediaUrl.value } }));
        return;
    }
    if (!audioElement.value) return;
    if (audioElement.value.paused) {
        try {
            window.dispatchEvent(new CustomEvent('private-chat:voice-play', { detail: { url: props.attachment.url } }));
            await audioElement.value.play();
            audioPlaying.value = true;
        } catch { audioPlaying.value = false; }
    } else { audioElement.value.pause(); audioPlaying.value = false; }
};
const toggleVideoNote = async () => { if (!videoNoteElement.value) return; if (videoNoteElement.value.paused) { try { await videoNoteElement.value.play(); videoNotePlaying.value = true; } catch { videoNotePlaying.value = false; } } else { videoNoteElement.value.pause(); videoNotePlaying.value = false; } };
const toggleVideo = async () => {
    if (!videoElement.value) return;
    if (videoElement.value.paused) { try { await videoElement.value.play(); videoPlaying.value = true; } catch { videoPlaying.value = false; } }
    else { videoElement.value.pause(); videoPlaying.value = false; }
};
const updateVideo = () => {
    if (!videoElement.value) return;
    const time = Number(videoElement.value.currentTime);
    const nextDuration = Number(videoElement.value.duration);
    videoTime.value = Number.isFinite(time) && time >= 0 ? time : 0;
    if (Number.isFinite(nextDuration) && nextDuration > 0) videoDuration.value = nextDuration;
};
const seekVideo = (event) => { if (videoElement.value) { videoElement.value.currentTime = Number(event.target.value) || 0; updateVideo(); } };
const toggleVideoMute = () => { if (videoElement.value) { videoElement.value.muted = !videoElement.value.muted; videoMuted.value = videoElement.value.muted; } };
const toggleVideoFullscreen = () => { if (videoElement.value?.requestFullscreen) videoElement.value.requestFullscreen(); };
const formatTime = (value) => {
    const numeric = Number(value);
    if (!Number.isFinite(numeric) || numeric < 0) return '0:00';
    const seconds = Math.floor(numeric);
    return `${Math.floor(seconds / 60)}:${String(seconds % 60).padStart(2, '0')}`;
};
const updateAudio = () => {
    if (!audioElement.value) return;
    const time = Number(audioElement.value.currentTime);
    const nextDuration = Number(audioElement.value.duration);
    audioTime.value = Number.isFinite(time) && time >= 0 ? time : 0;
    if (Number.isFinite(nextDuration) && nextDuration > 0) audioDuration.value = nextDuration;
};
const seekAudio = (event) => { const time = Number(event.target.value); if (!isVoiceMessage.value) window.dispatchEvent(new CustomEvent('private-chat:music-seek', { detail: { url: props.attachment.url, time } })); else if (audioElement.value) { audioElement.value.currentTime = time; updateAudio(); } };
const mediaFailed = () => {
    failed.value = true;
    if (objectUrl) URL.revokeObjectURL(objectUrl);
    objectUrl = null;
    mediaUrl.value = '';
};
let objectUrl = null;
let observer;
const syncMusicState = (event) => {
    const state = event.detail || {};
    if (state.url !== props.attachment.url || isVoiceMessage.value) return;
    audioPlaying.value = !!state.playing;
    audioTime.value = state.currentTime || 0;
    audioDuration.value = state.duration || audioDuration.value;
};
const stopOtherVoice = (event) => {
    if (!isVoiceMessage.value || event.detail?.url === props.attachment.url || !audioElement.value) return;
    audioElement.value.pause();
    audioPlaying.value = false;
};

const kind = computed(() => {
    const name = (props.attachment.name || '').toLowerCase();
    const mime = (props.attachment.mime || '').toLowerCase();
    const extension = name.split('.').pop();
    if (name.startsWith('voice-message-') || mime.startsWith('audio/') || ['mp3', 'wav', 'm4a', 'aac', 'ogg', 'opus', 'flac', 'weba'].includes(extension)) return 'audio';
    if (mime.startsWith('image/') || ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'bmp'].includes(extension)) return 'image';
    if (mime.startsWith('video/') || ['mp4', 'webm', 'mov', 'm4v', 'avi', 'mkv'].includes(extension)) return 'video';
    if (mime.startsWith('audio/')) return 'audio';
    return 'file';
});
const isVideoMessage = computed(() => props.attachment.name?.startsWith('video-message-'));
const isVoiceMessage = computed(() => props.attachment.name?.startsWith('voice-message-'));

const load = async () => {
    if (loading.value || mediaUrl.value || kind.value === 'file') return;
    loading.value = true;
    try {
        const response = await api.get(props.attachment.url, { responseType: 'blob' });
        const blob = response.data;
        if (!blob.size || blob.type === 'text/html' || blob.type === 'application/json') {
            failed.value = true;
            return;
        }
        const extension = (props.attachment.name || '').toLowerCase().split('.').pop();
        const typeByExtension = {
            jpg: 'image/jpeg', jpeg: 'image/jpeg', png: 'image/png', gif: 'image/gif', webp: 'image/webp', avif: 'image/avif',
            mp4: 'video/mp4', mov: 'video/quicktime', m4v: 'video/mp4',
            mp3: 'audio/mpeg', wav: 'audio/wav', m4a: 'audio/mp4', aac: 'audio/aac', ogg: 'audio/ogg', opus: 'audio/ogg', flac: 'audio/flac', weba: 'audio/webm',
            webm: isVoiceMessage.value ? 'audio/webm' : 'video/webm',
        };
        const expectedType = typeByExtension[extension];
        const mediaBlob = expectedType && blob.type !== expectedType ? new Blob([blob], { type: expectedType }) : blob;
        objectUrl = URL.createObjectURL(mediaBlob);
        mediaUrl.value = objectUrl;
    } catch { failed.value = true; }
    finally { loading.value = false; }
};

onMounted(() => {
    window.addEventListener('private-chat:music-state', syncMusicState);
    window.addEventListener('private-chat:voice-play', stopOtherVoice);
    if (kind.value === 'file') return;
    if (!('IntersectionObserver' in window)) { load(); return; }
    observer = new IntersectionObserver((entries) => {
        if (entries.some((entry) => entry.isIntersecting)) {
            observer?.disconnect();
            load();
        }
    }, { rootMargin: '160px' });
    if (root.value) observer.observe(root.value);
});

watch(() => props.attachment.url, () => {
    if (objectUrl) URL.revokeObjectURL(objectUrl);
    objectUrl = null;
    mediaUrl.value = '';
    failed.value = false;
    load();
});

onUnmounted(() => {
    window.removeEventListener('private-chat:music-state', syncMusicState);
    window.removeEventListener('private-chat:voice-play', stopOtherVoice);
    observer?.disconnect();
    if (objectUrl) URL.revokeObjectURL(objectUrl);
});
</script>

<template>
    <div ref="root" class="chat-media" :class="[`chat-media-${kind}`, { 'chat-video-note': isVideoMessage, 'chat-voice-note': isVoiceMessage }]" @click.stop>
        <template v-if="kind === 'file'">
            <button class="chat-attachment" type="button" @click="emit('download', attachment)">
                <span>📎</span><span><b>{{ attachment.name }}</b><small>{{ Math.ceil(attachment.size / 1024) }} کیلوبایت · دریافت امن</small></span><span>↓</span>
            </button>
        </template>
        <div v-else-if="failed" class="chat-media-fallback"><span>نمایش فایل ممکن نشد</span><button type="button" @click="emit('download', attachment)">دریافت فایل</button></div>
        <div v-else-if="loading || !mediaUrl" class="chat-media-loading" aria-label="در حال بارگیری رسانه"></div>
        <button v-else-if="kind === 'image'" class="chat-image-trigger" type="button" :aria-label="`نمایش تصویر ${attachment.name}`" @click="expanded = true">
            <img :src="mediaUrl" :alt="attachment.name" loading="lazy" @error="mediaFailed" />
        </button>
        <div v-else-if="kind === 'video' && isVideoMessage" class="chat-video-note-shell"><video ref="videoNoteElement" class="chat-video-player chat-video-note-player" :src="mediaUrl" playsinline preload="metadata" aria-label="ویدیو مسیج" @click="toggleVideoNote" @play="videoNotePlaying = true" @pause="videoNotePlaying = false" @ended="videoNotePlaying = false" @error="mediaFailed"></video><button v-if="!videoNotePlaying" type="button" class="chat-video-note-play" aria-label="پخش ویدیو مسیج" @click="toggleVideoNote">▶</button></div>
        <div v-else-if="kind === 'video'" class="chat-video-shell">
            <video ref="videoElement" class="chat-video-player" :src="mediaUrl" playsinline preload="metadata" :aria-label="attachment.name" @click="toggleVideo" @timeupdate="updateVideo" @loadedmetadata="updateVideo" @durationchange="updateVideo" @play="videoPlaying = true" @pause="videoPlaying = false" @ended="videoPlaying = false" @error="mediaFailed"></video>
            <div class="chat-video-controls">
                <button type="button" class="chat-video-control-play" :aria-label="videoPlaying ? 'توقف ویدیو' : 'پخش ویدیو'" @click="toggleVideo">{{ videoPlaying ? 'Ⅱ' : '▶' }}</button>
                <input class="chat-video-seek" type="range" min="0" :max="videoDuration || 0" step="0.1" :value="videoTime" aria-label="موقعیت ویدیو" @input="seekVideo" />
                <span class="chat-video-time">{{ formatTime(videoTime) }} / {{ formatTime(videoDuration) }}</span>
                <button type="button" class="chat-video-control" :aria-label="videoMuted ? 'فعال کردن صدا' : 'بی‌صدا کردن'" @click="toggleVideoMute">{{ videoMuted ? '🔇' : '🔊' }}</button>
                <button type="button" class="chat-video-control" aria-label="تمام صفحه" @click="toggleVideoFullscreen">⛶</button>
            </div>
        </div>
        <div v-else-if="kind === 'audio'" class="chat-audio-player" :class="{ 'chat-voice-note': isVoiceMessage, 'chat-music-track': !isVoiceMessage }">
            <span v-if="!isVoiceMessage" class="chat-audio-icon">♫</span>
            <span class="chat-audio-content"><b v-if="!isVoiceMessage">{{ attachment.name }}</b><div class="chat-audio-controls"><button type="button" class="chat-audio-play" :aria-label="audioPlaying ? 'توقف' : 'پخش'" @click="toggleAudio">{{ audioPlaying ? 'Ⅱ' : '▶' }}</button><span v-if="isVoiceMessage" class="chat-audio-wave" aria-hidden="true"><i v-for="(height, index) in waveform" :key="index" :style="{ height: height + 'px' }"></i></span><input v-else class="chat-audio-seek" type="range" min="0" :max="audioDuration || 0" step="0.1" :value="audioTime" aria-label="موقعیت پخش" @input="seekAudio" /><span class="chat-audio-time">{{ formatTime(audioTime) }} / {{ formatTime(audioDuration) }}</span></div><audio ref="audioElement" :src="mediaUrl" preload="metadata" :aria-label="isVoiceMessage ? 'پخش وویس مسیج' : `پخش ${attachment.name}`" @timeupdate="updateAudio" @loadedmetadata="updateAudio" @ended="audioPlaying = false" @pause="audioPlaying = false" @error="mediaFailed"></audio></span>
        </div>
        <div v-if="expanded" class="chat-image-viewer" role="dialog" aria-modal="true" aria-label="نمایش تصویر" @click.self="expanded = false" @keydown.esc.window="expanded = false">
            <button type="button" aria-label="بستن تصویر" @click="expanded = false">×</button>
            <img :src="mediaUrl" :alt="attachment.name" />
        </div>
    </div>
</template>



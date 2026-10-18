<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import api from '../lib/api';

const storageKey = 'private-chat-music-player';
const audio = ref(null);
const queue = ref([]);
const currentIndex = ref(0);
const currentTime = ref(0);
const duration = ref(0);
const volume = ref(1);
const playing = ref(false);
const shuffle = ref(false);
const repeat = ref('off');
const expanded = ref(false);
const playlists = ref([]);
const dismissed = ref(false);
const loading = ref(false);
let objectUrl = null;

const current = computed(() => queue.value[currentIndex.value] || null);
const formatTime = (value) => { const seconds = Math.max(0, Math.floor(value || 0)); return `${Math.floor(seconds / 60)}:${String(seconds % 60).padStart(2, '0')}`; };

const emitState = () => { if (current.value) window.dispatchEvent(new CustomEvent('private-chat:music-state', { detail: { url: current.value.url, playing: playing.value, currentTime: currentTime.value, duration: duration.value } })); };
const persist = () => localStorage.setItem(storageKey, JSON.stringify({ queue: queue.value, currentIndex: currentIndex.value, currentTime: currentTime.value, volume: volume.value, shuffle: shuffle.value, repeat: repeat.value, playing: playing.value }));
const persistPlaylists = () => localStorage.setItem(`${storageKey}-playlists`, JSON.stringify(playlists.value));
const savePlaylist = () => { if (!queue.value.length) return; const name = window.prompt('نام پلی‌لیست را وارد کنید'); if (!name?.trim()) return; playlists.value = [...playlists.value.filter((item) => item.name !== name.trim()), { name: name.trim(), tracks: queue.value.map((track) => ({ ...track, resumeTime: 0 })) }]; persistPlaylists(); };
const loadPlaylist = (playlist) => { if (!playlist) return; queue.value = playlist.tracks.map((track) => ({ ...track, resumeTime: 0 })); currentIndex.value = 0; currentTime.value = 0; loadTrack(true); persist(); };
const loadTrack = async (autoplay = true, immediateSrc = '') => {
    if (!current.value || !audio.value) return;
    loading.value = true;
    if (objectUrl) URL.revokeObjectURL(objectUrl);
    objectUrl = null;
    try {
        if (immediateSrc) {
            audio.value.src = immediateSrc;
            audio.value.load();
            audio.value.currentTime = current.value.resumeTime || 0;
            const immediatePlay = audio.value.play();
            if (autoplay) { playing.value = true; immediatePlay.catch(() => { playing.value = false; }); }
            emitState();
        }
        if (immediateSrc) { loading.value = false; persist(); return; }
        const response = await api.get(current.value.url, { responseType: 'blob' });
        objectUrl = URL.createObjectURL(new Blob([response.data], { type: current.value.mime || response.data.type || 'audio/mpeg' }));
        audio.value.src = objectUrl;
        audio.value.currentTime = current.value.resumeTime || 0;
        if (autoplay) { await audio.value.play(); playing.value = true; emitState(); }
    } catch { playing.value = false; }
    finally { loading.value = false; }
};
const playTrack = async (track) => {
    const existing = queue.value.findIndex((item) => item.url === track.url);
    if (existing < 0) { queue.value.push({ ...track, resumeTime: 0 }); currentIndex.value = queue.value.length - 1; }
    else { currentIndex.value = existing; if (track.src) queue.value[existing].resumeTime = 0; }
    await nextTick();
    await loadTrack(true, track.src || ''); persist(); emitState();
};
const toggle = async () => { if (!audio.value || !current.value) return; if (audio.value.paused) { await audio.value.play(); playing.value = true; } else { audio.value.pause(); playing.value = false; } persist(); emitState(); };
const next = async () => {
    if (!queue.value.length) return;
    if (shuffle.value && queue.value.length > 1) currentIndex.value = Math.floor(Math.random() * queue.value.length);
    else currentIndex.value = (currentIndex.value + 1) % queue.value.length;
    await loadTrack(true); persist(); emitState();
};
const previous = async () => {
    if (!audio.value || !current.value) return;
    if (audio.value.currentTime > 4) { audio.value.currentTime = 0; return; }
    currentIndex.value = (currentIndex.value - 1 + queue.value.length) % queue.value.length;
    await loadTrack(true); persist(); emitState();
};
const ended = async () => {
    if (repeat.value === 'one') { audio.value.currentTime = 0; await audio.value.play(); return; }
    if (currentIndex.value < queue.value.length - 1 || repeat.value === 'all') await next();
    else playing.value = false;
};
const remove = (index) => { queue.value.splice(index, 1); if (index < currentIndex.value) currentIndex.value--; if (!queue.value.length) { audio.value?.pause(); playing.value = false; } else if (index === currentIndex.value) loadTrack(playing.value); persist(); };
const move = (index, direction) => { const nextIndex = index + direction; if (nextIndex < 0 || nextIndex >= queue.value.length) return; [queue.value[index], queue.value[nextIndex]] = [queue.value[nextIndex], queue.value[index]]; if (currentIndex.value === index) currentIndex.value = nextIndex; else if (currentIndex.value === nextIndex) currentIndex.value = index; persist(); };
const onTime = () => { currentTime.value = audio.value?.currentTime || 0; duration.value = audio.value?.duration || 0; if (current.value) current.value.resumeTime = currentTime.value; persist(); emitState(); };
const seek = (event) => { if (audio.value) audio.value.currentTime = Number(event.target.value); };
const onMusicPlay = (event) => { dismissed.value = false; localStorage.removeItem(`${storageKey}-dismissed`); playTrack(event.detail); };
const onMusicToggle = (event) => { if (current.value?.url === event.detail?.url) toggle(); };
const onMusicSeek = (event) => { if (current.value?.url !== event.detail?.url || !audio.value) return; audio.value.currentTime = Number(event.detail.time) || 0; onTime(); };
const closePlayer = () => { dismissed.value = true; localStorage.setItem(`${storageKey}-dismissed`, '1'); };
const closeQueueOnOutside = (event) => { if (expanded.value && !event.target.closest?.('.music-dock')) { expanded.value = false; window.dispatchEvent(new CustomEvent('private-chat:queue-closed-outside')); } };

onMounted(() => {
    try { const saved = JSON.parse(localStorage.getItem(storageKey) || '{}'); queue.value = Array.isArray(saved.queue) ? saved.queue : []; currentIndex.value = saved.currentIndex || 0; currentTime.value = saved.currentTime || 0; volume.value = saved.volume ?? 1; shuffle.value = !!saved.shuffle; repeat.value = saved.repeat || 'off'; playlists.value = JSON.parse(localStorage.getItem(`${storageKey}-playlists`) || '[]'); dismissed.value = true; } catch { /* ignore malformed local state */ }
    window.addEventListener('private-chat:music-play', onMusicPlay);
    window.addEventListener('private-chat:music-toggle', onMusicToggle);
    window.addEventListener('private-chat:music-seek', onMusicSeek);
    document.addEventListener('pointerdown', closeQueueOnOutside, true);
    if (queue.value.length) loadTrack(false);
});
onUnmounted(() => { window.removeEventListener('private-chat:music-play', onMusicPlay); window.removeEventListener('private-chat:music-toggle', onMusicToggle); window.removeEventListener('private-chat:music-seek', onMusicSeek); document.removeEventListener('pointerdown', closeQueueOnOutside, true); if (objectUrl) URL.revokeObjectURL(objectUrl); });
watch(volume, (value) => { if (audio.value) audio.value.volume = value; persist(); });
</script>

<template>
    <section v-if="current && !dismissed" class="music-dock" :class="{ expanded }" aria-label="پخش‌کنندهٔ موسیقی">
        <audio ref="audio" @timeupdate="onTime" @loadedmetadata="onTime" @ended="ended" @play="playing = true; emitState()" @pause="playing = false; emitState()"></audio>
        <div class="music-dock-main">
            <button class="music-cover" type="button" @click="expanded = !expanded" aria-label="نمایش صف پخش">♫</button>
            <button class="music-track-info" type="button" aria-label="نمایش یا بستن صف پخش" @click="expanded = !expanded"><b>{{ current.name || 'آهنگ بدون نام' }}</b><small>{{ current.artist || 'فایل موسیقی' }}</small></button>
            <button class="music-control" type="button" :disabled="loading" aria-label="آهنگ قبلی" @click="previous">‹</button>
            <button class="music-control music-play" type="button" :disabled="loading" :aria-label="playing ? 'توقف' : 'پخش'" @click="toggle">{{ playing ? 'Ⅱ' : '▶' }}</button>
            <button class="music-control" type="button" :disabled="loading" aria-label="آهنگ بعدی" @click="next">›</button>
            <button class="music-control music-queue-toggle" type="button" aria-label="صف پخش" @click="expanded = !expanded">☷</button>
            <button class="music-control music-close" type="button" aria-label="بستن پلیر موسیقی" @click="closePlayer">×</button>
        </div>
        <div class="music-progress"><span>{{ formatTime(currentTime) }}</span><input type="range" min="0" :max="duration || 0" step="0.1" :value="currentTime" aria-label="پیشرفت پخش" @input="seek"><span>{{ formatTime(duration) }}</span></div>
        <div v-if="expanded" class="music-queue-panel">
            <header><b>صف پخش</b><div><button type="button" title="ذخیره پلی‌لیست" @click="savePlaylist">＋</button><button type="button" :class="{ active: shuffle }" @click="shuffle = !shuffle; persist()">⤨</button><button type="button" :class="{ active: repeat !== 'off' }" @click="repeat = repeat === 'off' ? 'all' : repeat === 'all' ? 'one' : 'off'; persist()">⟳{{ repeat === 'one' ? '۱' : '' }}</button></div></header>
            <select v-if="playlists.length" class="music-playlist-select" aria-label="پلی‌لیست‌های ذخیره‌شده" @change="loadPlaylist(playlists[$event.target.selectedIndex - 1])"><option value="">پلی‌لیست‌های من</option><option v-for="playlist in playlists" :key="playlist.name">{{ playlist.name }}</option></select>
            <div v-for="(track, index) in queue" :key="track.url" class="music-queue-row" :class="{ active: index === currentIndex }"><span class="music-drag">⠿</span><button class="music-queue-title" type="button" @click="currentIndex = index; loadTrack(true)"><b>{{ track.name }}</b><small>{{ track.artist || 'فایل موسیقی' }}</small></button><button type="button" aria-label="بالا بردن" @click="move(index, -1)">⌃</button><button type="button" aria-label="پایین بردن" @click="move(index, 1)">⌄</button><button type="button" aria-label="حذف از صف" @click="remove(index)">×</button></div>
            <label class="music-volume">🔊 <input v-model.number="volume" type="range" min="0" max="1" step="0.01" aria-label="صدا"></label>
        </div>
    </section>
</template>

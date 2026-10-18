<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import api from '../lib/api';
import ChatMedia from '../components/ChatMedia.vue';
import ChatReadReceipt from '../components/ChatReadReceipt.vue';
import ChatUserAvatar from '../components/ChatUserAvatar.vue';
import { requestChatNotifications, showChatNotification } from '../lib/pwa';

const auth = useAuthStore();
const route = useRoute();
const isAdminInbox = computed(() => route.meta.adminInbox === true);
const conversations = ref([]);
const conversationPage = ref(1);
const conversationLastPage = ref(1);
const loadingMoreConversations = ref(false);
const adminMessages = ref([]);
const messages = ref([]);
const messagesElement = ref(null);
const newMessageCount = ref(0);
const nextBeforeId = ref(null);
const loadingOlder = ref(false);
const selected = ref(null);
const people = ref([]);
const query = ref('');
const draft = ref('');
const file = ref(null);
const mediaPicker = ref(null);
const musicPicker = ref(null);
const documentPicker = ref(null);
const showMediaMenu = ref(false);
const openMessageMenuId = ref(null);
const messageMenuStyle = ref({});
const highlightedMessageId = ref(null);
const swipeStart = ref(null);
const swipeMessageId = ref(null);
const swipeOffset = ref(0);
let suppressMessageClick = false;
const replyTarget = ref(null);
const editingMessageId = ref(null);
const recordingMode = ref('');
const recordingSeconds = ref(0);
const recordingPreview = ref(null);
const videoPreviewElement = ref(null);
const loading = ref(true);
const sending = ref(false);
const error = ref('');
const showProfile = ref(false);
const showSettings = ref(false);
const nightMode = ref(localStorage.getItem('private-chat-night-mode') === 'true');
const chatProfile = ref({ nickname: '', avatar_url: null });
const nicknameDraft = ref('');
const profilePhoto = ref(null);
const profilePhotoFile = ref(null);
const clearProfilePhoto = ref(false);
const profileSaving = ref(false);
const profileError = ref('');
const avatarPhotos = ref({});
const avatarPhotoUrls = ref({});
const activeAvatar = computed(() => selected.value?.user?.id ? avatarPhotos.value[selected.value.user.id] : null);
watch(selected, (value) => { document.body.classList.toggle('chat-conversation-open', !!value); }, { immediate: true });
let poller;
let presenceTimer;
let visualViewportTarget = null;
let syncing = false;
let readSyncPromise = null;
const readThroughIds = new Map();
const pendingReadTargets = new Map();
let mediaRecorder = null;
let recordStream = null;
let recordChunks = [];
let recordTimer = null;
let cancelRecording = false;

const loadSidebar = async ({ page = 1, append = false, preserve = false } = {}) => {
    try {
        if (isAdminInbox.value) {
            const response = (await api.get('/private-chat/admin/inbox', { params: { page } })).data;
            conversationPage.value = response.meta.current_page || page;
            conversationLastPage.value = response.meta.last_page || 1;
            if (append || preserve) {
                conversations.value = [...new Map([...conversations.value, ...response.data].map((item) => [item.id, item])).values()];
            } else conversations.value = response.data;
        } else conversations.value = (await api.get('/private-chat/conversations')).data.data;
    } catch { error.value = 'گرفتن فهرست گفتگوها ممکن نشد.'; }
    finally { loading.value = false; }
};

const updatePresence = async () => {
    if (document.visibilityState !== 'visible') return;
    try { await api.post('/private-chat/presence'); } catch { /* Presence is best-effort; chat sync continues independently. */ }
};

const presenceLabel = (user) => {
    if (user?.is_online) return 'آنلاین';
    if (!user?.last_seen_at) return 'آفلاین';
    const seenAt = new Date(user.last_seen_at);
    if (Number.isNaN(seenAt.getTime())) return 'آفلاین';
    const minutes = Math.max(0, Math.floor((Date.now() - seenAt.getTime()) / 60_000));
    if (minutes < 1) return 'آخرین بازدید همین حالا';
    if (minutes < 60) return `آخرین بازدید ${minutes.toLocaleString('fa-IR')} دقیقه پیش`;
    const hours = Math.floor(minutes / 60);
    if (hours < 24) return `آخرین بازدید ${hours.toLocaleString('fa-IR')} ساعت پیش`;
    return `آخرین بازدید ${seenAt.toLocaleDateString('fa-IR', { month: 'short', day: 'numeric' })}`;
};

const formatMessageTime = (value) => {
    if (!value) return '';
    return new Date(value).toLocaleTimeString('fa-IR', { hour: '2-digit', minute: '2-digit' });
};

const formatConversationTime = (value) => {
    if (!value) return '';
    const date = new Date(value);
    const today = new Date();
    const daysAgo = Math.floor((new Date(today.getFullYear(), today.getMonth(), today.getDate()) - new Date(date.getFullYear(), date.getMonth(), date.getDate())) / 86_400_000);
    if (daysAgo === 0) return formatMessageTime(value);
    if (daysAgo > 0 && daysAgo < 7) return date.toLocaleDateString('fa-IR', { weekday: 'short' });
    return date.toLocaleDateString('fa-IR', { month: 'numeric', day: 'numeric' });
};

const messagePreview = (message) => message?.body?.trim()
    || (message?.attachment ? `📎 ${message.attachment.name || 'فایل'}` : 'گفتگوی جدید');

const loadMoreConversations = async () => {
    if (loadingMoreConversations.value || conversationPage.value >= conversationLastPage.value) return;
    loadingMoreConversations.value = true;
    try { await loadSidebar({ page: conversationPage.value + 1, append: true }); }
    finally { loadingMoreConversations.value = false; }
};

const loadMessages = async ({ beforeId = null, preserve = false } = {}) => {
    if (!selected.value) return;
    const conversationId = selected.value.id;
    try {
        const previousIds = new Set(messages.value.map((message) => message.id));
        const previousScrollHeight = messagesElement.value?.scrollHeight || 0;
        const previousScrollTop = messagesElement.value?.scrollTop || 0;
        const result = (await api.get(`/private-chat/conversations/${conversationId}/messages`, { params: beforeId ? { before_id: beforeId } : {} })).data.data;
        if (selected.value?.id !== conversationId) return;
        if (beforeId) {
            messages.value = [...result.messages, ...messages.value];
            nextBeforeId.value = result.next_before_id;
            await nextTick();
            if (messagesElement.value) messagesElement.value.scrollTop = previousScrollTop + (messagesElement.value.scrollHeight - previousScrollHeight);
            markVisibleIncomingAsRead();
        }
        else if (preserve) {
            const shouldFollowLatest = isMessagesNearBottom();
            const addedMessages = result.messages.filter((message) => !previousIds.has(message.id));
            if (!messages.value.length || messages.value[0].id >= (result.messages[0]?.id || 0)) nextBeforeId.value = result.next_before_id;
            const byId = new Map([...messages.value, ...result.messages].map((message) => [message.id, message]));
            messages.value = [...byId.values()].sort((a, b) => a.id - b.id);
            await nextTick();
            if (addedMessages.length) {
                if (shouldFollowLatest) scrollToLatest();
                else newMessageCount.value += addedMessages.length;
                addedMessages.filter((message) => message.sender_id !== auth.user?.id).forEach((message) => {
                    showChatNotification({
                        title: selected.value?.user?.name || 'پیام جدید',
                        body: message.attachment ? 'یک فایل جدید دریافت کردید.' : 'یک پیام جدید دریافت کردید.',
                        conversationId,
                    });
                });
            }
            markVisibleIncomingAsRead();
        } else {
            messages.value = result.messages;
            nextBeforeId.value = result.next_before_id;
            newMessageCount.value = 0;
            await nextTick();
            scrollToLatest('auto');
            markVisibleIncomingAsRead();
        }
    }
    catch { error.value = 'گرفتن پیام‌ها ممکن نشد.'; }
};

const syncVisualViewport = () => {
    const shell = document.querySelector('.chat-shell');
    if (!shell) return;
    const viewport = window.visualViewport;
    shell.style.setProperty('--chat-visual-height', `${Math.round(viewport?.height || window.innerHeight)}px`);
    shell.style.setProperty('--chat-visual-top', `${Math.round(viewport?.offsetTop || 0)}px`);
};

const dismissMediaMenuOnOutside = (event) => {
    const target = event.target instanceof Element ? event.target : event.target?.parentElement;
    if (showMediaMenu.value && !target?.closest('.chat-media-menu, .chat-attach-button')) showMediaMenu.value = false;
    if (openMessageMenuId.value && !target?.closest('.chat-message-menu')) {
        openMessageMenuId.value = null;
        messageMenuStyle.value = {};
        suppressMessageClick = true;
        setTimeout(() => { suppressMessageClick = false; }, 350);
    }
};
const handleQueueClosedOutside = () => {
    suppressMessageClick = true;
    setTimeout(() => { suppressMessageClick = false; }, 350);
};

const toggleMessageMenu = (message, event) => {
    event?.stopPropagation();
    if (suppressMessageClick) { suppressMessageClick = false; return; }
    if (openMessageMenuId.value === message.id) { openMessageMenuId.value = null; return; }
    openMessageMenuId.value = message.id;
    const target = event?.currentTarget?.querySelector?.('.chat-bubble, .chat-media, .chat-reply-preview') || event?.currentTarget;
    const rect = target?.getBoundingClientRect?.();
    if (!rect) return;
    const menuWidth = Math.min(220, Math.max(170, window.innerWidth - 24));
    const menuHeight = message.attachment ? 260 : 220;
    const gap = 7;
    const left = Math.max(12, Math.min(window.innerWidth - menuWidth - 12, message.sender_id === auth.user?.id ? rect.right - menuWidth : rect.left));
    const below = rect.bottom + gap;
    const top = below + menuHeight <= window.innerHeight - 8 ? below : Math.max(8, rect.top - menuHeight - gap);
    messageMenuStyle.value = { left: `${Math.round(left)}px`, top: `${Math.round(top)}px`, width: `${Math.round(menuWidth)}px` };
    nextTick(() => {
        const menu = document.querySelector('.chat-message-menu');
        if (!menu) return;
        const actual = menu.getBoundingClientRect();
        const actualTop = actual.height <= window.innerHeight - rect.bottom - gap
            ? rect.bottom + gap
            : Math.max(8, rect.top - actual.height - gap);
        const actualLeft = Math.max(12, Math.min(window.innerWidth - actual.width - 12, message.sender_id === auth.user?.id ? rect.right - actual.width : rect.left));
        messageMenuStyle.value = { left: `${Math.round(actualLeft)}px`, top: `${Math.round(actualTop)}px`, width: `${Math.round(actual.width)}px` };
    });
};

const startMessageSwipe = (message, event) => {
    if (event.pointerType === 'mouse') return;
    const point = event.touches?.[0] || event;
    if (point?.clientX == null) return;
    swipeStart.value = { message, x: point.clientX, y: point.clientY };
    swipeMessageId.value = message.id;
    swipeOffset.value = 0;
};

const moveMessageSwipe = (message, event) => {
    const start = swipeStart.value;
    const point = event.touches?.[0] || event;
    if (!start || start.message.id !== message.id || !point || point.clientX == null) return;
    const dx = Math.min(0, Math.max(-82, point.clientX - start.x));
    const dy = Math.abs(point.clientY - start.y);
    if (dy < 34 || Math.abs(dx) > dy) swipeOffset.value = dx;
};

const finishMessageSwipe = (message, event) => {
    const start = swipeStart.value;
    swipeStart.value = null;
    swipeMessageId.value = null;
    swipeOffset.value = 0;
    if (!start || start.message.id !== message.id) return;
    const point = event.changedTouches?.[0] || event;
    if (!point || point.clientX == null) return;
    const dx = point.clientX - start.x;
    const dy = Math.abs(point.clientY - start.y);
    if (dx < -42 && dy < 55 && Math.abs(dx) > dy * 1.15) {
        suppressMessageClick = true;
        replyToMessage(message);
        setTimeout(() => { suppressMessageClick = false; }, 400);
    }
};

const scrollToMessage = async (messageId) => {
    openMessageMenuId.value = null;
    await nextTick();
    const target = messagesElement.value?.querySelector(`[data-message-id="${messageId}"]`);
    if (!target) return;
    target.scrollIntoView({ behavior: 'smooth', block: 'center' });
    highlightedMessageId.value = messageId;
    setTimeout(() => { if (highlightedMessageId.value === messageId) highlightedMessageId.value = null; }, 900);
};

const copyMessage = async (message) => {
    const text = message.body || message.attachment?.name || '';
    if (text && navigator.clipboard) await navigator.clipboard.writeText(text);
    else if (text) {
        const input = document.createElement('textarea');
        input.value = text; input.setAttribute('readonly', ''); input.style.position = 'fixed'; input.style.opacity = '0';
        document.body.appendChild(input); input.select(); document.execCommand('copy'); input.remove();
    }
    openMessageMenuId.value = null;
};

const replyToMessage = (message) => {
    replyTarget.value = message;
    openMessageMenuId.value = null;
    nextTick(() => document.querySelector('.chat-compose textarea')?.focus());
};

const editMessage = (message) => {
    if (!message.body) return;
    editingMessageId.value = message.id;
    draft.value = message.body;
    openMessageMenuId.value = null;
    nextTick(() => document.querySelector('.chat-compose textarea')?.focus());
};

const cancelEdit = () => { editingMessageId.value = null; draft.value = ''; };

const pinMessage = async (message) => {
    try { await api.post(`/private-chat/messages/${message.id}/pin`); await loadMessages({ preserve: true }); }
    catch { error.value = 'پین کردن پیام انجام نشد.'; }
    openMessageMenuId.value = null;
};

const deleteMessage = async (message) => {
    if (!window.confirm('این پیام حذف شود؟')) return;
    try { await api.delete(`/private-chat/messages/${message.id}`); messages.value = messages.value.filter((item) => item.id !== message.id); }
    catch { error.value = 'حذف پیام انجام نشد.'; }
    openMessageMenuId.value = null;
};

const openMediaPicker = (picker) => {
    if (!picker) return;
    try {
        if (typeof picker.showPicker === 'function') picker.showPicker();
        else picker.click();
    } catch {
        picker.click();
    }
    showMediaMenu.value = false;
};

const isMessagesNearBottom = () => {
    const element = messagesElement.value;
    return !element || element.scrollHeight - element.scrollTop - element.clientHeight < 100;
};

const scrollToLatest = (behavior = 'smooth') => {
    const element = messagesElement.value;
    if (element) element.scrollTo({ top: element.scrollHeight, behavior });
    newMessageCount.value = 0;
};

const handleMessagesScroll = () => {
    openMessageMenuId.value = null;
    if (isMessagesNearBottom()) newMessageCount.value = 0;
    markVisibleIncomingAsRead();
};

const flushReadTargets = async () => {
    if (readSyncPromise || !pendingReadTargets.size) return readSyncPromise;
    readSyncPromise = (async () => {
        while (pendingReadTargets.size) {
            const [conversationId, messageId] = pendingReadTargets.entries().next().value;
            pendingReadTargets.delete(conversationId);
            try {
                await api.post(`/private-chat/conversations/${conversationId}/read`, { message_id: messageId });
                const confirmedReadThrough = Math.max(readThroughIds.get(conversationId) || 0, messageId);
                readThroughIds.set(conversationId, confirmedReadThrough);
                if ((pendingReadTargets.get(conversationId) || 0) <= confirmedReadThrough) pendingReadTargets.delete(conversationId);
            } catch {
                pendingReadTargets.set(conversationId, Math.max(pendingReadTargets.get(conversationId) || 0, messageId));
                break;
            }
        }
    })().finally(() => {
        readSyncPromise = null;
        if (pendingReadTargets.size) setTimeout(flushReadTargets, 1500);
    });
    return readSyncPromise;
};

const markVisibleIncomingAsRead = () => {
    if (isAdminInbox.value || document.visibilityState !== 'visible' || !selected.value || !messagesElement.value) return;
    const container = messagesElement.value;
    const viewport = container.getBoundingClientRect();
    let highestVisibleId = 0;
    container.querySelectorAll('.chat-message:not(.mine)').forEach((messageElement) => {
        const bounds = messageElement.getBoundingClientRect();
        const visibleHeight = Math.max(0, Math.min(bounds.bottom, viewport.bottom) - Math.max(bounds.top, viewport.top));
        const visibleThreshold = Math.min(32, Math.min(bounds.height, container.clientHeight) * 0.35);
        if (visibleHeight >= visibleThreshold) highestVisibleId = Math.max(highestVisibleId, Number(messageElement.dataset.messageId) || 0);
    });

    if (!highestVisibleId) return;
    const conversationId = selected.value.id;
    const alreadyReadThrough = readThroughIds.get(conversationId) || 0;
    const pendingReadThrough = pendingReadTargets.get(conversationId) || 0;
    const targetReadThrough = Math.max(highestVisibleId, pendingReadThrough);
    if (targetReadThrough <= alreadyReadThrough) return;
    pendingReadTargets.set(conversationId, targetReadThrough);
    flushReadTargets();
};

const loadOlder = async () => {
    if (!nextBeforeId.value || loadingOlder.value) return;
    loadingOlder.value = true;
    try { await loadMessages({ beforeId: nextBeforeId.value }); }
    finally { loadingOlder.value = false; }
};

const loadProtectedAvatar = async (avatarUrl) => {
    if (!avatarUrl) return null;
    try {
        const response = await api.get(avatarUrl, { responseType: 'blob' });
        return URL.createObjectURL(response.data);
    } catch { return null; }
};

const loadPrivateProfile = async () => {
    try {
        const profile = (await api.get('/private-chat/profile')).data.data;
        chatProfile.value = profile;
        nicknameDraft.value = profile.nickname || auth.user?.name || '';
        profilePhoto.value = await loadProtectedAvatar(profile.avatar_url);
    } catch { profileError.value = 'دریافت اطلاعات پروفایل انجام نشد.'; }
};

const openProfile = () => {
    nicknameDraft.value = chatProfile.value.nickname || auth.user?.name || '';
    profileError.value = '';
    showProfile.value = true;
};

const openConversation = async (conversation) => {
    selected.value = conversation;
    newMessageCount.value = 0;
    await Promise.all([loadMessages(), loadConversationAvatar(conversation.user)]);
};

const loadConversationAvatar = async (user) => {
    if (!user?.id) return;
    if (!user.avatar_url) {
        if (avatarPhotos.value[user.id]) URL.revokeObjectURL(avatarPhotos.value[user.id]);
        const nextPhotos = { ...avatarPhotos.value };
        const nextUrls = { ...avatarPhotoUrls.value };
        delete nextPhotos[user.id];
        delete nextUrls[user.id];
        avatarPhotos.value = nextPhotos;
        avatarPhotoUrls.value = nextUrls;
        return;
    }
    if (avatarPhotoUrls.value[user.id] === user.avatar_url) return;
    if (avatarPhotos.value[user.id]) URL.revokeObjectURL(avatarPhotos.value[user.id]);
    avatarPhotos.value = { ...avatarPhotos.value, [user.id]: null };
    avatarPhotoUrls.value = { ...avatarPhotoUrls.value, [user.id]: user.avatar_url };
    const photo = await loadProtectedAvatar(user.avatar_url);
    if (photo) avatarPhotos.value = { ...avatarPhotos.value, [user.id]: photo };
};

const chooseProfilePhoto = (event) => {
    const photo = event.target.files?.[0];
    event.target.value = '';
    if (!photo) return;
    if (!photo.type.startsWith('image/') || photo.size > 5 * 1024 * 1024) {
        profileError.value = 'یک تصویر حداکثر ۵ مگابایتی انتخاب کنید.';
        return;
    }
    if (profilePhoto.value?.startsWith('blob:')) URL.revokeObjectURL(profilePhoto.value);
    profilePhotoFile.value = photo;
    profilePhoto.value = URL.createObjectURL(photo);
    clearProfilePhoto.value = false;
    profileError.value = '';
};

const removeProfilePhoto = () => {
    if (profilePhoto.value?.startsWith('blob:')) URL.revokeObjectURL(profilePhoto.value);
    profilePhoto.value = null;
    profilePhotoFile.value = null;
    clearProfilePhoto.value = true;
};

const savePrivateProfile = async () => {
    const nickname = nicknameDraft.value.trim();
    if (!nickname) { profileError.value = 'نیک‌نیم را وارد کنید.'; return; }
    profileSaving.value = true;
    profileError.value = '';
    const data = new FormData();
    data.append('nickname', nickname);
    if (profilePhotoFile.value) data.append('avatar', profilePhotoFile.value);
    else if (clearProfilePhoto.value) data.append('clear_avatar', '1');
    try {
        const response = await api.post('/private-chat/profile', data, { headers: { 'Content-Type': 'multipart/form-data' } });
        chatProfile.value = response.data.data;
        nicknameDraft.value = response.data.data.nickname;
        if (response.data.data.avatar_url) {
            const savedPhoto = await loadProtectedAvatar(response.data.data.avatar_url);
            if (savedPhoto) {
                if (profilePhoto.value?.startsWith('blob:')) URL.revokeObjectURL(profilePhoto.value);
                profilePhoto.value = savedPhoto;
            }
        } else if (profilePhoto.value?.startsWith('blob:')) {
            URL.revokeObjectURL(profilePhoto.value);
            profilePhoto.value = null;
        }
        profilePhotoFile.value = null;
        clearProfilePhoto.value = false;
        showProfile.value = false;
    } catch (requestError) {
        profileError.value = Object.values(requestError.response?.data?.errors || {})[0]?.[0] || 'ذخیرهٔ پروفایل انجام نشد.';
    } finally { profileSaving.value = false; }
};

const syncLive = async () => {
    if (syncing) return;
    syncing = true;
    try {
        await loadSidebar({ preserve: true });
        if (selected.value && !isAdminInbox.value) {
            const fresh = conversations.value.find((conversation) => conversation.id === selected.value.id);
            if (fresh) {
                selected.value = { ...selected.value, user: fresh.user };
                await loadConversationAvatar(fresh.user);
            }
        }
        if (selected.value) await loadMessages({ preserve: true });
    }
    finally { syncing = false; }
};

const searchPeople = async () => {
    if (query.value.trim().length < 2) { people.value = []; return; }
    try { people.value = (await api.get('/private-chat/users', { params: { q: query.value.trim() } })).data.data; } catch { people.value = []; }
};

const startConversation = async (person) => {
    try {
        const result = (await api.post('/private-chat/conversations', { user_id: person.id })).data.data;
        query.value = ''; people.value = [];
        await loadSidebar();
        const conversation = conversations.value.find((item) => item.id === result.id) || { id: result.id, user: person };
        await openConversation(conversation);
    } catch { error.value = 'شروع گفتگو ممکن نشد.'; }
};

const sendMessage = async () => {
    if ((!draft.value.trim() && !file.value) || !selected.value || sending.value) return;
    sending.value = true; error.value = '';
    try {
        if (editingMessageId.value) {
            await api.patch(`/private-chat/messages/${editingMessageId.value}`, { body: draft.value.trim() });
            const edited = messages.value.find((message) => message.id === editingMessageId.value);
            if (edited) { edited.body = draft.value.trim(); edited.edited_at = new Date().toISOString(); }
            cancelEdit();
            return;
        }
        const data = new FormData();
        if (draft.value.trim()) data.append('body', draft.value.trim());
        if (file.value) data.append('file', file.value);
        if (replyTarget.value) data.append('reply_to_id', replyTarget.value.id);
        await api.post(`/private-chat/conversations/${selected.value.id}/messages`, data, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        draft.value = ''; file.value = null; replyTarget.value = null;
        [mediaPicker.value, musicPicker.value, documentPicker.value].forEach((picker) => { if (picker) picker.value = ''; });
        showMediaMenu.value = false;
        await Promise.all([loadMessages(), loadSidebar()]);
    } catch (e) { error.value = e.response?.data?.errors?.file?.[0] || e.response?.data?.errors?.message?.[0] || 'ارسال پیام انجام نشد.'; }
    finally { sending.value = false; }
};

const pickAttachment = (event) => {
    const chosen = event.target.files?.[0];
    event.target.value = '';
    if (!chosen) return;
    if (chosen.size > 25 * 1024 * 1024) { error.value = 'حداکثر اندازهٔ فایل ۲۵ مگابایت است.'; return; }
    file.value = chosen;
    showMediaMenu.value = false;
    error.value = '';
};

const sendRecordedMedia = async (recordedFile) => {
    file.value = recordedFile;
    await sendMessage();
};

const startRecording = async (mode) => {
    showMediaMenu.value = false;
    error.value = '';
    if (!window.isSecureContext) {
        error.value = 'برای ضبط صدا یا ویدیو در گوشی، سایت باید با نشانی امن HTTPS باز شود.';
        return;
    }
    if (!navigator.mediaDevices?.getUserMedia) {
        error.value = 'این مرورگر به میکروفن و دوربین دسترسی نمی‌دهد. صفحه را در مرورگر اصلی گوشی و با HTTPS باز کنید.';
        return;
    }
    if (typeof MediaRecorder === 'undefined') {
        error.value = 'ضبط رسانه در این نسخهٔ مرورگر پشتیبانی نمی‌شود؛ مرورگر گوشی را به‌روز کنید.';
        return;
    }
    try {
        recordStream = await navigator.mediaDevices.getUserMedia(mode === 'voice'
            ? { audio: true }
            : { audio: true, video: { facingMode: 'user', width: { ideal: 480 }, height: { ideal: 640 }, frameRate: { ideal: 24, max: 30 } } });
        const formats = mode === 'voice'
            ? ['audio/webm;codecs=opus', 'audio/webm', 'audio/mp4']
            : ['video/webm;codecs=vp9,opus', 'video/webm;codecs=vp8,opus', 'video/webm', 'video/mp4'];
        const mimeType = typeof MediaRecorder.isTypeSupported === 'function' ? formats.find((format) => MediaRecorder.isTypeSupported(format)) : undefined;
        const recorderOptions = { audioBitsPerSecond: 64000, ...(mode === 'video' ? { videoBitsPerSecond: 900000 } : {}) };
        if (mimeType) recorderOptions.mimeType = mimeType;
        mediaRecorder = new MediaRecorder(recordStream, recorderOptions);
        recordChunks = [];
        cancelRecording = false;
        mediaRecorder.ondataavailable = (event) => { if (event.data.size) recordChunks.push(event.data); };
        mediaRecorder.onstop = async () => {
            const shouldDiscard = cancelRecording;
            const type = mediaRecorder?.mimeType || (mode === 'voice' ? 'audio/webm' : 'video/webm');
            const result = new Blob(recordChunks, { type });
            recordStream?.getTracks().forEach((track) => track.stop());
            recordStream = null;
            recordingPreview.value = null;
            recordingMode.value = '';
            clearInterval(recordTimer);
            recordTimer = null;
            mediaRecorder = null;
            if (shouldDiscard || !result.size) return;
            if (result.size > 25 * 1024 * 1024) { error.value = 'رسانهٔ ضبط‌شده از حد ۲۵ مگابایت بیشتر شد.'; return; }
            const extension = type.includes('mp4') ? 'mp4' : 'webm';
            const name = `${mode === 'voice' ? 'voice-message' : 'video-message'}-${Date.now()}.${extension}`;
            await sendRecordedMedia(new File([result], name, { type }));
        };
        mediaRecorder.start(250);
        recordingMode.value = mode;
        recordingPreview.value = mode === 'video' ? recordStream : null;
        recordingSeconds.value = 0;
        recordTimer = setInterval(() => {
            recordingSeconds.value += 1;
            if (recordingSeconds.value >= 60) stopRecording();
        }, 1000);
    } catch (cause) {
        recordStream?.getTracks().forEach((track) => track.stop());
        recordStream = null;
        recordingPreview.value = null;
        recordingMode.value = '';
        error.value = cause?.name === 'NotAllowedError' || cause?.name === 'SecurityError'
            ? 'اجازهٔ دسترسی به میکروفن یا دوربین داده نشد. مجوز سایت را در تنظیمات مرورگر فعال کنید.'
            : cause?.name === 'NotFoundError'
                ? 'میکروفن یا دوربین سازگار روی این دستگاه پیدا نشد.'
                : 'شروع ضبط ممکن نشد. دسترسی دستگاه را بررسی کنید و دوباره امتحان کنید.';
    }
};

const stopRecording = (discard = false) => {
    if (!mediaRecorder || mediaRecorder.state === 'inactive') return;
    cancelRecording = discard;
    mediaRecorder.stop();
};

watch([recordingMode, recordingPreview], async () => {
    await nextTick();
    if (videoPreviewElement.value && recordingPreview.value) {
        videoPreviewElement.value.srcObject = recordingPreview.value;
        try { await videoPreviewElement.value.play(); } catch { /* muted preview may wait for a frame */ }
    }
});

const download = async (attachment) => {
    try {
        const response = await api.get(attachment.url, { responseType: 'blob' });
        const url = URL.createObjectURL(response.data);
        const link = document.createElement('a'); link.href = url; link.download = attachment.name; link.click(); URL.revokeObjectURL(url);
    } catch { error.value = 'دریافت فایل ممکن نشد.'; }
};

watch(query, () => { clearTimeout(window.__privateChatSearch); window.__privateChatSearch = setTimeout(searchPeople, 250); });
watch(nightMode, (enabled) => localStorage.setItem('private-chat-night-mode', String(enabled)));
const handlePresenceVisibility = () => {
    if (document.visibilityState === 'visible') {
        updatePresence();
        markVisibleIncomingAsRead();
    }
};

onMounted(async () => {
    await Promise.all([loadPrivateProfile(), loadSidebar()]);
    syncVisualViewport();
    visualViewportTarget = window.visualViewport;
    visualViewportTarget?.addEventListener('resize', syncVisualViewport);
    visualViewportTarget?.addEventListener('scroll', syncVisualViewport);
    window.addEventListener('resize', syncVisualViewport);
    document.addEventListener('pointerdown', dismissMediaMenuOnOutside, true);
    window.addEventListener('private-chat:queue-closed-outside', handleQueueClosedOutside);
    updatePresence();
    requestChatNotifications();
    presenceTimer = setInterval(updatePresence, 15_000);
    document.addEventListener('visibilitychange', handlePresenceVisibility);
    poller = setInterval(syncLive, 1800);
});
onUnmounted(() => {
    document.body.classList.remove('chat-conversation-open');
    clearInterval(poller);
    clearInterval(presenceTimer);
    visualViewportTarget?.removeEventListener('resize', syncVisualViewport);
    visualViewportTarget?.removeEventListener('scroll', syncVisualViewport);
    window.removeEventListener('resize', syncVisualViewport);
    document.removeEventListener('pointerdown', dismissMediaMenuOnOutside, true);
    window.removeEventListener('private-chat:queue-closed-outside', handleQueueClosedOutside);
    document.removeEventListener('visibilitychange', handlePresenceVisibility);
    clearInterval(recordTimer);
    if (mediaRecorder?.state !== 'inactive') {
        cancelRecording = true;
        mediaRecorder?.stop();
    }
    recordStream?.getTracks().forEach((track) => track.stop());
    if (profilePhoto.value?.startsWith('blob:')) URL.revokeObjectURL(profilePhoto.value);
    Object.values(avatarPhotos.value).forEach((url) => URL.revokeObjectURL(url));
});
</script>

<template>
    <section class="chat-shell" :class="{ 'chat-night': nightMode }" aria-label="پیام‌های خصوصی" @pointerdown.capture="dismissMediaMenuOnOutside">
        <div class="chat-head">
            <div><div class="chat-brand"><span class="chat-brand-icon">✳</span><div><h1>{{ isAdminInbox ? 'صندوق پیام‌ها' : 'گفتگوهای خصوصی' }}</h1><p>فضای امن پیام‌رسانی</p></div></div></div>
            <span class="chat-secure">🔒 رمزنگاری‌شده در سرور</span>
            <div class="chat-head-actions">
                <button class="chat-head-button chat-profile-button" type="button" aria-label="پروفایل" title="پروفایل" @click="openProfile"><img v-if="profilePhoto" :src="profilePhoto" alt="" /><span v-else>{{ (chatProfile.nickname || auth.user?.name || '؟').slice(0, 1) }}</span></button>
                <button class="chat-head-button" type="button" aria-label="تنظیمات" title="تنظیمات" @click="showSettings = true">⚙</button>
            </div>
        </div>
        <div v-if="error" class="chat-error" role="alert">{{ error }}<button @click="error = ''" aria-label="بستن">×</button></div>
        <div class="chat-layout" :class="{ 'has-selected': !!selected }">
            <aside class="chat-sidebar">
                <div v-if="!isAdminInbox" class="chat-search"><span>⌕</span><input v-model="query" aria-label="جستجوی کاربران" placeholder="جستجوی نام یا ایمیل" /><button v-if="query" @click="query=''; people=[]">×</button></div>
                <div v-if="people.length" class="chat-results"><button v-for="person in people" :key="person.id" class="chat-person" @click="startConversation(person)"><span class="chat-avatar">{{ person.name.slice(0,1) }}</span><span><b>{{ person.name }}</b><small>{{ person.email }}</small></span><span class="chat-arrow">←</span></button></div>
                <div class="chat-list-title">{{ isAdminInbox ? 'آخرین پیام‌ها' : 'گفتگوها' }}</div>
                <div v-if="loading" class="chat-empty">در حال دریافت گفتگوها…</div>
                <button v-for="conversation in conversations" :key="conversation.id" class="chat-conversation" :class="{ active: selected?.id === conversation.id }" @click="openConversation(conversation)">
                    <ChatUserAvatar :user="conversation.user" /><span class="chat-conversation-info"><b>{{ conversation.user?.name }}</b><small>{{ messagePreview(conversation.last_message) }}</small></span><span class="chat-conversation-meta"><time v-if="conversation.last_message">{{ formatConversationTime(conversation.last_message.created_at) }}</time><span v-if="conversation.unread_count" class="chat-unread-badge">{{ conversation.unread_count > 99 ? '۹۹+' : conversation.unread_count.toLocaleString('fa-IR') }}</span><ChatReadReceipt v-else-if="conversation.last_message?.sender_id === auth.user?.id" class="chat-list-receipt" :read="!!conversation.last_message.read_at" /></span>
                </button>
                <div v-if="!loading && !conversations.length && !isAdminInbox" class="chat-empty">هنوز گفتگویی ندارید.<br>نام یا ایمیل فرد را جستجو کنید.</div>
                <button v-if="isAdminInbox && conversationPage < conversationLastPage" class="chat-load-older" :disabled="loadingMoreConversations" @click="loadMoreConversations">{{ loadingMoreConversations ? 'در حال دریافت…' : 'گفتگوهای قدیمی‌تر' }}</button>
            </aside>
            <section class="chat-main" aria-label="محتوای گفتگو">
                <template v-if="selected">
                    <header class="chat-room-head"><button class="chat-back" @click="selected=null">›</button><span class="chat-avatar"><img v-if="activeAvatar" :src="activeAvatar" alt="" /><span v-else>{{ (selected.user?.name || '؟').slice(0,1) }}</span></span><div><b>{{ selected.user?.name }}</b><small><i :class="{ online: selected.user?.is_online }"></i>{{ presenceLabel(selected.user) }}</small></div></header>
                    <div ref="messagesElement" class="chat-messages" aria-live="polite" @scroll.passive="handleMessagesScroll">
                        <div class="chat-date">پیام‌ها با رمزنگاری سمت سرور نگهداری می‌شوند</div>
                        <button v-if="nextBeforeId" class="chat-load-older" :disabled="loadingOlder" @click="loadOlder">{{ loadingOlder ? 'در حال دریافت…' : 'نمایش پیام‌های قدیمی‌تر' }}</button>
                        <article v-for="message in messages" :key="message.id" :data-message-id="message.id" class="chat-message" :class="{ mine: message.sender_id === auth.user?.id, 'is-pinned': message.pinned_at, 'message-highlighted': highlightedMessageId === message.id, 'is-swiping': swipeMessageId === message.id }" :style="swipeMessageId === message.id ? { '--reply-swipe-offset': `${swipeOffset}px` } : undefined" @click="toggleMessageMenu(message, $event)" @pointerdown="startMessageSwipe(message, $event)" @pointermove="moveMessageSwipe(message, $event)" @pointerup="finishMessageSwipe(message, $event)" @pointercancel="swipeStart = null; swipeMessageId = null; swipeOffset = 0" @touchstart.passive="startMessageSwipe(message, $event)" @touchmove.passive="moveMessageSwipe(message, $event)" @touchend.passive="finishMessageSwipe(message, $event)" @touchcancel.passive="swipeStart = null; swipeMessageId = null; swipeOffset = 0">
                            <small v-if="isAdminInbox" class="chat-sender">{{ message.sender_name }}</small>
                            <button v-if="message.reply_to" type="button" class="chat-reply-preview" @click.stop="scrollToMessage(message.reply_to.id)"><b>{{ message.reply_to.sender_name || 'پیام' }}</b><span>{{ message.reply_to.body || 'رسانه' }}</span></button>
                            <p v-if="message.body" class="chat-bubble">{{ message.body }}</p>
                            <ChatMedia v-if="message.attachment" :attachment="message.attachment" @download="download" />
                            <time><span v-if="message.pinned_at" class="chat-pinned-mark" title="پین‌شده">⌖</span><span v-if="message.edited_at" class="chat-edited-mark">ویرایش‌شده</span><span v-if="message.sender_id === auth.user?.id" class="chat-message-status"><ChatReadReceipt :read="!!message.read_at" /></span>{{ formatMessageTime(message.created_at) }}</time>
                            <div v-if="openMessageMenuId === message.id" class="chat-message-menu" :style="messageMenuStyle" role="menu" @click.stop>
                                <button type="button" role="menuitem" @click="replyToMessage(message)"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 17 4 12l5-5M5 12h9a6 6 0 0 1 6 6v1"/></svg><span>ریپلای</span></button>
                                <button type="button" role="menuitem" @click="copyMessage(message)"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="8" y="8" width="11" height="12" rx="2"/><path d="M16 8V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h2"/></svg><span>کپی</span></button>
                                <button type="button" role="menuitem" @click="pinMessage(message)"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m8 4 8 8-2 2 3 3-2 2-3-3-2 2-2-8zM12 14l-5 6"/></svg><span>{{ message.pinned_at ? 'برداشتن پین' : 'پین' }}</span></button>
                                <button v-if="message.attachment" type="button" role="menuitem" @click="download(message.attachment); openMessageMenuId = null"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12m0 0 4-4m-4 4-4-4M5 19h14"/></svg><span>دانلود</span></button>
                                <button v-if="message.sender_id === auth.user?.id && message.body" type="button" role="menuitem" @click="editMessage(message)"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m4 16-.8 4.8L8 20l10.8-10.8-4-4zM13.5 6.5l4 4"/></svg><span>ادیت</span></button>
                                <button v-if="message.sender_id === auth.user?.id" type="button" role="menuitem" class="danger" @click="deleteMessage(message)"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 7h14M10 11v6m4-6v6M9 7l1-2h4l1 2m-8 0 1 14h8l1-14"/></svg><span>دلیت</span></button>
                            </div>
                        </article>
                        <div v-if="!messages.length" class="chat-empty">اولین پیام این گفتگو را بنویسید.</div>
                    </div>
                    <button v-if="newMessageCount" class="chat-jump-latest" type="button" :aria-label="`رفتن به پیام‌های جدید؛ ${newMessageCount} پیام جدید`" @click="scrollToLatest()"><span aria-hidden="true">↓</span>{{ newMessageCount.toLocaleString('fa-IR') }} پیام جدید</button>
                    <div v-if="showMediaMenu && !isAdminInbox" class="chat-media-menu" role="menu" aria-label="انتخاب رسانه">
                        <div class="chat-media-menu-title">انتخاب پیوست</div>
                        <button type="button" role="menuitem" @click="openMediaPicker(mediaPicker)"><span class="media-icon media-icon-photo">▧</span><b>عکس و ویدیو</b><small>از گالری</small></button>
                        <button type="button" role="menuitem" @click="openMediaPicker(musicPicker)"><span class="media-icon media-icon-music">♫</span><b>موسیقی</b><small>پخش کنید</small></button>
                        <button type="button" role="menuitem" @click="openMediaPicker(documentPicker)"><span class="media-icon media-icon-file">⌁</span><b>فایل</b><small>اسناد و فایل</small></button>
                        <button type="button" role="menuitem" @click="startRecording('voice')"><span class="media-icon media-icon-voice">🎙</span><b>وویس مسیج</b><small>ضبط صدا</small></button>
                        <button type="button" role="menuitem" @click="startRecording('video')"><span class="media-icon media-icon-video">◉</span><b>ویدیو مسیج</b><small>ضبط دوربین</small></button>
                    </div>
                    <input ref="mediaPicker" type="file" accept="image/*,video/*" class="sr-only" @change="pickAttachment" />
                    <input ref="musicPicker" type="file" accept="audio/*" class="sr-only" @change="pickAttachment" />
                    <input ref="documentPicker" type="file" class="sr-only" @change="pickAttachment" />
                    <div v-if="replyTarget || editingMessageId" class="chat-compose-context"><span v-if="replyTarget">پاسخ به: {{ replyTarget.body || replyTarget.attachment?.name || 'رسانه' }}</span><span v-else>در حال ویرایش پیام</span><button type="button" aria-label="لغو" @click="replyTarget ? replyTarget = null : cancelEdit()">×</button></div>
                    <form v-if="!isAdminInbox" class="chat-compose" @submit.prevent="sendMessage">
                        <button class="chat-attach-button" type="button" :aria-expanded="showMediaMenu" aria-label="افزودن پیوست" @click="showMediaMenu = !showMediaMenu"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m8 12.5 7.2-7.2a3.2 3.2 0 0 1 4.5 4.5l-9.2 9.2a5 5 0 0 1-7.1-7.1l8.8-8.8"/></svg></button>
                        <div class="chat-compose-field"><div v-if="file" class="chat-file-chip"><span>📎</span><span>{{ file.name }}</span><button type="button" aria-label="حذف پیوست" @click="file=null">×</button></div><textarea :value="draft" rows="1" placeholder="پیام را بنویسید" aria-label="متن پیام" @input="draft = $event.target.value" @keydown.enter.exact.prevent="sendMessage"></textarea></div>
                        <button v-if="draft.trim() || file" class="chat-send" :disabled="sending" aria-label="ارسال پیام"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m21 3-7.2 18-3.4-7.4L3 10.2 21 3Z"/><path d="M10.4 13.6 21 3"/></svg></button>
                        <button v-else class="chat-voice-trigger" type="button" aria-label="ضبط وویس مسیج" @click="startRecording('voice')"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="9" y="2.5" width="6" height="12" rx="3"/><path d="M5.5 11.5a6.5 6.5 0 0 0 13 0M12 18v3.5M9 21.5h6"/></svg></button>
                    </form>                    <div v-if="recordingMode" class="chat-recording-overlay" role="dialog" aria-modal="true" aria-label="ضبط پیام">
                        <video v-if="recordingMode === 'video'" ref="videoPreviewElement" class="chat-recording-preview" autoplay muted playsinline></video>
                        <div v-else class="chat-recording-microphone">🎙</div>
                        <strong>{{ recordingMode === 'voice' ? 'در حال ضبط وویس' : 'در حال ضبط ویدیو مسیج' }}</strong>
                        <span class="chat-recording-time">{{ `0:${String(recordingSeconds).padStart(2, '0')}` }} / 1:00</span>
                        <div class="chat-recording-actions"><button type="button" @click="stopRecording(true)">لغو</button><button type="button" @click="stopRecording()">توقف و ارسال</button></div>
                    </div>
                    <div v-if="isAdminInbox && !recordingMode" class="chat-readonly">نمایش فقط‌خواندنی صندوق ادمین</div>
                </template>
                <div v-else class="chat-welcome"><div class="chat-welcome-icon">✉</div><h2>{{ isAdminInbox ? 'صندوق گفتگوها' : 'پیام‌رسان خصوصی' }}</h2><p>{{ isAdminInbox ? 'برای مشاهده پیام‌ها یک گفتگو را از فهرست انتخاب کنید.' : 'یک گفتگو را انتخاب کنید یا برای شروع، نام فردی را جستجو کنید.' }}</p><span>🔒 پیام‌ها و پیوست‌ها به‌صورت رمزنگاری‌شده ذخیره می‌شوند.</span></div>
            </section>
        </div>
        <div v-if="showProfile" class="chat-modal-backdrop" @click.self="showProfile = false" @keydown.esc.window="showProfile = false">
            <section class="chat-modal" role="dialog" aria-modal="true" aria-labelledby="chat-profile-title">
                <button class="chat-modal-close" type="button" aria-label="بستن" @click="showProfile = false">×</button>
                <div class="chat-profile-avatar"><img v-if="profilePhoto" :src="profilePhoto" alt="" /><span v-else>{{ (chatProfile.nickname || auth.user?.name || '؟').slice(0, 1) }}</span></div>
                <h2 id="chat-profile-title">پروفایل</h2>
                <label class="chat-profile-field"><span>نیک‌نیم</span><input v-model="nicknameDraft" maxlength="32" required autocomplete="nickname" placeholder="نیک‌نیم شما" /></label>
                <div class="chat-profile-photo-actions"><label class="chat-photo-pick">{{ profilePhoto ? 'تغییر عکس' : 'انتخاب عکس پروفایل' }}<input type="file" accept="image/jpeg,image/png,image/webp" @change="chooseProfilePhoto" /></label><button v-if="profilePhoto" type="button" class="chat-photo-remove" @click="removeProfilePhoto">حذف عکس</button></div>
                <div class="chat-profile-row"><span>حساب سایت</span><b>{{ auth.user?.name || '—' }}</b></div>
                <div class="chat-profile-row"><span>ایمیل</span><b dir="ltr">{{ auth.user?.email || '—' }}</b></div>
                <div class="chat-profile-note">این نام و عکس فقط برای پیام‌رسان خصوصی استفاده می‌شوند و پروفایل سایت را تغییر نمی‌دهند.</div>
                <div v-if="profileError" class="chat-profile-error" role="alert">{{ profileError }}</div>
                <button class="chat-modal-primary" type="button" :disabled="profileSaving" @click="savePrivateProfile">{{ profileSaving ? 'در حال ذخیره…' : 'ذخیرهٔ تغییرات' }}</button>
            </section>
        </div>
        <div v-if="showSettings" class="chat-modal-backdrop" @click.self="showSettings = false" @keydown.esc.window="showSettings = false">
            <section class="chat-modal chat-settings-modal" role="dialog" aria-modal="true" aria-labelledby="chat-settings-title">
                <button class="chat-modal-close" type="button" aria-label="بستن" @click="showSettings = false">×</button>
                <h2 id="chat-settings-title">تنظیمات</h2>
                <p class="chat-modal-subtitle">ظاهر و تجربهٔ پیام‌رسان را تنظیم کنید.</p>
                <div class="chat-settings-section-title">ظاهر</div>
                <label class="chat-setting-row">
                    <span class="chat-setting-icon">◐</span>
                    <span class="chat-setting-copy"><b>حالت شب</b><small>تم تیره برای استفاده در نور کم</small></span>
                    <input v-model="nightMode" type="checkbox" role="switch" aria-label="حالت شب" class="chat-switch" />
                </label>
                <div class="chat-settings-section-title">حساب کاربری</div>
                <button class="chat-setting-row chat-setting-action" type="button" @click="showSettings = false; showProfile = true"><span class="chat-setting-icon">●</span><span class="chat-setting-copy"><b>پروفایل</b><small>مشاهدهٔ اطلاعات حساب</small></span><span class="chat-setting-chevron">‹</span></button>
            </section>
        </div>
    </section>
</template>




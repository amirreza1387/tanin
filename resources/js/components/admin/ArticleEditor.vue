<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import Button from '../Button.vue';
import Input from '../Input.vue';
import Select from '../Select.vue';
import Card from '../Card.vue';
import Icon from '../Icon.vue';
import MediaPicker from './MediaPicker.vue';

const props = defineProps({
    article: { type: Object, required: true }, editing: Boolean, categories: { type: Array, default: () => [] }, tags: { type: Array, default: () => [] }, media: { type: Array, default: () => [] }, saving: Boolean, uploading: Boolean,
});
const emit = defineEmits(['save', 'cancel', 'upload', 'dirty-change']);
const draft = reactive({ ...props.article, tag_ids: [...(props.article.tag_ids || [])] });
const step = ref(1); const errors = reactive({}); const snapshot = ref(JSON.stringify(draft));
const autosaveState = ref('');
const draftKey = computed(() => `tanin:article-draft:${props.article.id || 'new'}`);
const selectedMedia = computed(() => props.media.find((item) => item.id == draft.featured_media_id));
const dirty = computed(() => JSON.stringify(draft) !== snapshot.value);
// Parent form fields also change during media uploads. Reset only when the editor receives a different article.
watch(() => props.article.id, () => { const value = props.article; Object.assign(draft, value, { tag_ids: [...(value.tag_ids || [])] }); snapshot.value = JSON.stringify(draft); step.value = 1; });
watch(dirty, (value) => emit('dirty-change', value), { immediate: true });
const validate = () => { Object.keys(errors).forEach((key) => delete errors[key]); if (!draft.title.trim()) errors.title = 'عنوان خبر الزامی است.'; if (!draft.body.trim()) errors.body = 'متن خبر الزامی است.'; return !Object.keys(errors).length; };
const next = (event) => { event?.preventDefault(); event?.stopPropagation(); if (step.value === 1 && !validate()) return; step.value = Math.min(3, step.value + 1); };
const back = () => { step.value = Math.max(1, step.value - 1); };
const submit = () => { if (step.value !== 3) return; if (!validate()) { step.value = 1; return; } Object.assign(props.article, draft, { tag_ids: [...draft.tag_ids] }); snapshot.value = JSON.stringify(draft); emit('save', { ...draft, tag_ids: [...draft.tag_ids] }); };
let autosaveTimer;
watch(draft, () => { clearTimeout(autosaveTimer); autosaveState.value = 'Saving draft…'; autosaveTimer = setTimeout(() => { localStorage.setItem(draftKey.value, JSON.stringify({ savedAt: Date.now(), data: draft })); autosaveState.value = 'Draft saved'; }, 700); }, { deep: true });
const restoreDraft = () => { try { const saved = JSON.parse(localStorage.getItem(draftKey.value)); if (saved?.data && confirm('A saved draft was found. Restore it?')) Object.assign(draft, saved.data); } catch { /* ignored */ } };
const shortcut = (event) => { if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 's') { event.preventDefault(); submit(); } if (event.key === 'Escape') emit('cancel'); };
onMounted(() => { restoreDraft(); window.addEventListener('keydown', shortcut); });
const beforeUnload = (event) => { if (dirty.value) { event.preventDefault(); event.returnValue = ''; } };
window.addEventListener('beforeunload', beforeUnload);
onBeforeUnmount(() => { window.removeEventListener('beforeunload', beforeUnload); window.removeEventListener('keydown', shortcut); clearTimeout(autosaveTimer); });
</script>

<template>
    <Card class="border border-divider" :padded="false">
        <div class="flex items-center justify-between border-b border-divider px-5 py-4"><div><h2 class="font-black">{{ editing ? 'ویرایش خبر' : 'انتشار خبر جدید' }}</h2><p class="mt-1 text-xs text-muted">مرحله {{ step }} از ۳</p></div><Button variant="ghost" size="sm" aria-label="بستن فرم مقاله" @click="emit('cancel')"><Icon name="close" :size="18" /></Button></div>
        <div class="flex gap-2 border-b border-divider px-5 py-3 text-xs font-bold"><button v-for="item in [[1, 'محتوا'], [2, 'دسته‌بندی'], [3, 'پیش‌نمایش']]" :key="item[0]" type="button" class="rounded-full px-3 py-2" :class="step === item[0] ? 'bg-red-50 text-brand-red' : 'text-muted'" @click.prevent="step = item[0]">{{ item[0] }}. {{ item[1] }}</button></div>
        <form class="grid gap-5 p-5" @submit.prevent="step === 3 ? submit() : next($event)">
            <div v-if="step === 1" class="grid gap-4"><Input v-model="draft.title" label="عنوان خبر" id="article-title" required :error="errors.title" placeholder="عنوان خبر را وارد کنید" /><div><label for="article-lead" class="ds-label">خلاصه خبر</label><textarea id="article-lead" v-model="draft.lead" class="ds-control min-h-24 w-full" placeholder="خلاصه کوتاه و قابل فهم"></textarea></div><div><label for="article-body" class="ds-label">متن کامل خبر <span class="text-red-600">*</span></label><textarea id="article-body" v-model="draft.body" class="ds-control min-h-64 w-full" :class="errors.body ? 'border-red-500' : ''" placeholder="متن کامل خبر"></textarea><p v-if="errors.body" class="mt-1 text-xs text-red-700" role="alert">{{ errors.body }}</p></div></div>
            <div v-else-if="step === 2" class="grid gap-4"><Select v-model="draft.category_id" label="دسته‌بندی" id="article-category" :options="[{ value: '', label: 'بدون دسته‌بندی' }, ...categories.map((item) => ({ value: item.id, label: item.name }))]" /><div><label class="ds-label">تصویر شاخص</label><MediaPicker v-model="draft.featured_media_id" :media="media" :uploading="uploading" @upload="emit('upload', $event)" /><div v-if="selectedMedia" class="mt-3 h-36 overflow-hidden rounded-lg bg-cream"><img :src="selectedMedia.url" alt="پیش‌نمایش تصویر شاخص" class="h-full w-full object-contain"></div></div><fieldset><legend class="ds-label">برچسب‌ها</legend><div class="flex flex-wrap gap-2"><label v-for="tag in tags" :key="tag.id" class="rounded-full bg-cream px-3 py-2 text-xs"><input v-model="draft.tag_ids" type="checkbox" :value="tag.id" class="ml-1">{{ tag.name }}</label></div></fieldset></div>
            <div v-else class="rounded-xl bg-cream p-5"><h3 class="text-xl font-black">{{ draft.title || 'بدون عنوان' }}</h3><p v-if="draft.lead" class="mt-3 font-bold leading-8">{{ draft.lead }}</p><div class="mt-4 whitespace-pre-line text-sm leading-8">{{ draft.body || 'متن خبر هنوز وارد نشده است.' }}</div><div class="mt-5 border-t border-divider pt-4 text-xs text-muted">{{ categories.find((item) => item.id == draft.category_id)?.name || 'بدون دسته‌بندی' }}</div></div>
            <div class="sticky bottom-0 -mx-5 flex flex-wrap items-center justify-between gap-2 border-t border-divider bg-white/95 px-5 py-3 backdrop-blur"><div class="flex gap-2"><Button v-if="step > 1" type="button" variant="secondary" @click.stop.prevent="back">قبلی</Button><Button v-if="step < 3" type="button" @click.stop.prevent="next">ادامه</Button><Button v-else type="submit" :loading="saving">ذخیره خبر</Button></div><Button type="button" variant="ghost" @click="emit('cancel')">انصراف</Button></div>
        </form>
    </Card>
</template>

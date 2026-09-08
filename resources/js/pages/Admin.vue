<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import api from '../lib/api';
import AdminLayout from '../components/AdminLayout.vue';
import ConfirmDialog from '../components/ConfirmDialog.vue';
import { firstError, notify } from '../lib/api';
import Button from '../components/Button.vue';
import Card from '../components/Card.vue';
import StatusBadge from '../components/StatusBadge.vue';
import ArticlesManager from '../components/admin/ArticlesManager.vue';
import DashboardAnalytics from '../components/admin/DashboardAnalytics.vue';
import AppSkeleton from '../components/AppSkeleton.vue';

const route = useRoute();
const section = computed(() => route.params.section || 'dashboard');
const loading = ref(false); const uploadProgress = ref(0); const message = ref(''); const error = ref('');
const confirmState = ref({ open: false, message: '', action: null });
const articles = ref([]); const comments = ref([]); const users = ref([]); const categories = ref([]); const tags = ref([]); const media = ref([]); const advertisements = ref([]);
const uploading = ref(false); const uploadPreview = ref(null); const uploadedMedia = ref(null);
const categoryForm = reactive({ name: '', parent_id: '', sort_order: 0 }); const tagForm = reactive({ name: '' });
const adForm = reactive({ title: '', placement: 'sidebar', media_id: '', link_url: '', is_active: true, starts_at: '', ends_at: '', sort_order: 0 });
const editingAd = ref(null); const showAdForm = ref(false);
const stats = computed(() => ({ articles: articles.value.length, comments: comments.value.length, users: users.value.length, categories: categories.value.length }));
const announce = (text) => { message.value = text; notify(text); setTimeout(() => message.value = '', 3000); };
const askConfirm = (message, action) => { confirmState.value = { open: true, message, action }; };
const cancelConfirm = () => { confirmState.value = { open: false, message: '', action: null }; };
const acceptConfirm = async () => { const action = confirmState.value.action; cancelConfirm(); if (action) await action(); };
const load = async () => {
  loading.value = true; error.value = '';
  try {
    if (['dashboard', 'articles'].includes(section.value)) { const r = await api.get('/management/articles?per_page=100'); articles.value = r.data.data || []; }
    if (section.value === 'comments') comments.value = (await api.get('/management/comments?per_page=100')).data.data || [];
    if (section.value === 'users') users.value = (await api.get('/management/users?per_page=100')).data.data || [];
    if (['dashboard', 'categories', 'articles'].includes(section.value)) categories.value = (await api.get('/categories')).data.data || [];
    if (['dashboard', 'tags', 'articles'].includes(section.value)) tags.value = (await api.get('/tags')).data.data || [];
    if (['articles', 'media'].includes(section.value)) media.value = (await api.get('/media?per_page=100')).data.data || [];
    if (section.value === 'advertisements') { advertisements.value = (await api.get('/management/advertisements')).data.data || []; media.value = (await api.get('/media?per_page=100')).data.data || []; }
  } catch (e) { error.value = e.response?.data?.errors?.message || 'دریافت اطلاعات پنل ناموفق بود.'; } finally { loading.value = false; }
};
onMounted(load); watch(section, load);
const moderate = async (comment, status) => { try { await api.patch(`/management/comments/${comment.id}`, { status }); notify('وضعیت نظر تغییر کرد.'); await load(); } catch (e) { error.value = firstError(e); } };
const saveCategory = async () => { try { await api.post('/categories', categoryForm); Object.assign(categoryForm, { name: '', parent_id: '', sort_order: 0 }); notify('دسته‌بندی اضافه شد.'); await load(); } catch (e) { error.value = firstError(e); } };
const removeCategory = async (item) => askConfirm(`حذف «${item.name}»؟`, async () => { try { await api.delete(`/categories/${item.id}`); announce('دسته‌بندی حذف شد.'); await load(); } catch (e) { error.value = firstError(e); notify(error.value, 'error'); } });
const saveTag = async () => { try { await api.post('/tags', tagForm); tagForm.name = ''; notify('برچسب اضافه شد.'); await load(); } catch (e) { error.value = firstError(e); } };
const removeTag = async (item) => askConfirm(`حذف «${item.name}»؟`, async () => { try { await api.delete(`/tags/${item.id}`); announce('برچسب حذف شد.'); await load(); } catch (e) { error.value = firstError(e); notify(error.value, 'error'); } });
const resetAd = () => { Object.assign(adForm, { title: '', placement: 'sidebar', media_id: '', link_url: '', is_active: true, starts_at: '', ends_at: '', sort_order: 0 }); editingAd.value = null; showAdForm.value = true; };
const editAd = (ad) => { editingAd.value = ad; Object.assign(adForm, { title: ad.title, placement: ad.placement, media_id: ad.media_id || '', link_url: ad.link_url || '', is_active: ad.is_active, starts_at: ad.starts_at ? ad.starts_at.slice(0, 16) : '', ends_at: ad.ends_at ? ad.ends_at.slice(0, 16) : '', sort_order: ad.sort_order || 0 }); showAdForm.value = true; };
const saveAd = async () => { try { if (editingAd.value) await api.put(`/management/advertisements/${editingAd.value.id}`, adForm); else await api.post('/management/advertisements', adForm); showAdForm.value = false; notify('تبلیغ ذخیره شد.'); await load(); } catch (e) { error.value = firstError(e); } };
const removeAd = async (ad) => askConfirm(`حذف «${ad.title}»؟`, async () => { try { await api.delete(`/management/advertisements/${ad.id}`); announce('تبلیغ حذف شد.'); await load(); } catch (e) { error.value = firstError(e); notify(error.value, 'error'); } });
const placementLabel = { sidebar: 'سایدبار', horizontal: 'افقی', footer: 'فوتر' };
const updateUser = async (user, role) => { await api.patch(`/management/users/${user.id}`, { role }); notify('نقش کاربر به‌روزرسانی شد.'); await load(); };
const uploadMedia = async (event) => {
  const file = event.target.files?.[0];
  if (!file) return;
  if (uploading.value) return;
  uploadPreview.value = file.type.startsWith('image/') ? URL.createObjectURL(file) : null;
  uploadedMedia.value = null; uploading.value = true; uploadProgress.value = 0; error.value = '';
  const data = new FormData(); data.append('file', file);
  try {
    const response = await api.post('/media', data, { headers: { 'Content-Type': 'multipart/form-data' }, onUploadProgress: (progress) => { uploadProgress.value = progress.total ? Math.round((progress.loaded * 100) / progress.total) : 0; } });
    uploadedMedia.value = response.data.data;
    media.value = [response.data.data, ...media.value];
    notify('رسانه با موفقیت آپلود شد.');
  } catch (e) { error.value = firstError(e); notify(error.value, 'error'); } finally { uploading.value = false; event.target.value = ''; }
};
const statusLabel = { draft: 'پیش‌نویس', published: 'منتشرشده', scheduled: 'زمان‌بندی‌شده' };
</script>
<template>
  <AdminLayout :section="section">
    <div v-if="message" class="ds-alert ds-alert-success mb-4" role="status">{{ message }}</div>
    <div v-if="error" class="ds-alert ds-alert-error mb-4" role="alert">{{ error }}</div>
    <div v-if="uploading" class="mb-4 rounded-lg bg-white p-3 shadow-sm" aria-live="polite"><div class="mb-1 flex justify-between text-xs text-muted"><span>پیشرفت آپلود</span><span>{{ uploadProgress }}٪</span></div><progress class="h-2 w-full accent-brand-red" :value="uploadProgress" max="100">{{ uploadProgress }}%</progress></div>
    <ConfirmDialog :open="confirmState.open" :message="confirmState.message" @cancel="cancelConfirm" @confirm="acceptConfirm" />
    <AppSkeleton v-if="loading" :variant="section === 'articles' || section === 'comments' || section === 'users' ? 'table' : 'news-grid'" />
    <template v-else>
      <DashboardAnalytics v-if="section === 'dashboard'" class="mb-6" />
      <div v-if="section === 'dashboard'" class="space-y-6">
        <div><h1 class="text-2xl font-black text-navy">نمای کلی سامانه</h1><p class="mt-1 text-sm text-muted">مدیریت محتوای طنین جنوب</p></div>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4"><RouterLink v-for="[key, label, value] in [['articles','اخبار',stats.articles],['comments','نظرات',stats.comments],['users','کاربران',stats.users],['categories','دسته‌بندی',stats.categories]]" :key="key" :to="`/admin/${key}`" class="ds-card p-5 transition hover:-translate-y-1 hover:shadow-md"><div class="text-sm text-muted">{{ label }}</div><div class="mt-3 text-3xl font-black text-navy">{{ value }}</div></RouterLink></div>
        <Card><div class="mb-4 flex items-center justify-between"><h2 class="font-black">آخرین اخبار</h2><RouterLink to="/admin/articles" class="text-xs text-brand-red">مشاهده همه</RouterLink></div><div class="space-y-2"><div v-for="article in articles.slice(0, 5)" :key="article.id" class="flex items-center justify-between border-b border-divider py-3 text-sm"><span class="truncate">{{ article.title }}</span><StatusBadge class="mr-3 shrink-0" :status="article.status" :label="statusLabel[article.status] || article.status" /></div></div></Card>
      </div>
      <ArticlesManager v-else-if="section === 'articles'" />
      <div v-else-if="section === 'comments'" class="rounded-2xl bg-white p-5"><h1 class="mb-5 text-2xl font-black text-navy">مدیریت نظرات</h1><div v-for="comment in comments" :key="comment.id" class="border-b border-divider py-4 last:border-0"><div class="flex justify-between text-xs text-muted"><span>{{ comment.user?.name }} · {{ comment.article?.title }}</span><span>{{ comment.status_label || comment.status }}</span></div><p class="my-2 text-sm leading-7">{{ comment.body }}</p><button v-if="comment.status !== 'approved'" class="ml-3 text-sm font-bold text-green-700" @click="moderate(comment, 'approved')">تأیید</button><button v-if="comment.status !== 'rejected'" class="text-sm font-bold text-red-700" @click="moderate(comment, 'rejected')">رد</button></div></div>
      <Card v-else-if="section === 'users'" :padded="false" class="overflow-x-auto"><table class="ds-table ds-table-mobile-cards min-w-[600px] md:min-w-0"><thead><tr><th>نام</th><th>ایمیل</th><th>نقش</th></tr></thead><tbody><tr v-for="user in users" :key="user.id"><td data-label="نام" class="ds-title-safe font-bold">{{ user.name }}</td><td data-label="ایمیل" class="break-all text-left">{{ user.email }}</td><td data-label="نقش"><select :value="user.role" class="ds-control max-w-36 py-2 text-xs" @change="updateUser(user, $event.target.value)"><option value="user">کاربر</option><option value="reporter">خبرنگار</option><option value="admin">مدیر</option></select></td></tr></tbody></table></Card>
      <div v-else-if="section === 'categories'" class="space-y-4"><h1 class="text-2xl font-black text-navy">دسته‌بندی‌ها</h1><form class="flex flex-wrap gap-2 rounded-xl bg-white p-4" @submit.prevent="saveCategory"><input v-model="categoryForm.name" required class="rounded-lg border border-divider px-3 py-2" placeholder="نام دسته‌بندی"><input v-model="categoryForm.sort_order" type="number" class="w-24 rounded-lg border border-divider px-3 py-2" placeholder="ترتیب"><button class="rounded-lg bg-brand-red px-4 py-2 text-sm font-bold text-white">افزودن</button></form><div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3"><div v-for="cat in categories" :key="cat.id" class="flex items-center justify-between rounded-xl bg-white p-4"><span class="font-bold">{{ cat.name }}</span><button class="text-sm text-red-700" @click="removeCategory(cat)">حذف</button></div></div></div>
      <div v-else-if="section === 'tags'" class="space-y-4"><h1 class="text-2xl font-black text-navy">برچسب‌ها</h1><form class="flex gap-2 rounded-xl bg-white p-4" @submit.prevent="saveTag"><input v-model="tagForm.name" required class="flex-1 rounded-lg border border-divider px-3 py-2" placeholder="نام برچسب"><button class="rounded-lg bg-brand-red px-4 py-2 text-sm font-bold text-white">افزودن</button></form><div class="flex flex-wrap gap-3"><div v-for="tag in tags" :key="tag.id" class="flex items-center gap-3 rounded-full bg-white px-4 py-2 shadow-sm"><span>{{ tag.name }}</span><button class="text-red-700" @click="removeTag(tag)">×</button></div></div></div>
      <div v-else-if="section === 'media'" class="space-y-5"><div class="rounded-2xl bg-white p-6"><h1 class="text-2xl font-black text-navy">مدیریت رسانه</h1><p class="mt-2 text-sm leading-7 text-muted">تصویر یا ویدیوی خود را انتخاب کنید. حداکثر حجم فایل ۱۰۰ مگابایت است.</p><label class="mt-6 flex min-h-44 cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-divider bg-[#FAF8F5] p-6 text-center transition hover:border-brand-red hover:bg-red-50"><input class="sr-only" type="file" accept="image/*,video/*" :disabled="uploading" @change="uploadMedia"><span class="text-4xl text-brand-red">↑</span><span class="mt-2 font-bold">{{ uploading ? 'در حال آپلود...' : 'برای انتخاب فایل کلیک کنید' }}</span><span class="mt-1 text-xs text-muted">JPG، PNG، WEBP، GIF، MP4، MOV، AVI یا WEBM</span></label><div v-if="uploadPreview || uploadedMedia" class="mt-6 rounded-xl border border-divider p-4"><img v-if="uploadPreview" :src="uploadPreview" class="max-h-64 w-full rounded-lg object-contain"><video v-else-if="uploadedMedia?.type === 'video'" :src="uploadedMedia.url" controls class="max-h-64 w-full rounded-lg"></video><div v-if="uploadedMedia" class="mt-4 flex flex-wrap items-center justify-between gap-3"><span class="text-sm font-bold text-green-700">آپلود موفق بود · {{ uploadedMedia.original_name }}</span><a :href="uploadedMedia.url" target="_blank" rel="noopener" class="rounded-lg bg-navy px-3 py-2 text-xs font-bold text-white">مشاهده رسانه</a></div></div></div><div class="rounded-2xl bg-white p-5"><h2 class="mb-4 font-black text-navy">رسانه‌های آپلودشده</h2><div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4"><div v-for="item in media" :key="item.id" class="overflow-hidden rounded-xl border border-divider bg-[#FAF8F5]"><img v-if="item.type === 'image'" :src="item.url" :alt="item.original_name" class="aspect-square w-full object-cover"><video v-else :src="item.url" class="aspect-square w-full object-cover"></video><div class="truncate p-2 text-xs" :title="item.original_name">{{ item.original_name }}</div></div></div></div></div>
      <div v-else-if="section === 'advertisements'" class="space-y-5"><div class="flex items-center justify-between"><div><h1 class="text-2xl font-black text-navy">مدیریت تبلیغات</h1><p class="mt-1 text-sm text-muted">تبلیغات صفحه اصلی را مدیریت کنید.</p></div><button class="rounded-lg bg-brand-red px-4 py-2 text-sm font-bold text-white" @click="resetAd">+ تبلیغ جدید</button></div><div v-if="showAdForm" class="rounded-2xl bg-white p-5 shadow-sm"><div class="mb-4 flex items-center justify-between"><h2 class="font-black">{{ editingAd ? 'ویرایش تبلیغ' : 'افزودن تبلیغ' }}</h2><button class="text-xl text-muted" @click="showAdForm = false">×</button></div><form class="grid gap-4 md:grid-cols-2" @submit.prevent="saveAd"><input v-model="adForm.title" required class="rounded-lg border border-divider px-3 py-3" placeholder="عنوان تبلیغ"><select v-model="adForm.placement" class="rounded-lg border border-divider px-3 py-3"><option value="sidebar">سایدبار</option><option value="horizontal">افقی</option><option value="footer">فوتر</option></select><select v-model="adForm.media_id" class="rounded-lg border border-divider px-3 py-3"><option value="">بدون تصویر</option><option v-for="item in media.filter((item) => item.type === 'image')" :key="item.id" :value="item.id">{{ item.original_name }}</option></select><input v-model="adForm.link_url" type="url" class="rounded-lg border border-divider px-3 py-3 text-left" placeholder="لینک مقصد https://..."><label class="rounded-lg border border-divider px-3 py-3 text-sm"><input v-model="adForm.is_active" type="checkbox" class="ml-2"> تبلیغ فعال باشد</label><label class="text-sm text-muted">شروع نمایش<input v-model="adForm.starts_at" type="datetime-local" class="mt-1 block w-full rounded-lg border border-divider px-3 py-2 text-black"></label><label class="text-sm text-muted">پایان نمایش<input v-model="adForm.ends_at" type="datetime-local" class="mt-1 block w-full rounded-lg border border-divider px-3 py-2 text-black"></label><input v-model="adForm.sort_order" type="number" min="0" class="rounded-lg border border-divider px-3 py-3" placeholder="ترتیب نمایش"><div class="flex gap-2 md:col-span-2"><button class="rounded-lg bg-brand-red px-5 py-3 text-sm font-bold text-white">ذخیره تبلیغ</button><button type="button" class="rounded-lg border border-divider px-5 py-3 text-sm" @click="showAdForm = false">انصراف</button></div></form></div><div v-if="!advertisements.length" class="rounded-2xl bg-white p-12 text-center text-muted">هنوز تبلیغی ثبت نشده است.</div><div v-else class="grid gap-4 md:grid-cols-2"><div v-for="ad in advertisements" :key="ad.id" class="overflow-hidden rounded-2xl border border-divider bg-white shadow-sm"><img v-if="ad.image_url" :src="ad.image_url" :alt="ad.title" class="aspect-[3/1] w-full object-cover"><div class="p-4"><div class="flex items-start justify-between gap-3"><div><h2 class="font-black">{{ ad.title }}</h2><div class="mt-1 text-xs text-muted">{{ placementLabel[ad.placement] }} · اولویت {{ ad.sort_order }}</div></div><span class="rounded-full px-2 py-1 text-[11px]" :class="ad.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'">{{ ad.is_active ? 'فعال' : 'غیرفعال' }}</span></div><div class="mt-4 flex gap-2"><button class="rounded-lg border border-navy/20 px-3 py-2 text-xs font-bold text-navy hover:bg-navy hover:text-white" @click="editAd(ad)">ویرایش</button><button class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-bold text-red-700 hover:bg-red-700 hover:text-white" @click="removeAd(ad)">حذف</button></div></div></div></div></div>
    </template>
  </AdminLayout>
</template>

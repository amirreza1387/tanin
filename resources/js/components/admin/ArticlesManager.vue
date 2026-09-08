<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import api, { firstError, notify } from '../../lib/api';
import ArticleEditor from './ArticleEditor.vue';
import AsyncState from '../AsyncState.vue';
import Button from '../Button.vue';
import Card from '../Card.vue';
import ConfirmDialog from '../ConfirmDialog.vue';
import Icon from '../Icon.vue';
import Input from '../Input.vue';
import Select from '../Select.vue';
import StatusBadge from '../StatusBadge.vue';

const articles = ref([]); const categories = ref([]); const tags = ref([]); const media = ref([]);
const loading = ref(true); const saving = ref(false); const uploading = ref(false); const error = ref(''); const feedback = ref('');
const editorOpen = ref(false); const editing = ref(null); const dirty = ref(false); const confirmOpen = ref(false);
const query = ref(''); const status = ref('all'); const page = ref(1); const pageSize = 10;
const form = reactive({ id: '', title: '', lead: '', body: '', category_id: '', featured_media_id: '', tag_ids: [], meta_title: '', meta_description: '' });
const labels = { draft: 'پیش‌نویس', published: 'منتشرشده', scheduled: 'زمان‌بندی‌شده' };
const filtered = computed(() => articles.value.filter((item) => (!query.value.trim() || item.title.toLowerCase().includes(query.value.trim().toLowerCase())) && (status.value === 'all' || item.status === status.value)));
const pageCount = computed(() => Math.max(1, Math.ceil(filtered.value.length / pageSize)));
const visibleArticles = computed(() => filtered.value.slice((page.value - 1) * pageSize, page.value * pageSize));
const pages = computed(() => Array.from({ length: pageCount.value }, (_, index) => index + 1));
watch([query, status], () => { page.value = 1; });
watch(pageCount, (count) => { if (page.value > count) page.value = count; });
const announce = (text) => { feedback.value = text; notify(text); window.setTimeout(() => { feedback.value = ''; }, 3500); };
const load = async () => { loading.value = true; error.value = ''; try { const [articleResult, categoryResult, tagResult, mediaResult] = await Promise.all([api.get('/management/articles?per_page=100'), api.get('/categories'), api.get('/tags'), api.get('/media?per_page=100')]); articles.value = articleResult.data.data || []; categories.value = categoryResult.data.data || []; tags.value = tagResult.data.data || []; media.value = mediaResult.data.data || []; } catch (e) { error.value = firstError(e) || 'دریافت خبرها ممکن نشد.'; } finally { loading.value = false; } };
const openNew = () => { Object.assign(form, { id: '', title: '', lead: '', body: '', category_id: '', featured_media_id: '', tag_ids: [], meta_title: '', meta_description: '' }); editing.value = null; dirty.value = false; editorOpen.value = true; };
const openEdit = (article) => { Object.assign(form, { id: article.id, title: article.title, lead: article.lead || '', body: article.body || '', category_id: article.category?.id || '', featured_media_id: article.featured_media?.id || '', tag_ids: article.tags?.map((tag) => tag.id) || [], meta_title: article.seo?.meta_title || '', meta_description: article.seo?.meta_description || '' }); editing.value = article; dirty.value = false; editorOpen.value = true; };
const closeEditor = () => { if (dirty.value) confirmOpen.value = true; else editorOpen.value = false; };
const discardEditor = () => { confirmOpen.value = false; dirty.value = false; editorOpen.value = false; };
const save = async (payload) => { saving.value = true; error.value = ''; try { if (editing.value) await api.put(`/articles/${editing.value.id}`, payload); else await api.post('/articles', payload); localStorage.removeItem(`tanin:article-draft:${payload.id || 'new'}`); editorOpen.value = false; dirty.value = false; announce(editing.value ? 'تغییرات خبر ذخیره شد.' : 'خبر جدید ذخیره شد.'); await load(); } catch (e) { error.value = firstError(e); notify(error.value, 'error'); } finally { saving.value = false; } };
const action = async (article, name) => { const snapshot = [...articles.value]; try { if (name === 'delete') articles.value = articles.value.filter((item) => item.id !== article.id); else article.status = name === 'publish' ? 'published' : 'draft'; if (name === 'delete') await api.delete(`/articles/${article.id}`); else await api.post(`/articles/${article.id}/${name}`); announce(name === 'publish' ? 'خبر منتشر شد.' : name === 'revert' ? 'خبر به پیش‌نویس بازگشت.' : 'خبر حذف شد.'); } catch (e) { articles.value = snapshot; error.value = firstError(e); notify(error.value, 'error'); } };
const setFeatured = async (article) => { try { const result = await api.patch(`/articles/${article.id}/featured`, { is_featured: !article.is_featured }); articles.value = articles.value.map((item) => ({ ...item, is_featured: item.id === article.id ? result.data.data.is_featured : false })); announce(result.data.data.is_featured ? 'خبر منتخب به‌روزرسانی شد.' : 'خبر از حالت منتخب خارج شد.'); } catch (e) { error.value = firstError(e); notify(error.value, 'error'); } };
const upload = async (event) => { const file = event.target.files?.[0]; if (!file) return; if (!file.type.startsWith('image/')) { error.value = 'برای تصویر شاخص فقط فایل تصویری انتخاب کنید.'; return; } uploading.value = true; try { const data = new FormData(); data.append('file', file); const result = await api.post('/media', data, { headers: { 'Content-Type': 'multipart/form-data' } }); media.value.unshift(result.data.data); form.featured_media_id = result.data.data.id; announce('تصویر شاخص آپلود و انتخاب شد.'); } catch (e) { error.value = firstError(e); } finally { uploading.value = false; event.target.value = ''; } };
onMounted(load);
</script>

<template>
  <section class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3"><div><h1 class="text-2xl font-black text-navy">مدیریت اخبار</h1><p class="mt-1 text-sm text-muted">جست‌وجو، ویرایش و انتشار خبرها در یک جریان واحد.</p></div><Button @click="openNew"><Icon name="plus" :size="16" />خبر جدید</Button></div>
    <Card v-if="articles.some((article) => article.is_featured)" class="flex flex-wrap items-center justify-between gap-3 border-r-4 border-brand-red"><div><div class="text-xs font-bold text-brand-red">خبر منتخب صفحهٔ اصلی</div><div class="mt-1 font-black">{{ articles.find((article) => article.is_featured)?.title }}</div></div><Button variant="secondary" size="sm" @click="setFeatured(articles.find((article) => article.is_featured))">حذف از منتخب</Button></Card>
    <div v-if="feedback" class="ds-alert ds-alert-success" role="status">{{ feedback }}</div><div v-if="error" class="ds-alert ds-alert-error" role="alert">{{ error }}</div>
    <ArticleEditor v-if="editorOpen" :article="form" :editing="Boolean(editing)" :categories="categories" :tags="tags" :media="media" :saving="saving" :uploading="uploading" @save="save" @upload="upload" @dirty-change="dirty = $event" @cancel="closeEditor" />
    <ConfirmDialog :open="confirmOpen" title="تغییرات ذخیره نشده" message="تغییرات ذخیره‌نشده کنار گذاشته شوند؟" @cancel="confirmOpen = false" @confirm="discardEditor" />
    <AsyncState :loading="loading" skeleton="table" :error="error" :empty="!loading && !error && !articles.length" empty-text="هنوز خبری برای مدیریت وجود ندارد." @retry="load">
      <Card class="grid gap-3 sm:grid-cols-[1fr_190px_auto]"><Input id="article-search" v-model="query" placeholder="جست‌وجوی عنوان خبر" aria-label="جست‌وجوی خبر" /><Select id="article-status" v-model="status" aria-label="فیلتر وضعیت" :options="[{ value: 'all', label: 'همه وضعیت‌ها' }, { value: 'draft', label: 'پیش‌نویس' }, { value: 'published', label: 'منتشرشده' }, { value: 'scheduled', label: 'زمان‌بندی‌شده' }]" /><span class="self-center text-xs text-muted">{{ filtered.length }} نتیجه</span></Card>
      <Card :padded="false" class="mt-4 overflow-x-auto"><table class="ds-table ds-table-mobile-cards min-w-[760px] md:min-w-0"><thead><tr><th>عنوان</th><th>وضعیت</th><th>بازدید</th><th>عملیات</th></tr></thead><tbody><tr v-for="article in visibleArticles" :key="article.id"><td data-label="عنوان" class="ds-title-safe max-w-sm font-bold">{{ article.title }} <span v-if="article.is_featured" class="mr-2 text-xs text-brand-red">منتخب</span></td><td data-label="وضعیت"><StatusBadge :status="article.status" :label="labels[article.status] || article.status" /></td><td data-label="بازدید" class="text-muted">{{ article.views }}</td><td data-label="عملیات"><div class="flex flex-wrap gap-2"><Button v-if="article.status === 'published'" :variant="article.is_featured ? 'primary' : 'secondary'" size="sm" @click="setFeatured(article)">{{ article.is_featured ? 'منتخب است' : 'انتخاب برای هیرو' }}</Button><Button variant="secondary" size="sm" @click="openEdit(article)">ویرایش</Button><Button v-if="article.status !== 'published'" size="sm" @click="action(article, 'publish')">انتشار</Button><Button v-else variant="secondary" size="sm" @click="action(article, 'revert')">پیش‌نویس</Button><Button variant="danger" size="sm" @click="action(article, 'delete')">حذف</Button></div></td></tr></tbody></table></Card>
      <nav v-if="pageCount > 1" class="ds-pagination mt-4" aria-label="صفحه‌بندی خبرها"><Button variant="secondary" size="sm" :disabled="page === 1" @click="page--">قبلی</Button><Button v-for="item in pages" :key="item" variant="secondary" size="sm" :aria-current="page === item ? 'page' : undefined" @click="page = item">{{ item }}</Button><Button variant="secondary" size="sm" :disabled="page === pageCount" @click="page++">بعدی</Button></nav>
    </AsyncState>
  </section>
</template>

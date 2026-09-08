<script setup>
import { useAuthStore } from '../stores/auth';
import Icon from './Icon.vue';
const auth = useAuthStore();
defineProps({ section: String });
const items = [
  ['dashboard', 'نمای کلی', 'grid'],
  ['articles', 'مدیریت اخبار', 'file'],
  ['comments', 'نظرات', 'chat'],
  ['users', 'کاربران', 'users'],
  ['categories', 'دسته‌بندی‌ها', 'grid'],
  ['tags', 'برچسب‌ها', 'tag'],
  ['media', 'رسانه‌ها', 'image'],
  ['advertisements', 'تبلیغات', 'ad'],
];
</script>
<template>
  <div class="mx-auto flex max-w-7xl gap-6 px-4 py-6">
    <aside class="hidden w-56 shrink-0 rounded-2xl bg-navy p-4 text-white md:block">
      <div class="mb-6 border-b border-white/15 pb-5"><div class="text-lg font-black">پنل مدیریت</div><div class="mt-1 text-xs text-white/60">{{ auth.user?.name }}</div></div>
      <nav class="space-y-1" aria-label="ناوبری مدیریت"><RouterLink v-for="[key, label, icon] in items" :key="key" :to="`/admin/${key}`" :aria-current="section === key ? 'page' : undefined" class="flex items-center gap-3 rounded-lg px-3 py-3 text-sm transition hover:bg-white/10" :class="section === key ? 'bg-brand-red font-bold' : 'text-white/80'"><Icon :name="icon" :size="18" aria-hidden="true" />{{ label }}</RouterLink></nav>
      <RouterLink to="/" class="mt-8 block border-t border-white/15 pt-4 text-center text-xs text-white/60">بازگشت به سایت</RouterLink>
    </aside>
    <div class="min-w-0 flex-1"><nav class="mb-4 flex gap-2 overflow-x-auto rounded-xl bg-white p-2 md:hidden" aria-label="ناوبری مدیریت موبایل"><RouterLink v-for="[key, label] in items" :key="key" :to="`/admin/${key}`" :aria-current="section === key ? 'page' : undefined" class="whitespace-nowrap rounded-lg px-3 py-2 text-xs" :class="section === key ? 'bg-brand-red text-white' : 'bg-cream'">{{ label }}</RouterLink></nav><slot /></div>
  </div>
</template>

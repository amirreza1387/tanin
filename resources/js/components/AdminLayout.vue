<script setup>
import { useAuthStore } from '../stores/auth';
const auth = useAuthStore();
defineProps({ section: String });
const items = [
  ['dashboard', 'نمای کلی', '▦'],
  ['articles', 'مدیریت اخبار', '▤'],
  ['comments', 'نظرات', '◌'],
  ['users', 'کاربران', '♙'],
  ['categories', 'دسته‌بندی‌ها', '▥'],
  ['tags', 'برچسب‌ها', '#'],
  ['media', 'رسانه‌ها', '▧'],
  ['advertisements', 'تبلیغات', '▣'],
];
</script>
<template>
  <div class="mx-auto flex max-w-7xl gap-6 px-4 py-6">
    <aside class="hidden w-56 shrink-0 rounded-2xl bg-navy p-4 text-white md:block">
      <div class="mb-6 border-b border-white/15 pb-5"><div class="text-lg font-black">پنل مدیریت</div><div class="mt-1 text-xs text-white/60">{{ auth.user?.name }}</div></div>
      <nav class="space-y-1"><RouterLink v-for="[key, label, icon] in items" :key="key" :to="`/admin/${key}`" class="flex items-center gap-3 rounded-lg px-3 py-3 text-sm transition hover:bg-white/10" :class="section === key ? 'bg-brand-red font-bold' : 'text-white/80'"><span class="w-5 text-center">{{ icon }}</span>{{ label }}</RouterLink></nav>
      <RouterLink to="/" class="mt-8 block border-t border-white/15 pt-4 text-center text-xs text-white/60">بازگشت به سایت</RouterLink>
    </aside>
    <div class="min-w-0 flex-1"><div class="mb-4 flex gap-2 overflow-x-auto rounded-xl bg-white p-2 md:hidden"><RouterLink v-for="[key, label] in items" :key="key" :to="`/admin/${key}`" class="whitespace-nowrap rounded-lg px-3 py-2 text-xs" :class="section === key ? 'bg-brand-red text-white' : 'bg-cream'">{{ label }}</RouterLink></div><slot /></div>
  </div>
</template>

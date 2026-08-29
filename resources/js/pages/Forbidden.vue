<template>
  <section class="mx-auto max-w-2xl px-4 py-20 text-center">
    <div class="rounded-2xl bg-white p-8 shadow-sm">
      <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-red-50 text-3xl">!</div>
      <p class="mt-6 text-sm font-bold text-brand-red">خطای ۴۰۳</p>
      <h1 class="mt-2 text-2xl font-black text-navy">دسترسی غیرمجاز</h1>
      <p class="mt-3 leading-8 text-muted">حساب کاربری شما اجازه ورود به پنل مدیریت را ندارد.</p>
      <RouterLink to="/" class="mt-6 inline-flex rounded-lg bg-brand-red px-5 py-3 text-sm font-bold text-white">
        بازگشت به صفحه اصلی
      </RouterLink>
    </div>
  </section>
</template>

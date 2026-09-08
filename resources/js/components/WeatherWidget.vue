<script setup>
import { computed, onMounted, ref } from 'vue';
import Icon from './Icon.vue';

const locations = [
  { province: 'خوزستان', city: 'اهواز', latitude: 31.3183, longitude: 48.6706 },
  { province: 'بوشهر', city: 'بوشهر', latitude: 28.9234, longitude: 50.8203 },
  { province: 'فارس', city: 'شیراز', latitude: 29.5918, longitude: 52.5837 },
  { province: 'هرمزگان', city: 'بندرعباس', latitude: 27.1832, longitude: 56.2666 },
  { province: 'کهگیلویه و بویراحمد', city: 'یاسوج', latitude: 30.6682, longitude: 51.5880 },
  { province: 'سیستان و بلوچستان', city: 'زاهدان', latitude: 29.4963, longitude: 60.8629 },
];

const weather = ref([]);
const loading = ref(true);
const failed = ref(false);
const cacheKey = 'tanin_weather_cache';

const weatherLabel = (code) => {
  if (code === 0) return 'صاف';
  if ([1, 2, 3].includes(code)) return 'نیمه‌ابری';
  if ([45, 48].includes(code)) return 'مه‌آلود';
  if ([51, 53, 55, 56, 57].includes(code)) return 'نم‌نم باران';
  if ([61, 63, 65, 66, 67, 80, 81, 82].includes(code)) return 'بارانی';
  if ([71, 73, 75, 77, 85, 86].includes(code)) return 'برفی';
  if ([95, 96, 99].includes(code)) return 'رعدوبرق';
  return 'نامشخص';
};

const weatherIcon = (code) => {
  if (code === 0) return 'weather-sun';
  if ([1, 2, 3, 45, 48].includes(code)) return 'weather-cloud';
  if ([51, 53, 55, 56, 57, 61, 63, 65, 66, 67, 80, 81, 82].includes(code)) return 'weather-rain';
  if ([71, 73, 75, 77, 85, 86].includes(code)) return 'weather-snow';
  if ([95, 96, 99].includes(code)) return 'weather-storm';
  return 'weather-cloud';
};

const toPersianDigits = (value) => String(value).replace(/\d/g, (digit) => '۰۱۲۳۴۵۶۷۸۹'[digit]);
const updatedAt = computed(() => weather.value[0]?.current?.time ? new Date(weather.value[0].current.time).toLocaleTimeString('fa-IR', { hour: '2-digit', minute: '2-digit' }) : '');

const loadWeather = async () => {
  loading.value = true;
  failed.value = false;
  const cached = sessionStorage.getItem(cacheKey);
  if (cached) {
    try {
      weather.value = JSON.parse(cached);
      loading.value = false;
    } catch {
      sessionStorage.removeItem(cacheKey);
    }
  }
  const latitude = locations.map((location) => location.latitude).join(',');
  const longitude = locations.map((location) => location.longitude).join(',');
  const params = new URLSearchParams({
    latitude,
    longitude,
    current: 'temperature_2m,apparent_temperature,weather_code,wind_speed_10m',
    timezone: 'Asia/Tehran',
    forecast_days: '1',
  });

  try {
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 10000);
    const response = await fetch(`https://api.open-meteo.com/v1/forecast?${params}`, { signal: controller.signal });
    clearTimeout(timeout);
    if (!response.ok) throw new Error('Weather request failed');
    const payload = await response.json();
    const results = Array.isArray(payload) ? payload : [payload];
    weather.value = results.map((item, index) => ({ ...locations[index], ...item }));
    sessionStorage.setItem(cacheKey, JSON.stringify(weather.value));
  } catch {
    failed.value = true;
  } finally {
    loading.value = false;
  }
};

onMounted(loadWeather);
</script>

<template>
  <section class="rounded-xl border border-divider bg-gradient-to-br from-navy to-[#496477] p-3 text-white sm:p-4">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="font-black">آب‌وهوای جنوب</h2>
        <p class="mt-1 text-[11px] text-white/60">وضعیت فعلی مراکز استان‌ها</p>
      </div>
      <Icon name="weather-sun" :size="26" />
    </div>

    <div v-if="loading" class="mt-3 grid grid-cols-1 gap-1.5 min-[360px]:grid-cols-2 sm:mt-4 sm:gap-2" aria-busy="true" aria-label="در حال بارگذاری آب‌وهوا"><div v-for="item in 6" :key="item" class="rounded-lg bg-white/10 p-2 sm:p-2.5"><i class="ds-skeleton block h-3 w-2/3 bg-white/20"></i><div class="mt-3 flex items-center justify-between"><i class="ds-skeleton h-6 w-6 rounded-full bg-white/20"></i><i class="ds-skeleton h-6 w-10 bg-white/20"></i></div><i class="ds-skeleton mt-3 block h-2 w-3/4 bg-white/20"></i></div></div>
    <div v-else-if="failed" class="py-6 text-center text-sm text-white/75">
      دریافت وضعیت هوا ممکن نشد.
      <button class="mt-2 block w-full text-xs font-bold text-white underline" @click="loadWeather">تلاش دوباره</button>
    </div>
    <div v-else class="mt-3 grid grid-cols-1 gap-1.5 min-[360px]:grid-cols-2 sm:mt-4 sm:gap-2">
      <div v-for="item in weather" :key="item.province" class="flex items-center justify-between gap-2 rounded-lg bg-white/10 p-2 backdrop-blur-sm sm:block sm:p-2.5">
        <div class="truncate text-xs font-bold">{{ item.province }}</div>
        <div class="flex items-center justify-between gap-1 sm:mt-2">
          <Icon :name="weatherIcon(item.current.weather_code)" :size="22" />
          <strong class="text-lg">{{ toPersianDigits(Math.round(item.current.temperature_2m)) }}°</strong>
        </div>
        <div class="hidden truncate text-[10px] text-white/65 sm:mt-1 sm:block">{{ item.city }} · {{ weatherLabel(item.current.weather_code) }}</div>
        <div class="hidden text-[10px] text-white/55 sm:mt-1 sm:block">حس‌شده {{ toPersianDigits(Math.round(item.current.apparent_temperature)) }}°</div>
      </div>
    </div>
    <div v-if="updatedAt" class="mt-3 border-t border-white/15 pt-2 text-[10px] text-white/50">آخرین به‌روزرسانی: {{ updatedAt }}</div>
  </section>
</template>

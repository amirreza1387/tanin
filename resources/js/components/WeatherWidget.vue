<script setup>
import { computed, onMounted, ref } from 'vue';

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
  if (code === 0) return '☀️';
  if ([1, 2, 3].includes(code)) return '⛅';
  if ([45, 48].includes(code)) return '🌫️';
  if ([51, 53, 55, 56, 57, 61, 63, 65, 66, 67, 80, 81, 82].includes(code)) return '🌧️';
  if ([71, 73, 75, 77, 85, 86].includes(code)) return '❄️';
  if ([95, 96, 99].includes(code)) return '⛈️';
  return '🌤️';
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
  <section class="rounded-xl border border-divider bg-gradient-to-br from-navy to-[#496477] p-4 text-white">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="font-black">آب‌وهوای جنوب</h2>
        <p class="mt-1 text-[11px] text-white/60">وضعیت فعلی مراکز استان‌ها</p>
      </div>
      <span class="text-2xl">☀️</span>
    </div>

    <div v-if="loading" class="py-8 text-center text-sm text-white/70">در حال دریافت وضعیت هوا...</div>
    <div v-else-if="failed" class="py-6 text-center text-sm text-white/75">
      دریافت وضعیت هوا ممکن نشد.
      <button class="mt-2 block w-full text-xs font-bold text-white underline" @click="loadWeather">تلاش دوباره</button>
    </div>
    <div v-else class="mt-4 grid grid-cols-2 gap-2">
      <div v-for="item in weather" :key="item.province" class="rounded-lg bg-white/10 p-2.5 backdrop-blur-sm">
        <div class="truncate text-xs font-bold">{{ item.province }}</div>
        <div class="mt-2 flex items-center justify-between gap-1">
          <span class="text-xl">{{ weatherIcon(item.current.weather_code) }}</span>
          <strong class="text-lg">{{ toPersianDigits(Math.round(item.current.temperature_2m)) }}°</strong>
        </div>
        <div class="mt-1 truncate text-[10px] text-white/65">{{ item.city }} · {{ weatherLabel(item.current.weather_code) }}</div>
        <div class="mt-1 text-[10px] text-white/55">حس‌شده {{ toPersianDigits(Math.round(item.current.apparent_temperature)) }}°</div>
      </div>
    </div>
    <div v-if="updatedAt" class="mt-3 border-t border-white/15 pt-2 text-[10px] text-white/50">آخرین به‌روزرسانی: {{ updatedAt }}</div>
  </section>
</template>

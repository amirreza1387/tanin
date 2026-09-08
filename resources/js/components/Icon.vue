<script setup>
defineProps({ name: { type: String, required: true }, size: { type: [String, Number], default: 20 } });
</script>
<template>
  <svg :width="size" :height="size" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <path v-if="name === 'menu'" d="M4 6h16M4 12h16M4 18h16" />
    <path v-else-if="name === 'search'" d="m21 21-4.4-4.4M10.8 18a7.2 7.2 0 1 1 0-14.4 7.2 7.2 0 0 1 0 14.4Z" />
    <path v-else-if="name === 'plus'" d="M12 5v14M5 12h14" />
    <path v-else-if="name === 'close'" d="m6 6 12 12M18 6 6 18" />
    <path v-else-if="name === 'upload'" d="M12 16V4m0 0-5 5m5-5 5 5M5 14v5h14v-5" />
    <path v-else-if="name === 'arrow-up'" d="M12 19V5m0 0-6 6m6-6 6 6" />
    <g v-else-if="name === 'weather-sun'"><circle cx="12" cy="12" r="3.5" /><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4" /></g>
    <g v-else-if="name === 'weather-cloud'"><path d="M6 18h11a4 4 0 0 0 .5-7.97A5.5 5.5 0 0 0 7.1 8.5 4.75 4.75 0 0 0 6 18Z" /></g>
    <g v-else-if="name === 'weather-rain'"><path d="M6 15h11a4 4 0 0 0 .5-7.97A5.5 5.5 0 0 0 7.1 5.5 4.75 4.75 0 0 0 6 15Z" /><path d="m8 18-1 3m5-3-1 3m5-3-1 3" /></g>
    <g v-else-if="name === 'weather-snow'"><path d="M6 15h11a4 4 0 0 0 .5-7.97A5.5 5.5 0 0 0 7.1 5.5 4.75 4.75 0 0 0 6 15Z" /><path d="m9 18 1 2m0-2-1 2m5-2 1 2m0-2-1 2" /></g>
    <g v-else-if="name === 'weather-storm'"><path d="M6 15h11a4 4 0 0 0 .5-7.97A5.5 5.5 0 0 0 7.1 5.5 4.75 4.75 0 0 0 6 15Z" /><path d="m13 16-3 4h3l-2 3 4-5h-3l2-2" /></g>
    <path v-else-if="name === 'grid'" d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z" />
    <path v-else-if="name === 'file'" d="M6 3h8l4 4v14H6zM14 3v5h5" />
    <path v-else-if="name === 'chat'" d="M4 5h16v11H8l-4 4z" />
    <path v-else-if="name === 'users'" d="M16 20v-1.5a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4V20m6-9a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm6-5a2.5 2.5 0 0 1 0 5m2 9v-1.5a3.5 3.5 0 0 0-2.5-3.35" />
    <path v-else-if="name === 'tag'" d="m20 13-7 7L4 11V4h7l9 9ZM8 8h.01" />
    <path v-else-if="name === 'image'" d="M4 5h16v14H4zM4 16l4-4 3 3 2-2 7 5M15 9h.01" />
    <path v-else-if="name === 'ad'" d="M4 5h16v14H4zM8 9h8M8 13h5" />
  </svg>
</template>

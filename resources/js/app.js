import './bootstrap';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import router from './router';
import { startPerformanceMonitoring } from './lib/performance';

createApp(App).use(createPinia()).use(router).mount('#app');
startPerformanceMonitoring();

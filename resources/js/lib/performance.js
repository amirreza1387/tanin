import api from './api';

export const startPerformanceMonitoring = () => {
    if (!('PerformanceObserver' in window) || navigator.doNotTrack === '1') return;
    const metrics = {};
    const observe = (type, callback) => { try { new PerformanceObserver((list) => callback(list.getEntries())).observe({ type, buffered: true }); } catch { /* unsupported */ } };
    observe('largest-contentful-paint', (entries) => { metrics.lcp = Math.round(entries.at(-1)?.startTime || 0); });
    observe('layout-shift', (entries) => { metrics.cls = (metrics.cls || 0) + entries.filter((entry) => !entry.hadRecentInput).reduce((sum, entry) => sum + entry.value, 0); });
    observe('event', (entries) => { metrics.inp = Math.max(metrics.inp || 0, ...entries.map((entry) => entry.duration || 0)); });
    window.addEventListener('pagehide', () => {
        const navigation = performance.getEntriesByType('navigation')[0]; metrics.ttfb = Math.round(navigation?.responseStart || 0);
        api.post('/telemetry', { path: location.pathname.slice(0, 160), metrics: { ...metrics, cls: Number((metrics.cls || 0).toFixed(4)) } }, { timeout: 2500 }).catch(() => undefined);
    }, { once: true });
};

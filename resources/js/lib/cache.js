const prefix = 'tanin:cache:';
const memory = new Map();

export const cachedGet = async (key, request, { ttl = 60_000, stale = true } = {}) => {
    const now = Date.now();
    let entry = memory.get(key);
    if (!entry) {
        try { entry = JSON.parse(sessionStorage.getItem(prefix + key)); } catch { entry = null; }
        if (entry) memory.set(key, entry);
    }
    if (entry && now - entry.at < ttl) return entry.data;
    if (entry && stale) { request().then((response) => setCached(key, response.data)).catch(() => undefined); return entry.data; }
    return setCached(key, (await request()).data);
};

export const setCached = (key, data) => {
    const entry = { at: Date.now(), data }; memory.set(key, entry);
    try { sessionStorage.setItem(prefix + key, JSON.stringify(entry)); } catch { /* optional */ }
    return data;
};

export const invalidateCache = (startsWith) => [...memory.keys()].filter((key) => key.startsWith(startsWith)).forEach((key) => {
    memory.delete(key); try { sessionStorage.removeItem(prefix + key); } catch { /* optional */ }
});

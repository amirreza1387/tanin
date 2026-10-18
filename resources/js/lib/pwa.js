let registrationPromise;

export const registerPwa = () => {
    if (!('serviceWorker' in navigator)) return Promise.resolve(null);
    registrationPromise ||= navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(() => null);
    return registrationPromise;
};

export const requestChatNotifications = async () => {
    if (!('Notification' in window)) return 'unsupported';
    if (Notification.permission === 'default') {
        try { return await Notification.requestPermission(); } catch { return 'denied'; }
    }
    return Notification.permission;
};

export const showChatNotification = ({ title, body, conversationId }) => {
    if (!('Notification' in window) || Notification.permission !== 'granted' || document.visibilityState === 'visible') return;
    const notification = new Notification(title || 'پیام جدید', {
        body: body || 'یک پیام جدید دریافت کردید.',
        icon: '/favicon.ico',
        tag: `private-chat-${conversationId || 'message'}`,
        data: { url: '/private-messages-7f3a' },
    });
    notification.onclick = () => { window.focus(); notification.close(); };
};

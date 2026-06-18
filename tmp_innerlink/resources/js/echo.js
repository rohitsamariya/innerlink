import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

const echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});

window.Echo = echo;

export function subscribeToUserChannel(userId) {
    const channel = echo.private(`users.${userId}`);

    channel.listen('.user.force.disconnect', (e) => {
        window.dispatchEvent(new CustomEvent('user:force-disconnect', { detail: e }));
    });

    channel.listen('.user.status.changed', (e) => {
        window.dispatchEvent(new CustomEvent('user:status-changed', { detail: e }));
    });

    channel.listen('.user.presence.changed', (e) => {
        window.dispatchEvent(new CustomEvent('user:presence-changed', { detail: e }));
    });

    return channel;
}

export function subscribeToGroupChannel(groupId) {
    const channel = echo.private(`groups.${groupId}`);

    channel.listen('.message.sent', (e) => {
        window.dispatchEvent(new CustomEvent('message:sent', { detail: e }));
    });

    channel.listen('.message.read', (e) => {
        window.dispatchEvent(new CustomEvent('message:read', { detail: e }));
    });

    channel.listen('.message.delivered', (e) => {
        window.dispatchEvent(new CustomEvent('message:delivered', { detail: e }));
    });

    channel.listen('.typing.started', (e) => {
        window.dispatchEvent(new CustomEvent('typing:started', { detail: e }));
    });

    channel.listen('.typing.stopped', (e) => {
        window.dispatchEvent(new CustomEvent('typing:stopped', { detail: e }));
    });

    return channel;
}

export function subscribeToPresenceGroupChannel(groupId) {
    const channel = echo.join(`presence-groups.${groupId}`);

    channel.here((users) => {
        window.dispatchEvent(new CustomEvent('presence:here', { detail: users }));
    });

    channel.joining((user) => {
        window.dispatchEvent(new CustomEvent('presence:joining', { detail: user }));
    });

    channel.leaving((user) => {
        window.dispatchEvent(new CustomEvent('presence:leaving', { detail: user }));
    });

    return channel;
}

export function subscribeToAdminDashboard() {
    const channel = echo.private('admin.dashboard');

    channel.listen('.export.completed', (e) => {
        window.dispatchEvent(new CustomEvent('export:completed', { detail: e }));
    });

    return channel;
}

export function subscribeToUserCallChannel(userId) {
    const channel = echo.private(`users.${userId}`);

    channel.listen('.call.offer', (e) => {
        window.dispatchEvent(new CustomEvent('call:offer', { detail: e }));
    });

    channel.listen('.call.accepted', (e) => {
        window.dispatchEvent(new CustomEvent('call:accepted', { detail: e }));
    });

    channel.listen('.call.rejected', (e) => {
        window.dispatchEvent(new CustomEvent('call:rejected', { detail: e }));
    });

    return channel;
}

export function subscribeToCallChannel(callId) {
    const channel = echo.private(`calls.${callId}`);

    channel.listen('.call.ice-candidate', (e) => {
        window.dispatchEvent(new CustomEvent('call:ice-candidate', { detail: e }));
    });

    channel.listen('.call.ended', (e) => {
        window.dispatchEvent(new CustomEvent('call:ended', { detail: e }));
    });

    return channel;
}

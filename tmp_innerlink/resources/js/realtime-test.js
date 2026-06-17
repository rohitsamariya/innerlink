import './echo';

const logEl = document.getElementById('event-log');
const maxLogEntries = 500;
let logEntries = [];

function timestamp() {
    const d = new Date();
    return d.toLocaleTimeString('en-US', { hour12: false });
}

function log(color, label, payload) {
    const t = timestamp();
    let entry = `<span class="text-gray-500">[${t}]</span> <span class="text-${color}-400">${label}</span>`;
    if (payload) {
        entry += ` <span class="text-gray-400">${JSON.stringify(payload)}</span>`;
    }
    logEntries.unshift(entry);
    if (logEntries.length > maxLogEntries) {
        logEntries = logEntries.slice(0, maxLogEntries);
    }
    logEl.innerHTML = logEntries.join('<br>');
}

function updateConnectionState(state) {
    const badge = document.getElementById('connection-badge');
    const text = document.getElementById('connection-state');
    text.textContent = state;
    const colors = {
        connected: 'bg-green-500',
        connecting: 'bg-yellow-500',
        disconnected: 'bg-red-500',
        reconnecting: 'bg-orange-500',
    };
    badge.className = `inline-block w-3 h-3 rounded-full ${colors[state] || 'bg-gray-500'}`;
    log(state === 'connected' ? 'green' : 'red', state.toUpperCase());
}

function updateDebugState() {
    const el = document.getElementById('reverb-debug-state');
    try {
        const state = window.Echo.connector.pusher.connection.state;
        const socketId = window.Echo.socketId() || 'none';
        el.innerHTML = `state: <span class="text-cyan-300">${state}</span> | socketId: <span class="text-cyan-300">${socketId}</span>`;
    } catch {
        el.textContent = 'Echo not initialized';
    }
}

let echo = null;
let subscribedUserChannel = null;
let subscribedGroupChannel = null;
let subscribedPresenceChannel = null;
let connectionPoll = null;

function init() {
    echo = window.Echo;

    if (!echo) {
        log('red', 'ERROR', 'Echo not initialized');
        return;
    }

    const pusher = echo.connector.pusher;
    const conn = pusher.connection;

    conn.bind('state_change', function (states) {
        updateConnectionState(states.current);
    });

    conn.bind('error', function (err) {
        log('red', 'CONNECTION_ERROR', err);
    });

    updateConnectionState(conn.state);

    connectionPoll = setInterval(() => {
        updateDebugState();
        const s = conn.state;
        const badge = document.getElementById('connection-badge');
        const text = document.getElementById('connection-state');
        if (text.textContent !== s) {
            updateConnectionState(s);
        }
    }, 1000);
}

window.subscribeUserChannel = function () {
    const userId = window.USER?.id;
    if (!userId) {
        log('red', 'ERROR', 'No user ID available — are you logged in?');
        return;
    }

    const btn = document.getElementById('subscribe-user-btn');
    const status = document.getElementById('user-channel-status');
    btn.disabled = true;
    status.textContent = 'subscribing...';

    try {
        if (subscribedUserChannel) {
            subscribedUserChannel.unsubscribe();
        }

        const channel = echo.private(`users.${userId}`);

        channel.listen('.user.force.disconnect', (e) => {
            log('red', 'user.force.disconnect', e);
        });

        channel.listen('.user.status.changed', (e) => {
            log('yellow', 'user.status.changed', e);
        });

        channel.listen('.user.presence.changed', (e) => {
            log('blue', 'user.presence.changed', e);
            const statusEl = document.getElementById('user-presence-status');
            if (statusEl && e.status) {
                statusEl.textContent = e.status;
            }
        });

        channel.error((err) => {
            log('red', 'USER_CHANNEL_ERROR', err);
            status.textContent = 'error';
            btn.disabled = false;
        });

        subscribedUserChannel = channel;
        status.textContent = 'subscribed';
        log('green', 'user.subscribed', { userId });
    } catch (err) {
        log('red', 'USER_CHANNEL_ERROR', err.message);
        status.textContent = 'error';
        btn.disabled = false;
    }
};

window.subscribeGroupChannel = function () {
    const groupId = document.getElementById('group-id-input').value;
    if (!groupId) {
        log('red', 'ERROR', 'Enter a Group ID');
        return;
    }

    const status = document.getElementById('group-channel-status');
    status.textContent = 'subscribing...';

    try {
        if (subscribedGroupChannel) {
            subscribedGroupChannel.unsubscribe();
        }

        const channel = echo.private(`groups.${groupId}`);

        channel.listen('.message.sent', (e) => {
            log('yellow', 'message.sent', e);
        });

        channel.listen('.message.read', (e) => {
            log('yellow', 'message.read', e);
        });

        channel.listen('.message.delivered', (e) => {
            log('yellow', 'message.delivered', e);
        });

        channel.listen('.typing.started', (e) => {
            log('yellow', 'typing.started', e);
        });

        channel.listen('.typing.stopped', (e) => {
            log('yellow', 'typing.stopped', e);
        });

        channel.error((err) => {
            log('red', 'GROUP_CHANNEL_ERROR', err);
            status.textContent = 'error';
        });

        subscribedGroupChannel = channel;
        status.textContent = `subscribed to groups.${groupId}`;
        log('green', 'group.subscribed', { groupId });
    } catch (err) {
        log('red', 'GROUP_CHANNEL_ERROR', err.message);
        status.textContent = 'error';
    }
};

window.joinPresenceChannel = function () {
    const groupId = document.getElementById('presence-group-id-input').value;
    if (!groupId) {
        log('red', 'ERROR', 'Enter a Group ID');
        return;
    }

    const status = document.getElementById('presence-channel-status');
    status.textContent = 'joining...';

    try {
        if (subscribedPresenceChannel) {
            subscribedPresenceChannel.unsubscribe();
        }

        const channel = echo.join(`presence-groups.${groupId}`);

        channel.here((users) => {
            document.getElementById('presence-here-count').textContent = users.length;
            document.getElementById('presence-users-list').textContent = users.map(u => u.id || u.user_id || '?').join(', ') || '(none)';
            log('purple', 'presence.here', { count: users.length, users });
        });

        channel.joining((user) => {
            log('purple', 'presence.joining', user);
        });

        channel.leaving((user) => {
            log('purple', 'presence.leaving', user);
        });

        channel.error((err) => {
            log('red', 'PRESENCE_CHANNEL_ERROR', err);
            status.textContent = 'error';
        });

        subscribedPresenceChannel = channel;
        status.textContent = `joined presence-groups.${groupId}`;
        log('green', 'presence.joined', { groupId });
    } catch (err) {
        log('red', 'PRESENCE_CHANNEL_ERROR', err.message);
        status.textContent = 'error';
    }
};

window.clearLog = function () {
    logEntries = [];
    logEl.innerHTML = '<div class="text-gray-600">[log cleared]</div>';
};

window.disconnectEcho = function () {
    if (echo) {
        echo.disconnect();
        log('red', 'DISCONNECTED', 'Echo manually disconnected');
    }
};

window.reconnectEcho = function () {
    if (echo) {
        echo.connect();
        log('green', 'RECONNECTING', 'Echo reconnecting');
    }
};

document.addEventListener('DOMContentLoaded', () => {
    setTimeout(init, 500);

    if (window.USER?.id) {
        setTimeout(window.subscribeUserChannel, 1000);
    }
});

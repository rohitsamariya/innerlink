<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Realtime Debug Dashboard</title>
    @vite(['resources/css/app.css', 'resources/js/realtime-test.js'])
</head>
<body class="bg-gray-950 text-gray-100 font-mono text-sm p-4">
    <div id="app" class="max-w-6xl mx-auto space-y-4">

        {{-- Header --}}
        <div class="flex items-center justify-between border-b border-gray-700 pb-2">
            <h1 class="text-lg font-bold tracking-wide">REALTIME DEBUG DASHBOARD</h1>
            <div class="flex gap-2 text-xs">
                <button onclick="clearLog()" class="px-3 py-1 rounded bg-gray-700 hover:bg-gray-600">Clear Log</button>
                <button onclick="disconnectEcho()" class="px-3 py-1 rounded bg-red-900 hover:bg-red-800">Disconnect</button>
                <button onclick="reconnectEcho()" class="px-3 py-1 rounded bg-green-900 hover:bg-green-800">Reconnect</button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

            {{-- Left Column --}}
            <div class="space-y-4">

                {{-- 1. Connection Status --}}
                <section class="border border-gray-700 rounded p-3">
                    <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Connection Status</h2>
                    <div class="flex items-center gap-2">
                        <span id="connection-badge" class="inline-block w-3 h-3 rounded-full bg-gray-500"></span>
                        <span id="connection-state" class="text-sm">unknown</span>
                    </div>
                </section>

                {{-- 2. Current User --}}
                <section class="border border-gray-700 rounded p-3">
                    <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Current User</h2>
                    <div id="current-user" class="text-sm space-y-0.5">
                        @auth
                            <div>ID: <span class="text-cyan-300">{{ auth()->id() }}</span></div>
                            <div>Name: <span class="text-cyan-300">{{ auth()->user()->full_name ?? auth()->user()->name ?? 'N/A' }}</span></div>
                            <div>Presence: <span id="user-presence-status" class="text-cyan-300">{{ auth()->user()->presence_status ?? 'OFFLINE' }}</span></div>
                        @else
                            <div class="text-yellow-400">Not authenticated — some features require login</div>
                        @endauth
                    </div>
                    @auth
                        <script>window.USER = @json(auth()->user());</script>
                    @endauth
                </section>

                {{-- 3. User Channel Subscription --}}
                <section class="border border-gray-700 rounded p-3">
                    <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">User Channel</h2>
                    <div class="flex items-center gap-2">
                        <button id="subscribe-user-btn" onclick="subscribeUserChannel()" class="px-3 py-1 rounded bg-blue-800 hover:bg-blue-700 text-xs">Subscribe User Channel</button>
                        <span id="user-channel-status" class="text-xs text-gray-500">idle</span>
                    </div>
                </section>

                {{-- 4. Group Channel Subscription --}}
                <section class="border border-gray-700 rounded p-3">
                    <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Group Channel</h2>
                    <div class="flex items-center gap-2">
                        <input id="group-id-input" type="number" placeholder="Group ID" class="bg-gray-800 border border-gray-700 rounded px-2 py-1 w-24 text-xs">
                        <button onclick="subscribeGroupChannel()" class="px-3 py-1 rounded bg-blue-800 hover:bg-blue-700 text-xs">Subscribe</button>
                        <span id="group-channel-status" class="text-xs text-gray-500">idle</span>
                    </div>
                </section>

                {{-- 5. Presence Channel Subscription --}}
                <section class="border border-gray-700 rounded p-3">
                    <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Presence Channel</h2>
                    <div class="flex items-center gap-2 mb-2">
                        <input id="presence-group-id-input" type="number" placeholder="Group ID" class="bg-gray-800 border border-gray-700 rounded px-2 py-1 w-24 text-xs">
                        <button onclick="joinPresenceChannel()" class="px-3 py-1 rounded bg-purple-800 hover:bg-purple-700 text-xs">Join Presence</button>
                        <span id="presence-channel-status" class="text-xs text-gray-500">idle</span>
                    </div>
                    <div class="text-xs">
                        <div>here(): <span id="presence-here-count" class="text-gray-400">0</span></div>
                        <div>Active Users: <span id="presence-users-list" class="text-gray-400">(none)</span></div>
                    </div>
                </section>

                {{-- 10. Reverb State --}}
                <section class="border border-gray-700 rounded p-3">
                    <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Reverb Connection State</h2>
                    <div id="reverb-debug-state" class="text-xs text-gray-400">waiting...</div>
                </section>

            </div>

            {{-- Right Column --}}
            <div class="space-y-4">

                {{-- 7-10. Event Log --}}
                <section class="border border-gray-700 rounded p-3 flex flex-col h-[70vh]">
                    <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Event Log</h2>
                    <div class="flex flex-wrap gap-2 mb-2 text-xs text-gray-500">
                        <span class="text-green-400">●</span> connected
                        <span class="text-red-400">●</span> disconnected
                        <span class="text-blue-400">●</span> presence
                        <span class="text-yellow-400">●</span> message/read/typing
                        <span class="text-purple-400">●</span> presence here/joining/leaving
                    </div>
                    <div id="event-log" class="flex-1 overflow-y-auto bg-gray-900 border border-gray-800 rounded p-2 text-xs leading-relaxed space-y-0.5 font-mono">
                        <div class="text-gray-600">[log ready]</div>
                    </div>
                </section>

            </div>
        </div>
    </div>
</body>
</html>

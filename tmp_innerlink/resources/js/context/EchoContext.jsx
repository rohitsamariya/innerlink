import { createContext, useContext, useEffect, useRef, useState } from 'react';

const EchoContext = createContext(null);

export function EchoProvider({ children }) {
    const echoRef = useRef(null);
    const [echo, setEcho] = useState(null);

    useEffect(() => {
        const key = import.meta.env.VITE_REVERB_APP_KEY;
        if (!key) {
            return;
        }

        async function init() {
            try {
                const { default: Echo } = await import('laravel-echo');
                const { default: Pusher } = await import('pusher-js');
                window.Pusher = Pusher;

                const token = localStorage.getItem('auth_token');

                const instance = new Echo({
                    broadcaster: 'reverb',
                    key,
                    wsHost: import.meta.env.VITE_REVERB_HOST,
                    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
                    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
                    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
                    enabledTransports: ['ws', 'wss'],
                    withCredentials: true,
                    auth: token ? {
                        headers: {
                            Authorization: `Bearer ${token}`,
                        },
                    } : undefined,
                });

                echoRef.current = instance;
                setEcho(instance);
            } catch {
                console.warn('Echo initialization skipped');
            }
        }
        init();

        return () => {
            if (echoRef.current) {
                echoRef.current.disconnect();
            }
        };
    }, []);

    return (
        <EchoContext.Provider value={echo}>
            {children}
        </EchoContext.Provider>
    );
}

export function useEcho() {
    const ctx = useContext(EchoContext);
    return ctx;
}

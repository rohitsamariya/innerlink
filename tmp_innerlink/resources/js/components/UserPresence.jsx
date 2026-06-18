import { useEffect } from 'react';
import { useAuth } from '../context/AuthContext';
import client from '../api/client';

export default function UserPresence() {
    const { user } = useAuth();

    useEffect(() => {
        if (!user?.id) return;

        const heartbeat = setInterval(() => {
            client.post('/auth/heartbeat').catch(() => {});
        }, 2000);

        return () => clearInterval(heartbeat);
    }, [user?.id]);

    return null;
}

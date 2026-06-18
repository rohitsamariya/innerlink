import { useEffect, useRef } from 'react';
import { useAuth } from '../context/AuthContext';
import { useEcho } from '../context/EchoContext';

export default function UserPresence() {
    const { user } = useAuth();
    const echo = useEcho();
    const lastUserId = useRef(null);

    useEffect(() => {
        if (!echo || !user?.id) return;

        if (lastUserId.current && lastUserId.current !== user.id) {
            echo.leave(`users.${lastUserId.current}`);
        }

        lastUserId.current = user.id;
        echo.private(`users.${user.id}`);

        return () => {
            echo.leave(`users.${user.id}`);
        };
    }, [echo, user?.id]);

    return null;
}

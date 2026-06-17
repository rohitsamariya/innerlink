import { useEffect, useState, useCallback } from 'react';
import { useAuth } from '../context/AuthContext';
import client from '../api/client';
import { isUserOnline } from '../utils/formatDate';
import Skeleton from '../components/Skeleton';

function unwrap(response) {
    return response.data?.data ?? response.data;
}

export default function Dashboard() {
    const { user } = useAuth();
    const [stats, setStats] = useState(null);
    const [unread, setUnread] = useState(0);
    const [loading, setLoading] = useState(true);

    const fetchUnread = useCallback(() => {
        Promise.all([
            client.get('/private-messages/conversations'),
            client.get('/groups'),
        ]).then(([convRes, grpRes]) => {
            const convs = unwrap(convRes);
            const grps = unwrap(grpRes);
            const convUnread = (Array.isArray(convs) ? convs : []).reduce((s, c) => s + (c.unread_count || 0), 0);
            const grpUnread = (Array.isArray(grps) ? grps : []).reduce((s, g) => s + (g.unread_count || 0), 0);
            setUnread(convUnread + grpUnread);
        }).catch(() => {});
    }, []);

    useEffect(() => {
        client.get('/stats')
            .then((res) => setStats(unwrap(res)))
            .catch(() => {})
            .finally(() => setLoading(false));
        fetchUnread();
    }, [fetchUnread]);

    const onlineCount = (() => {
        const n = stats?.online_users ?? 0;
        if (n > 0) return n;
        const names = stats?.online_user_names ?? [];
        if (isUserOnline(user) && !names.includes(user?.full_name)) return 1;
        return 0;
    })();

    const cards = [
        { label: 'Total Users', value: stats?.total_users, bg: 'bg-primary/[0.07]', text: 'text-primary', icon: (
            <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5">
                <path strokeLinecap="round" strokeLinejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
            </svg>
        )},
        { label: 'Online Now', value: onlineCount, bg: 'bg-success/10', text: 'text-success', icon: (
            <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5">
                <path strokeLinecap="round" strokeLinejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
            </svg>
        )},
        { label: 'Active Groups', value: stats?.active_groups, bg: 'bg-accent/10', text: 'text-accent', icon: (
            <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5">
                <path strokeLinecap="round" strokeLinejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
            </svg>
        )},
        { label: 'Unread Messages', value: unread, bg: 'bg-danger/10', text: 'text-danger', icon: (
            <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5">
                <path strokeLinecap="round" strokeLinejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
            </svg>
        )},
    ];

    return (
        <div className="flex-1 p-4 sm:p-8 overflow-y-auto">
            <h2 className="text-xl sm:text-2xl font-bold text-primary mb-2">Welcome, {user?.full_name}</h2>
            <p className="text-secondary mb-8">Here's what's happening in your workspace.</p>

            {loading ? (
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                    <Skeleton className="h-28" count={4} />
                </div>
            ) : (
                <>
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                        {cards.map((card) => (
                            <div key={card.label} className="bg-surface border border-border rounded-lg p-4 sm:p-6 flex items-center gap-4">
                                <div className={`w-10 h-10 sm:w-12 sm:h-12 ${card.bg} ${card.text} flex items-center justify-center flex-shrink-0 rounded-lg`}>
                                    {card.icon}
                                </div>
                                <div>
                                    <p className="text-xl sm:text-2xl font-bold text-primary">{card.value ?? '-'}</p>
                                    <p className="text-sm text-secondary">
                                        {card.label === 'Online Now' ? (
                                            <>
                                                {card.label}
                                                <span className="text-xs text-muted block leading-tight mt-0.5">Admin + Manager + Employee</span>
                                            </>
                                        ) : card.label}
                                    </p>
                                </div>
                            </div>
                        ))}
                    </div>

                    {(stats?.online_user_names?.length > 0 || isUserOnline(user)) && (
                        <div className="mt-8 bg-surface border border-border rounded-lg p-6">
                            <h3 className="text-sm font-semibold text-primary mb-3 flex items-center gap-2">
                                <span className="h-2.5 w-2.5 rounded-full bg-success inline-block animate-pulse" />
                                Online Now
                            </h3>
                            <div className="flex flex-wrap gap-2">
                                {stats?.online_user_names?.map((name) => (
                                    <span key={name} className="inline-flex items-center gap-1.5 px-3 py-1 bg-success/10 text-success text-sm font-medium rounded-md">
                                        <span className="h-2 w-2 rounded-full bg-success inline-block" />
                                        {name}
                                    </span>
                                ))}
                                {isUserOnline(user) && !stats?.online_user_names?.includes(user?.full_name) && (
                                    <span className="inline-flex items-center gap-1.5 px-3 py-1 bg-success/10 text-success text-sm font-medium rounded-md">
                                        <span className="h-2 w-2 rounded-full bg-success inline-block" />
                                        {user?.full_name}
                                    </span>
                                )}
                            </div>
                        </div>
                    )}
                </>
            )}
        </div>
    );
}

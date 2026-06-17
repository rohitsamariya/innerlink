import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import client from '../api/client';
import { formatIST, formatRelativeTime, isUserOnline } from '../utils/formatDate';
import Skeleton from '../components/Skeleton';

function unwrap(response) {
    return response.data?.data ?? response.data;
}

export default function Chats() {
    const navigate = useNavigate();
    const [conversations, setConversations] = useState([]);
    const [loading, setLoading] = useState(true);

    const loadConversations = () => {
        client.get('/private-messages/conversations')
            .then((res) => {
                const data = unwrap(res);
                setConversations(Array.isArray(data) ? data : []);
            })
            .catch(() => {})
            .finally(() => setLoading(false));
    };

    useEffect(() => {
        loadConversations();
        const onFocus = () => loadConversations();
        window.addEventListener('focus', onFocus);
        return () => window.removeEventListener('focus', onFocus);
    }, []);

    return (
        <div className="flex-1 p-4 sm:p-8 overflow-y-auto">
            <h2 className="text-xl sm:text-2xl font-bold text-primary mb-6">Chats</h2>

            {loading ? (
                <div className="space-y-3">
                    <Skeleton className="h-16 w-full" count={5} />
                </div>
            ) : conversations.length === 0 ? (
                <div className="text-center py-8 text-muted bg-surface border border-border rounded-lg p-6">
                    No conversations yet. Use the Compose button to start one.
                </div>
            ) : (
                <div className="bg-surface border border-border rounded-lg overflow-hidden">
                    <ul className="divide-y divide-border">
                        {conversations.map((conv) => (
                            <li key={conv.user?.id}>
                                <button
                                    onClick={() => navigate(`/private-chat/${conv.user?.id}`)}
                                    className="w-full flex items-center gap-4 px-4 sm:px-6 py-4 hover:bg-primary/[0.02] transition-colors text-left"
                                >
                                    <div className="relative w-10 h-10 flex-shrink-0">
                                        <div className="w-full h-full bg-accent/10 flex items-center justify-center text-accent text-sm font-medium rounded-lg">
                                            {conv.user?.full_name?.charAt(0)?.toUpperCase() || '?'}
                                        </div>
                                        {isUserOnline(conv.user) && (
                                            <span className="absolute -bottom-0.5 -right-0.5 h-3 w-3 bg-success border-2 border-surface rounded-full" />
                                        )}
                                    </div>
                                    <div className="flex-1 min-w-0">
                                        <div className="flex items-center justify-between gap-2">
                                            <div className="flex items-center gap-1.5 min-w-0">
                                                <p className="text-sm font-medium text-primary truncate">
                                                    {conv.user?.full_name || 'Unknown'}
                                                </p>
                                                {isUserOnline(conv.user) ? (
                                                    <span className="text-xs text-success font-medium shrink-0">Online</span>
                                                ) : conv.user?.last_seen_at ? (
                                                    <span className="text-xs text-muted shrink-0" title={formatIST(conv.user.last_seen_at)}>
                                                        {formatRelativeTime(conv.user.last_seen_at)}
                                                    </span>
                                                ) : null}
                                            </div>
                                            {conv.last_message_at && (
                                                <p className="text-xs text-muted flex-shrink-0">
                                                    {formatIST(conv.last_message_at)}
                                                </p>
                                            )}
                                        </div>
                                        <p className="text-sm text-secondary truncate mt-0.5">
                                            {conv.last_message || 'No messages'}
                                        </p>
                                    </div>
                                    {conv.unread_count > 0 && (
                                        <span className="flex-shrink-0 inline-flex items-center justify-center h-5 min-w-[1.25rem] px-1.5 bg-accent text-white text-xs font-medium rounded-full">
                                            {conv.unread_count}
                                        </span>
                                    )}
                                </button>
                            </li>
                        ))}
                    </ul>
                </div>
            )}
        </div>
    );
}

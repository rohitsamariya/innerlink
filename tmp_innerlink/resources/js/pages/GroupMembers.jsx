import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { fetchGroupMembers } from '../api/admin';
import { fetchGroup } from '../api/groups';
import { isUserOnline, formatRelativeTime } from '../utils/formatDate';

export default function GroupMembers() {
    const { groupId } = useParams();
    const navigate = useNavigate();
    const [group, setGroup] = useState(null);
    const [members, setMembers] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        Promise.all([
            fetchGroup(groupId).then((d) => setGroup(d?.data ?? d)),
            fetchGroupMembers(groupId).then((data) => {
                setMembers(Array.isArray(data) ? data : data.data || []);
            }),
        ])
            .catch(() => navigate('/groups'))
            .finally(() => setLoading(false));
    }, [groupId, navigate]);

    const onlineCount = members.filter((u) => isUserOnline(u)).length;

    return (
        <div className="flex-1 flex flex-col">
            <div className="border-b border-border bg-header px-4 sm:px-6 py-4 flex items-center gap-3">
                <button onClick={() => navigate(`/chat/${groupId}`)} className="text-secondary hover:text-primary">
                    <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                </button>
                <div className="flex-1 min-w-0">
                    <h1 className="text-xl font-semibold text-primary truncate">
                        {group?.name || 'Group'} — All Members
                    </h1>
                    <p className="text-sm text-muted">
                        {loading ? 'Loading...' : `${members.length} members (${onlineCount} online)`}
                    </p>
                </div>
            </div>

            <div className="flex-1 overflow-y-auto">
                {loading ? (
                    <div className="flex items-center justify-center py-16">
                        <div className="animate-spin h-6 w-6 border-2 border-primary border-t-transparent rounded-full" />
                    </div>
                ) : members.length === 0 ? (
                    <div className="text-center py-16 text-muted text-sm">
                        No members in this group.
                    </div>
                ) : (
                    <div className="max-w-2xl mx-auto divide-y divide-border">
                        {members.map((u) => (
                            <div key={u.id} className="flex items-center gap-3 px-4 sm:px-6 py-3.5 hover:bg-primary/[0.02] transition-colors">
                                <div className="relative w-9 h-9 flex-shrink-0">
                                    <div className="w-full h-full rounded-full bg-accent/10 flex items-center justify-center text-accent text-sm font-medium">
                                        {u.full_name?.charAt(0)?.toUpperCase() || '?'}
                                    </div>
                                    {isUserOnline(u) && (
                                        <span className="absolute -bottom-0.5 -right-0.5 h-3 w-3 bg-success border-2 border-surface rounded-full" />
                                    )}
                                </div>
                                <div className="flex-1 min-w-0">
                                    <p className="text-sm font-medium text-primary truncate">{u.full_name}</p>
                                    <p className="text-xs text-muted truncate">{u.email}</p>
                                </div>
                                <div className="flex-shrink-0 text-xs">
                                    {isUserOnline(u) ? (
                                        <span className="text-success font-medium">Online</span>
                                    ) : u.last_seen_at ? (
                                        <span className="text-muted" title={u.last_seen_at}>
                                            {formatRelativeTime(u.last_seen_at)}
                                        </span>
                                    ) : (
                                        <span className="text-muted">Offline</span>
                                    )}
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </div>
    );
}

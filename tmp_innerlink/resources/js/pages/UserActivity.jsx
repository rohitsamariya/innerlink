import { useEffect, useState } from 'react';
import { useAuth } from '../context/AuthContext';
import { useNavigate } from 'react-router-dom';
import client from '../api/client';
import { formatIST, formatRelativeTime, isUserOnline } from '../utils/formatDate';
import Skeleton from '../components/Skeleton';

function unwrap(response) {
    return response.data?.data ?? response.data;
}

export default function UserActivity() {
    const { user } = useAuth();
    const navigate = useNavigate();
    const [users, setUsers] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        client.get('/users')
            .then((res) => {
                const data = unwrap(res);
                setUsers(Array.isArray(data) ? data : []);
            })
            .catch(() => {})
            .finally(() => setLoading(false));
    }, []);

    const online = users.filter((u) => isUserOnline(u));

    return (
        <div className="flex-1 p-4 sm:p-8 overflow-y-auto">
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
                <div>
                    <h2 className="text-xl sm:text-2xl font-bold text-primary">Login Activity</h2>
                    <p className="text-sm text-secondary mt-1">Monitor user presence and last active times.</p>
                </div>
                <div className="self-start sm:self-auto inline-flex items-center gap-2 px-3 py-1.5 bg-accent/10 text-accent text-sm font-medium rounded-md">
                    <span className="h-2 w-2 bg-accent rounded-full" />
                    {online.length} online
                </div>
            </div>

            {loading ? (
                <div className="space-y-3">
                    <Skeleton className="h-12 w-full" count={8} />
                </div>
            ) : users.length === 0 ? (
                <div className="bg-surface border border-border rounded-lg p-8 text-center text-muted">
                    No users found.
                </div>
            ) : (
                <>
                    {/* Desktop + tablet table */}
                    <div className="hidden sm:block bg-surface border border-border rounded-lg overflow-hidden">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="bg-primary/[0.02] border-b border-border">
                                        <th className="text-left px-4 sm:px-5 py-3 font-medium text-secondary whitespace-nowrap">User</th>
                                        <th className="text-left px-4 sm:px-5 py-3 font-medium text-secondary whitespace-nowrap">Role</th>
                                        <th className="text-left px-4 sm:px-5 py-3 font-medium text-secondary whitespace-nowrap">Last Active</th>
                                        <th className="text-right px-4 sm:px-5 py-3 font-medium text-secondary whitespace-nowrap">Status</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border">
                                    {users.map((u) => (
                                        <tr key={u.id} onClick={() => navigate(`/activity/${u.id}`, { state: { user: u } })} className="hover:bg-primary/[0.02] cursor-pointer">
                                            <td className="px-4 sm:px-5 py-3 sm:py-3.5">
                                                <div className="flex items-center gap-3">
                                                    <div className="relative w-8 h-8 shrink-0">
                                                        <div className="w-full h-full bg-accent/10 flex items-center justify-center text-accent text-xs font-semibold rounded-lg">
                                                            {(u.full_name || '?').charAt(0).toUpperCase()}
                                                        </div>
                                                        {isUserOnline(u) && (
                                                            <span className="absolute -bottom-0.5 -right-0.5 h-2.5 w-2.5 bg-success border-2 border-surface rounded-full" />
                                                        )}
                                                    </div>
                                                    <div className="min-w-0">
                                                        <p className="text-sm font-medium text-primary truncate max-w-[140px] lg:max-w-none">{u.full_name}</p>
                                                        <p className="text-xs text-muted truncate max-w-[140px] lg:max-w-none">{u.email}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-4 sm:px-5 py-3 sm:py-3.5 whitespace-nowrap">
                                                <span className={`inline-flex px-2 py-0.5 text-xs font-medium rounded-md ${
                                                    u.role === 'ADMIN'
                                                        ? 'bg-accent/10 text-accent'
                                                        : u.role === 'MANAGER'
                                                        ? 'bg-accent/10 text-accent'
                                                        : 'bg-primary/5 text-secondary'
                                                }`}>
                                                    {u.role}
                                                </span>
                                            </td>
                                            <td className="px-4 sm:px-5 py-3 sm:py-3.5 whitespace-nowrap">
                                                {u.last_seen_at ? (
                                                    <span className="text-sm text-secondary" title={formatIST(u.last_seen_at)}>
                                                        {formatRelativeTime(u.last_seen_at)}
                                                    </span>
                                                ) : (
                                                    <span className="text-sm text-muted">Never</span>
                                                )}
                                            </td>
                                            <td className="px-4 sm:px-5 py-3 sm:py-3.5 text-right whitespace-nowrap">
                                                {isUserOnline(u) ? (
                                                    <span className="inline-flex items-center gap-1.5 text-success font-medium">
                                                        <span className="h-2 w-2 bg-success rounded-full" />
                                                        Online
                                                    </span>
                                                ) : (
                                                    <span className="inline-flex items-center gap-1.5 text-muted">
                                                        <span className="h-2 w-2 bg-border rounded-full" />
                                                        Offline
                                                    </span>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {/* Mobile cards */}
                    <div className="sm:hidden space-y-3">
                        {users.map((u) => (
                            <div key={u.id} onClick={() => navigate(`/activity/${u.id}`, { state: { user: u } })} className="bg-surface border border-border rounded-lg p-4 cursor-pointer active:scale-[0.99] transition-transform">
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center gap-3 min-w-0">
                                        <div className="relative w-9 h-9 shrink-0">
                                            <div className="w-full h-full bg-accent/10 flex items-center justify-center text-accent text-sm font-semibold rounded-lg">
                                                {(u.full_name || '?').charAt(0).toUpperCase()}
                                            </div>
                                            {isUserOnline(u) && (
                                                <span className="absolute -bottom-0.5 -right-0.5 h-2.5 w-2.5 bg-success border-2 border-surface rounded-full" />
                                            )}
                                        </div>
                                        <div className="min-w-0">
                                            <p className="text-sm font-medium text-primary truncate">{u.full_name}</p>
                                            <p className="text-xs text-muted truncate">{u.email}</p>
                                        </div>
                                    </div>
                                    <span className={`inline-flex px-2 py-0.5 text-xs font-medium rounded-md shrink-0 ${
                                        u.role === 'ADMIN'
                                            ? 'bg-accent/10 text-accent'
                                            : u.role === 'MANAGER'
                                            ? 'bg-accent/10 text-accent'
                                            : 'bg-primary/5 text-secondary'
                                    }`}>
                                        {u.role}
                                    </span>
                                </div>
                                <div className="flex items-center justify-between text-xs mt-3 pt-3 border-t border-border">
                                    <div className="flex items-center gap-2">
                                        <span className={`inline-flex items-center gap-1.5 font-medium ${
                                            isUserOnline(u) ? 'text-success' : 'text-muted'
                                        }`}>
                                            <span className={`h-2 w-2 rounded-full ${isUserOnline(u) ? 'bg-success' : 'bg-border'}`} />
                                            {isUserOnline(u) ? 'Online' : 'Offline'}
                                        </span>
                                    </div>
                                    <span className="text-muted" title={u.last_seen_at ? formatIST(u.last_seen_at) : ''}>
                                        {u.last_seen_at ? formatRelativeTime(u.last_seen_at) : 'Never'}
                                    </span>
                                </div>
                            </div>
                        ))}
                    </div>
                </>
            )}
        </div>
    );
}

import { useEffect, useState } from 'react';
import { useParams, useNavigate, useLocation } from 'react-router-dom';
import client from '../api/client';
import { formatIST, formatRelativeTime, isUserOnline } from '../utils/formatDate';

function unwrap(response) {
    return response.data?.data ?? response.data;
}

export default function UserActivityDetail() {
    const { userId } = useParams();
    const navigate = useNavigate();
    const location = useLocation();
    const passedUser = location.state?.user;

    const [userData, setUserData] = useState(passedUser || null);
    const [loginHistory, setLoginHistory] = useState([]);
    const [loading, setLoading] = useState(!passedUser);

    useEffect(() => {
        const load = async () => {
            try {
                const [userRes, historyRes] = await Promise.all([
                    passedUser ? Promise.resolve({ data: { data: passedUser } }) : client.get('/users'),
                    client.get(`/users/${userId}/login-activity`),
                ]);
                if (!passedUser) {
                    const users = unwrap(userRes);
                    setUserData((Array.isArray(users) ? users : []).find((u) => String(u.id) === String(userId)) || null);
                }
                const history = historyRes.data?.data ?? [];
                setLoginHistory(Array.isArray(history) ? history : []);
            } catch {
                if (!userData) setUserData(null);
            } finally {
                setLoading(false);
            }
        };
        load();
    }, [userId, passedUser]);

    if (loading) {
        return (
            <div className="flex-1 flex items-center justify-center">
                <div className="animate-spin h-8 w-8 border-2 border-primary border-t-transparent rounded-full" />
            </div>
        );
    }

    if (!userData) {
        return (
            <div className="flex-1 p-4 sm:p-8">
                <button onClick={() => navigate('/activity')} className="inline-flex items-center gap-1.5 text-sm text-secondary hover:text-primary mb-4">
                    <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5"><path strokeLinecap="round" strokeLinejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
                    Back to Login Activity
                </button>
                <div className="bg-surface border border-border rounded-lg p-8 text-center text-muted">User not found.</div>
            </div>
        );
    }

    const online = isUserOnline(userData);

    return (
        <div className="flex-1 p-4 sm:p-8 overflow-y-auto">
            <button onClick={() => navigate('/activity')} className="inline-flex items-center gap-1.5 text-sm text-secondary hover:text-primary mb-4 sm:mb-6 transition-colors">
                <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5"><path strokeLinecap="round" strokeLinejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
                Back to Login Activity
            </button>

            {/* User profile card */}
            <div className="bg-surface border border-border rounded-lg p-5 sm:p-6 mb-6">
                <div className="flex flex-col sm:flex-row sm:items-center gap-4 sm:gap-6">
                    <div className="relative w-14 h-14 sm:w-16 sm:h-16 shrink-0 self-start">
                        <div className="w-full h-full bg-accent/10 flex items-center justify-center text-accent text-xl sm:text-2xl font-bold rounded-xl">
                            {(userData.full_name || '?').charAt(0).toUpperCase()}
                        </div>
                        {online && (
                            <span className="absolute -bottom-0.5 -right-0.5 h-3.5 w-3.5 bg-success border-2 border-surface rounded-full" />
                        )}
                    </div>
                    <div className="flex-1 min-w-0">
                        <div className="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4">
                            <h2 className="text-xl sm:text-2xl font-bold text-primary truncate">{userData.full_name}</h2>
                            <span className={`self-start inline-flex px-2.5 py-0.5 text-xs font-medium rounded-md ${
                                userData.role === 'ADMIN'
                                    ? 'bg-accent/10 text-accent'
                                    : userData.role === 'MANAGER'
                                    ? 'bg-accent/10 text-accent'
                                    : 'bg-primary/5 text-secondary'
                            }`}>
                                {userData.role}
                            </span>
                        </div>
                        <p className="text-sm text-secondary mt-0.5">{userData.email}</p>
                    </div>
                    <div className="flex items-center gap-3 shrink-0">
                        {online ? (
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
                    </div>
                </div>
            </div>

            {/* Activity Stats */}
            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6 mb-6">
                <div className="bg-surface border border-border rounded-lg p-4 sm:p-5">
                    <p className="text-xs font-medium text-secondary uppercase tracking-wider mb-1">Current Status</p>
                    <div className="flex items-center gap-2 mt-1">
                        <span className={`h-2.5 w-2.5 rounded-full ${online ? 'bg-success' : 'bg-border'}`} />
                        <p className={`text-base sm:text-lg font-semibold ${online ? 'text-success' : 'text-muted'}`}>
                            {online ? 'Online' : 'Offline'}
                        </p>
                    </div>
                    <p className="text-xs text-muted mt-1.5">
                        {online ? 'User is currently active in the system' : 'User is currently not active'}
                    </p>
                </div>

                <div className="bg-surface border border-border rounded-lg p-4 sm:p-5">
                    <p className="text-xs font-medium text-secondary uppercase tracking-wider mb-1">Last Active</p>
                    {userData.last_seen_at ? (
                        <>
                            <p className="text-base sm:text-lg font-semibold text-primary">{formatRelativeTime(userData.last_seen_at)}</p>
                            <p className="text-xs text-muted mt-1.5 font-mono">{formatIST(userData.last_seen_at)}</p>
                        </>
                    ) : (
                        <p className="text-base sm:text-lg font-semibold text-muted">Never</p>
                    )}
                </div>

                <div className="bg-surface border border-border rounded-lg p-4 sm:p-5">
                    <p className="text-xs font-medium text-secondary uppercase tracking-wider mb-1">Total Sessions</p>
                    <p className="text-base sm:text-lg font-semibold text-primary">{loginHistory.length}</p>
                    <p className="text-xs text-muted mt-1.5">
                        {loginHistory.length === 0 ? 'No sessions recorded' :
                         loginHistory.length === 1 ? '1 login session' :
                         `${loginHistory.length} login sessions recorded`}
                    </p>
                </div>
            </div>

            {/* Login Activity — Desktop table */}
            <div className="hidden sm:block bg-surface border border-border rounded-lg overflow-hidden">
                <div className="px-5 sm:px-6 py-4 border-b border-border">
                    <h3 className="text-base font-semibold text-primary">Login Activity</h3>
                    <p className="text-xs text-muted mt-0.5">
                        {loginHistory.length} {loginHistory.length === 1 ? 'session' : 'sessions'} recorded
                    </p>
                </div>
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="bg-primary/[0.02] border-b border-border">
                                <th className="text-left px-4 sm:px-6 py-3 font-medium text-secondary whitespace-nowrap">Login Time</th>
                                <th className="text-left px-4 sm:px-6 py-3 font-medium text-secondary whitespace-nowrap">Logout Time</th>
                                <th className="text-right px-4 sm:px-6 py-3 font-medium text-secondary whitespace-nowrap">Status</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border">
                            {loginHistory.map((entry, i) => {
                                const session = entry;
                                return (
                                    <tr key={i} className="hover:bg-primary/[0.02]">
                                        <td className="px-4 sm:px-6 py-3.5 sm:py-4 whitespace-nowrap">
                                            <div className="flex items-center gap-2">
                                                <div className="h-2 w-2 rounded-full bg-accent shrink-0" />
                                                <div>
                                                    <p className="text-sm text-primary font-mono">{formatIST(session.logged_in_at)}</p>
                                                    <p className="text-xs text-muted mt-0.5">{formatRelativeTime(session.logged_in_at)}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-4 sm:px-6 py-3.5 sm:py-4 whitespace-nowrap">
                                            {session.logged_out_at ? (
                                                <>
                                                    <p className="text-sm text-primary font-mono">{formatIST(session.logged_out_at)}</p>
                                                    <p className="text-xs text-muted mt-0.5">{formatRelativeTime(session.logged_out_at)}</p>
                                                </>
                                            ) : (
                                                <span className="text-sm text-muted">—</span>
                                            )}
                                        </td>
                                        <td className="px-4 sm:px-6 py-3.5 sm:py-4 text-right whitespace-nowrap">
                                            {session.logged_out_at ? (
                                                <span className="inline-flex items-center gap-1 text-xs font-medium text-muted">
                                                    <span className="h-1.5 w-1.5 bg-border rounded-full" />
                                                    Logged out
                                                </span>
                                            ) : session.last_used_at && new Date(session.last_used_at).getTime() > Date.now() - 300000 ? (
                                                <span className="inline-flex items-center gap-1 text-xs font-medium text-success">
                                                    <span className="h-1.5 w-1.5 bg-success rounded-full" />
                                                    Active
                                                </span>
                                            ) : (
                                                <span className="inline-flex items-center gap-1 text-xs font-medium text-muted">
                                                    <span className="h-1.5 w-1.5 bg-border rounded-full" />
                                                    Inactive
                                                </span>
                                            )}
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </div>
                {loginHistory.length === 0 && (
                    <div className="px-5 sm:px-6 py-8 text-center text-muted text-sm">No login activity recorded yet.</div>
                )}
            </div>

            {/* Login Activity — Mobile cards */}
            <div className="sm:hidden space-y-3">
                <div className="bg-surface border border-border rounded-lg px-4 py-3">
                    <h3 className="text-sm font-semibold text-primary">Login Activity</h3>
                    <p className="text-xs text-muted mt-0.5">
                        {loginHistory.length} {loginHistory.length === 1 ? 'session' : 'sessions'} recorded
                    </p>
                </div>
                {loginHistory.length === 0 ? (
                    <div className="bg-surface border border-border rounded-lg px-4 py-6 text-center text-muted text-sm">No login activity recorded yet.</div>
                ) : (
                    loginHistory.map((entry, i) => {
                        const session = entry;
                        return (
                            <div key={i} className="bg-surface border border-border rounded-lg p-4">
                                <div className="flex items-center justify-between mb-2">
                                    <span className="text-xs font-medium text-muted uppercase tracking-wider">Session #{loginHistory.length - i}</span>
                                    {session.logged_out_at ? (
                                        <span className="inline-flex items-center gap-1 text-xs font-medium text-muted">
                                            <span className="h-1.5 w-1.5 bg-border rounded-full" />
                                            Logged out
                                        </span>
                                    ) : session.last_used_at && new Date(session.last_used_at).getTime() > Date.now() - 300000 ? (
                                        <span className="inline-flex items-center gap-1 text-xs font-medium text-success">
                                            <span className="h-1.5 w-1.5 bg-success rounded-full" />
                                            Active
                                        </span>
                                    ) : (
                                        <span className="inline-flex items-center gap-1 text-xs font-medium text-muted">
                                            <span className="h-1.5 w-1.5 bg-border rounded-full" />
                                            Inactive
                                        </span>
                                    )}
                                </div>
                                <div className="grid grid-cols-2 gap-3 text-sm">
                                    <div>
                                        <p className="text-xs text-muted mb-0.5">Login Time</p>
                                        <p className="text-primary font-mono text-xs">{formatIST(session.logged_in_at)}</p>
                                        <p className="text-muted text-xs mt-0.5">{formatRelativeTime(session.logged_in_at)}</p>
                                    </div>
                                    <div>
                                        <p className="text-xs text-muted mb-0.5">Logout Time</p>
                                        {session.logged_out_at ? (
                                            <>
                                                <p className="text-primary font-mono text-xs">{formatIST(session.logged_out_at)}</p>
                                                <p className="text-muted text-xs mt-0.5">{formatRelativeTime(session.logged_out_at)}</p>
                                            </>
                                        ) : (
                                            <p className="text-muted text-xs">—</p>
                                        )}
                                    </div>
                                </div>
                            </div>
                        );
                    })
                )}
            </div>
        </div>
    );
}

import { useState, useEffect } from 'react';
import { fetchCallHistory } from '../../api/calls';

const statusColors = {
    accepted: 'text-green-600 dark:text-green-400',
    ringing: 'text-yellow-600 dark:text-yellow-400',
    rejected: 'text-red-600 dark:text-red-400',
    missed: 'text-red-600 dark:text-red-400',
    ended: 'text-gray-600 dark:text-gray-400',
    failed: 'text-red-600 dark:text-red-400',
};

const statusLabels = {
    accepted: 'Connected',
    ringing: 'Ringing',
    rejected: 'Rejected',
    missed: 'Missed',
    ended: 'Ended',
    failed: 'Failed',
};

export default function CallHistory({ userId }) {
    const [calls, setCalls] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetchCallHistory()
            .then(setCalls)
            .catch(() => {})
            .finally(() => setLoading(false));
    }, []);

    if (loading) {
        return (
            <div className="flex items-center justify-center py-12">
                <div className="animate-spin h-6 w-6 border-2 border-primary border-t-transparent rounded-full" />
            </div>
        );
    }

    if (calls.length === 0) {
        return (
            <div className="text-center py-12 text-muted">
                <svg className="h-12 w-12 mx-auto mb-3 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
                </svg>
                <p className="text-sm">No call history yet.</p>
            </div>
        );
    }

    return (
        <div className="space-y-1">
            {calls.map((call) => {
                const isCaller = call.caller_id === userId;
                const otherName = isCaller ? call.receiver_name : call.caller_name;
                const directionIcon = isCaller ? (
                    <svg className="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M15 11.25l-3-3m0 0l-3 3m3-3v7.5" />
                    </svg>
                ) : (
                    <svg className="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M19.5 13.5l-6-6m0 0l-6 6m6-6v15" />
                    </svg>
                );

                const formatDuration = (secs) => {
                    if (!secs) return null;
                    const m = Math.floor(secs / 60);
                    const s = secs % 60;
                    return `${m}:${String(s).padStart(2, '0')}`;
                };

                const formatDate = (dateStr) => {
                    if (!dateStr) return '';
                    const d = new Date(dateStr);
                    return d.toLocaleDateString(undefined, {
                        month: 'short', day: 'numeric',
                        hour: '2-digit', minute: '2-digit',
                    });
                };

                return (
                    <div key={call.id} className="flex items-center gap-3 px-4 py-3 hover:bg-primary/[0.02] rounded-lg transition-colors">
                        <div className="flex-shrink-0 w-8 h-8 rounded-full bg-primary/5 flex items-center justify-center">
                            {directionIcon}
                        </div>
                        <div className="flex-1 min-w-0">
                            <p className="text-sm font-medium text-primary truncate">{otherName || 'Unknown'}</p>
                            <div className="flex items-center gap-2 text-xs text-muted">
                                <span className={statusColors[call.status] || 'text-muted'}>
                                    {statusLabels[call.status] || call.status}
                                </span>
                                {formatDuration(call.duration_seconds) && (
                                    <>
                                        <span>&middot;</span>
                                        <span>{formatDuration(call.duration_seconds)}</span>
                                    </>
                                )}
                            </div>
                        </div>
                        <div className="text-xs text-muted flex-shrink-0">
                            {formatDate(call.created_at)}
                        </div>
                    </div>
                );
            })}
        </div>
    );
}

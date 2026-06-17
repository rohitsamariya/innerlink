import { useEffect, useState } from 'react';
import client from '../api/client';
import { formatISTTime, formatRelativeTime, isUserOnline } from '../utils/formatDate';

export default function SeenByPopup({ groupId, messageId, onClose }) {
    const [readers, setReaders] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        client.get(`/groups/${groupId}/messages/${messageId}/readers`)
            .then((res) => setReaders(res.data?.readers ?? []))
            .catch(() => {})
            .finally(() => setLoading(false));
    }, [groupId, messageId]);

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4" onClick={onClose}>
            <div className="bg-surface border border-border rounded-xl w-full max-w-sm max-h-[80vh] flex flex-col" onClick={(e) => e.stopPropagation()}>
                <div className="flex items-center justify-between px-4 py-3.5 border-b border-border shrink-0">
                    <div>
                        <h3 className="text-sm font-semibold text-primary">Seen by</h3>
                        {!loading && readers.length > 0 && (
                            <p className="text-xs text-muted mt-0.5">{readers.length} {readers.length === 1 ? 'person' : 'people'}</p>
                        )}
                    </div>
                    <button onClick={onClose} className="w-7 h-7 flex items-center justify-center text-muted hover:text-primary transition-colors">
                        <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div className="flex-1 overflow-y-auto py-1">
                    {loading ? (
                        <div className="flex items-center justify-center py-8">
                            <div className="animate-spin h-5 w-5 border-2 border-primary border-t-transparent rounded-full" />
                        </div>
                    ) : readers.length === 0 ? (
                        <div className="flex flex-col items-center justify-center py-8 text-muted">
                            <svg className="h-8 w-8 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                <path strokeLinecap="round" strokeLinejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <p className="text-sm">No one has seen this yet</p>
                        </div>
                    ) : (
                        <ul className="divide-y divide-border">
                            {readers.map((r) => (
                                <li key={r.user_id} className="flex items-center gap-3 px-4 py-3 hover:bg-primary/[0.02] transition-colors">
                                    <div className="relative w-8 h-8 shrink-0">
                                        <div className="w-full h-full bg-accent/10 flex items-center justify-center text-accent text-xs font-semibold rounded-lg">
                                            {(r.full_name || '?').charAt(0).toUpperCase()}
                                        </div>
                                        {isUserOnline(r) && (
                                            <span className="absolute -bottom-0.5 -right-0.5 h-2.5 w-2.5 bg-success border-2 border-surface rounded-full" />
                                        )}
                                    </div>
                                    <div className="flex-1 min-w-0">
                                        <p className="text-sm font-medium text-primary truncate">{r.full_name}</p>
                                    </div>
                                    <span className="text-xs text-muted shrink-0 whitespace-nowrap" title={r.read_at ? formatISTTime(r.read_at) : ''}>
                                        {r.read_at ? formatRelativeTime(r.read_at) : '-'}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            </div>
        </div>
    );
}

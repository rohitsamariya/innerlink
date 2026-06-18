import { useState } from 'react';
import { useAuth } from '../../context/AuthContext';
import { useCall } from '../../context/CallContext';

export default function CallButton({ targetUser, variant = 'icon' }) {
    const { user } = useAuth();
    const { initiate, activeCall } = useCall();
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);

    const canCall = user?.role === 'ADMIN' || targetUser?.role === 'ADMIN';

    const handleClick = async () => {
        if (loading || activeCall) {
            if (activeCall) setError('You already have an active call.');
            return;
        }
        setLoading(true);
        setError(null);

        try {
            await initiate(targetUser.id);
        } catch (err) {
            setError(err.response?.data?.message || 'Failed to start call.');
        } finally {
            setLoading(false);
        }
    };

    if (!canCall) return null;

    if (variant === 'text') {
        return (
            <div>
                <button
                    onClick={handleClick}
                    disabled={loading || !!activeCall}
                    className="flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-white bg-green-600 hover:bg-green-700 disabled:opacity-50 rounded-md transition-colors"
                >
                    {loading ? (
                        <svg className="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182" />
                        </svg>
                    ) : (
                        <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
                        </svg>
                    )}
                    {loading ? 'Connecting...' : 'Call'}
                </button>
                {error && <p className="text-xs text-red-500 mt-1">{error}</p>}
            </div>
        );
    }

    return (
        <div className="relative">
            <button
                onClick={handleClick}
                disabled={loading || !!activeCall}
                className="p-2 text-secondary hover:text-green-500 hover:bg-green-500/10 disabled:opacity-50 rounded-full transition-colors"
                title="Call"
            >
                <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
                </svg>
            </button>
            {error && (
                <div className="absolute top-full right-0 mt-1 bg-red-50 text-red-600 text-xs px-2 py-1 rounded shadow-lg border border-red-200 whitespace-nowrap z-50">
                    {error}
                    <button onClick={() => setError(null)} className="ml-1 text-red-400 hover:text-red-600">&times;</button>
                </div>
            )}
        </div>
    );
}

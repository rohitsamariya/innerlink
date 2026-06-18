import { useEffect, useState } from 'react';

export default function IncomingCallModal({ callData, onAccept, onReject }) {
    const [timeoutId, setTimeoutId] = useState(null);

    useEffect(() => {
        const id = setTimeout(() => {
            onReject?.();
        }, 30000);

        setTimeoutId(id);

        return () => clearTimeout(id);
    }, [callData?.id]);

    const handleAccept = () => {
        clearTimeout(timeoutId);
        onAccept?.();
    };

    const handleReject = () => {
        clearTimeout(timeoutId);
        onReject?.();
    };

    if (!callData) return null;

    return (
        <div className="fixed inset-0 z-[100] flex items-center justify-center bg-black/50">
            <div className="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-8 w-full max-w-sm mx-4 text-center">
                <div className="w-16 h-16 mx-auto mb-4 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                    <svg className="h-8 w-8 text-green-600 dark:text-green-400 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
                    </svg>
                </div>
                <h2 className="text-lg font-semibold text-gray-900 dark:text-white mb-1">Incoming Call</h2>
                <p className="text-gray-600 dark:text-gray-400 mb-6">
                    <span className="font-medium">{callData.caller_name}</span> is calling you...
                </p>
                <div className="flex gap-4 justify-center">
                    <button
                        onClick={handleReject}
                        className="px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white font-medium text-sm rounded-full transition-colors flex items-center gap-2"
                    >
                        <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Decline
                    </button>
                    <button
                        onClick={handleAccept}
                        className="px-6 py-2.5 bg-green-600 hover:bg-green-700 text-white font-medium text-sm rounded-full transition-colors flex items-center gap-2"
                    >
                        <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
                        </svg>
                        Accept
                    </button>
                </div>
            </div>
        </div>
    );
}

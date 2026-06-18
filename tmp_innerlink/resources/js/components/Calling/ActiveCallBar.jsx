import { useState, useEffect, useRef } from 'react';

export default function ActiveCallBar({ callData, onEndCall, remoteStream, localStream, isMicMuted, onToggleMute }) {
    const [duration, setDuration] = useState(0);
    const remoteAudioRef = useRef(null);
    const timerRef = useRef(null);

    useEffect(() => {
        if (callData?.status === 'accepted') {
            timerRef.current = setInterval(() => {
                setDuration(d => d + 1);
            }, 1000);
        }

        return () => {
            if (timerRef.current) clearInterval(timerRef.current);
        };
    }, [callData?.status]);

    useEffect(() => {
        if (remoteAudioRef.current && remoteStream) {
            remoteAudioRef.current.srcObject = remoteStream;
        }
    }, [remoteStream]);

    const formatDuration = (secs) => {
        const m = Math.floor(secs / 60);
        const s = secs % 60;
        return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
    };

    const otherName = callData?.caller_id === callData?.receiver_id
        ? callData?.caller_name
        : (callData?.caller_name || 'Unknown');

    return (
        <div className="fixed bottom-0 left-0 right-0 z-50 bg-green-700 text-white px-4 py-3 shadow-lg">
            <audio ref={remoteAudioRef} autoPlay />
            <div className="max-w-screen-xl mx-auto flex items-center justify-between">
                <div className="flex items-center gap-3">
                    <div className="w-8 h-8 rounded-full bg-green-500 flex items-center justify-center">
                        <svg className="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
                        </svg>
                    </div>
                    <div>
                        <p className="text-sm font-medium">{otherName}</p>
                        <p className="text-xs text-green-200">
                            {callData?.status === 'ringing' ? 'Ringing...' : formatDuration(duration)}
                        </p>
                    </div>
                </div>
                <div className="flex items-center gap-3">
                    {callData?.status === 'accepted' && (
                        <button
                            onClick={onToggleMute}
                            className={`p-2 rounded-full transition-colors ${isMicMuted ? 'bg-red-500 hover:bg-red-600' : 'bg-green-600 hover:bg-green-500'}`}
                            title={isMicMuted ? 'Unmute' : 'Mute'}
                        >
                            {isMicMuted ? (
                                <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M12 1.5l-2.27 2.27M12 1.5l2.27 2.27M12 1.5v5.25M12 12V8.25m0 0l-3.75-3.75M12 8.25l3.75-3.75M12 12v4.5m0 0a4.5 4.5 0 004.5-4.5M12 16.5a4.5 4.5 0 01-4.5-4.5" strokeLinecap="round" strokeLinejoin="round" />
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M19.5 12h-1.5M6 12H4.5m3 6.75l-1.5 1.5M12 21.75v-1.5m4.5-3l1.5 1.5" />
                                </svg>
                            ) : (
                                <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M12 18.75a6 6 0 006-6v-1.5m-6 7.5a6 6 0 01-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 01-3-3V4.5a3 3 0 116 0v8.25a3 3 0 01-3 3z" />
                                </svg>
                            )}
                        </button>
                    )}
                    <button
                        onClick={onEndCall}
                        className="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-full transition-colors flex items-center gap-2"
                    >
                        <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M16.714 10.987c-2.659-1.884-6.145-2.824-9.558-2.507a.75.75 0 00-.622.533l-.734 2.572m12.172 2.329c1.35.398 2.53.977 3.426 1.704" />
                            <path strokeLinecap="round" strokeLinejoin="round" d="M3.75 13.5l1.5-5.25m0 0L4.5 7.09c.044-.162.18-.28.363-.308 2.687-.405 5.408-.405 8.094 0 .183.028.319.146.363.308l.75 2.652m0 0l.75 2.652c.044.162.18.28.363.308 1.289.194 2.538.543 3.726 1.017" />
                        </svg>
                        Hang Up
                    </button>
                </div>
            </div>
        </div>
    );
}

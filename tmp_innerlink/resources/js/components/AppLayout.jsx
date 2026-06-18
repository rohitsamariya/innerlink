import { useState, useEffect } from 'react';
import { useAuth } from '../context/AuthContext';
import { useEcho } from '../context/EchoContext';
import { useCall } from '../context/CallContext';
import Sidebar from './Sidebar';
import IncomingCallModal from './Calling/IncomingCallModal';
import ActiveCallBar from './Calling/ActiveCallBar';

export default function AppLayout({ children }) {
    const { user } = useAuth();
    const echo = useEcho();
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const { incomingCall, activeCall, remoteStream, localStream, isMicMuted, acceptIncoming, rejectIncoming, hangUp, toggleMute } = useCall();

    useEffect(() => {
        if (!echo || !user?.id) return;
        const channel = echo.private(`users.${user.id}`);
        return () => { echo.leave(`users.${user.id}`); };
    }, [echo, user?.id]);

    return (
        <div className="flex h-screen bg-page">
            <div className="flex-1 flex flex-col min-w-0">
                <div className="lg:hidden flex items-center h-12 px-4 bg-header border-b border-border">
                    <button onClick={() => setSidebarOpen(true)} className="text-secondary hover:text-primary">
                        <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                    </button>
                    <div className="flex-1" />
                    <span className="text-base font-bold text-primary">InnerLink</span>
                </div>
                {children}
            </div>

            <Sidebar isOpen={sidebarOpen} onClose={() => setSidebarOpen(false)} />

            <IncomingCallModal
                callData={incomingCall}
                onAccept={acceptIncoming}
                onReject={rejectIncoming}
            />

            {activeCall && (
                <ActiveCallBar
                    callData={activeCall}
                    onEndCall={hangUp}
                    remoteStream={remoteStream}
                    localStream={localStream}
                    isMicMuted={isMicMuted}
                    onToggleMute={toggleMute}
                />
            )}
        </div>
    );
}

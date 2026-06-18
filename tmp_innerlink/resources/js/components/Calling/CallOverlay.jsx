import { useCall } from '../../context/CallContext';
import IncomingCallModal from './IncomingCallModal';
import ActiveCallBar from './ActiveCallBar';

export default function CallOverlay() {
    const {
        incomingCall,
        activeCall,
        remoteStream,
        localStream,
        isMicMuted,
        acceptIncoming,
        rejectIncoming,
        hangUp,
        toggleMute,
    } = useCall();

    return (
        <>
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
        </>
    );
}

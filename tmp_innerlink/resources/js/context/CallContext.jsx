import { createContext, useContext, useState, useEffect, useCallback, useRef } from 'react';
import { useAuth } from './AuthContext';
import { useEcho } from './EchoContext';
import { acceptCall, rejectCall, endCall, sendIceCandidate } from '../api/calls';
import { initiateCall as apiInitiateCall } from '../api/calls';

const CallContext = createContext(null);

export function CallProvider({ children }) {
    const { user } = useAuth();
    const echo = useEcho();
    const [incomingCall, setIncomingCall] = useState(null);
    const [activeCall, setActiveCall] = useState(null);
    const [remoteStream, setRemoteStream] = useState(null);
    const [localStream, setLocalStream] = useState(null);
    const [isMicMuted, setIsMicMuted] = useState(false);
    const pcRef = useRef(null);
    const localStreamRef = useRef(null);

    const cleanupWebRTC = useCallback(() => {
        if (pcRef.current) {
            pcRef.current.close();
            pcRef.current = null;
        }
        if (localStreamRef.current) {
            localStreamRef.current.getTracks().forEach(t => t.stop());
            localStreamRef.current = null;
        }
        setRemoteStream(null);
        setLocalStream(null);
        setIsMicMuted(false);
    }, []);

    const startLocalStream = useCallback(async () => {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true, video: false });
        localStreamRef.current = stream;
        setLocalStream(stream);
        return stream;
    }, []);

    const createPeerConnection = useCallback((stream) => {
        const pc = new RTCPeerConnection({
            iceServers: [
                { urls: 'stun:stun.l.google.com:19302' },
                { urls: 'stun:stun1.l.google.com:19302' },
            ],
        });

        stream.getTracks().forEach(track => pc.addTrack(track, stream));

        pc.ontrack = (event) => {
            setRemoteStream(event.streams[0]);
        };

        pc.onicecandidate = (event) => {
            if (event.candidate && activeCall?.id) {
                sendIceCandidate(activeCall.id, event.candidate.toJSON()).catch(() => {});
            }
        };

        pcRef.current = pc;
        return pc;
    }, [activeCall?.id]);

    useEffect(() => {
        if (!echo || !user?.id) return;
        const channel = echo.private(`users.${user.id}`);

        channel.listen('.call.offer', (e) => {
            setIncomingCall(e);
        });

        channel.listen('.call.accepted', async (e) => {
            setIncomingCall(null);
            setActiveCall(e);
        });

        channel.listen('.call.rejected', (e) => {
            setActiveCall(prev => prev?.id === e.id ? null : prev);
            setIncomingCall(prev => prev?.id === e.id ? null : prev);
        });

        channel.listen('.call.ended', (e) => {
            setActiveCall(prev => prev?.id === e.id ? null : prev);
            setIncomingCall(prev => prev?.id === e.id ? null : prev);
            cleanupWebRTC();
        });

        return () => {
            echo.leave(`users.${user.id}`);
        };
    }, [echo, user?.id, cleanupWebRTC]);

    const initiate = useCallback(async (receiverId) => {
        const data = await apiInitiateCall(receiverId);
        const stream = await startLocalStream();
        const pc = createPeerConnection(stream);
        const offer = await pc.createOffer();
        await pc.setLocalDescription(offer);
        setActiveCall({ ...data, sdp: offer });
        return data;
    }, [startLocalStream, createPeerConnection]);

    const acceptIncoming = useCallback(async () => {
        if (!incomingCall) return;
        const data = await acceptCall(incomingCall.id);
        const stream = await startLocalStream();
        const pc = createPeerConnection(stream);
        const answer = await pc.createAnswer();
        await pc.setLocalDescription(answer);
        setActiveCall({ ...data, sdp: answer });
        setIncomingCall(null);
    }, [incomingCall, startLocalStream, createPeerConnection]);

    const rejectIncoming = useCallback(async () => {
        if (!incomingCall) return;
        try { await rejectCall(incomingCall.id); } catch {}
        setIncomingCall(null);
    }, [incomingCall]);

    const hangUp = useCallback(async () => {
        if (!activeCall) return;
        try { await endCall(activeCall.id); } catch {}
        cleanupWebRTC();
        setActiveCall(null);
    }, [activeCall, cleanupWebRTC]);

    const toggleMute = useCallback(() => {
        if (localStreamRef.current) {
            const track = localStreamRef.current.getAudioTracks()[0];
            if (track) {
                track.enabled = !track.enabled;
                setIsMicMuted(!track.enabled);
            }
        }
    }, []);

    return (
        <CallContext.Provider value={{
            incomingCall,
            activeCall,
            remoteStream,
            localStream,
            isMicMuted,
            initiate,
            acceptIncoming,
            rejectIncoming,
            hangUp,
            toggleMute,
        }}>
            {children}
        </CallContext.Provider>
    );
}

export function useCall() {
    const ctx = useContext(CallContext);
    if (!ctx) throw new Error('useCall must be used within CallProvider');
    return ctx;
}

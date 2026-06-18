import { useRef, useState, useCallback, useEffect } from 'react';

const ICE_SERVERS = {
    iceServers: [
        { urls: 'stun:stun.l.google.com:19302' },
        { urls: 'stun:stun1.l.google.com:19302' },
    ],
};

export default function useWebRTC({ callId, userId, echo, onIceCandidate }) {
    const pcRef = useRef(null);
    const localStreamRef = useRef(null);
    const remoteStreamRef = useRef(null);
    const [remoteStream, setRemoteStream] = useState(null);
    const [localStream, setLocalStream] = useState(null);
    const [isMicMuted, setIsMicMuted] = useState(false);

    const cleanup = useCallback(() => {
        if (pcRef.current) {
            pcRef.current.close();
            pcRef.current = null;
        }
        if (localStreamRef.current) {
            localStreamRef.current.getTracks().forEach(t => t.stop());
            localStreamRef.current = null;
            setLocalStream(null);
        }
        remoteStreamRef.current = null;
        setRemoteStream(null);
    }, []);

    const startLocalStream = useCallback(async () => {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true, video: false });
            localStreamRef.current = stream;
            setLocalStream(stream);
            return stream;
        } catch {
            throw new Error('Microphone access denied.');
        }
    }, []);

    const createPeerConnection = useCallback((stream) => {
        const pc = new RTCPeerConnection(ICE_SERVERS);

        stream.getTracks().forEach(track => pc.addTrack(track, stream));

        pc.ontrack = (event) => {
            remoteStreamRef.current = event.streams[0];
            setRemoteState(event.streams[0]);
        };

        function setRemoteState(s) {
            setRemoteStream(s);
            remoteStreamRef.current = s;
        }

        pc.onicecandidate = (event) => {
            if (event.candidate && onIceCandidate) {
                onIceCandidate(event.candidate.toJSON());
            }
        };

        pcRef.current = pc;
        return pc;
    }, [onIceCandidate]);

    const createOffer = useCallback(async () => {
        const stream = await startLocalStream();
        const pc = createPeerConnection(stream);
        const offer = await pc.createOffer();
        await pc.setLocalDescription(offer);
        return offer;
    }, [startLocalStream, createPeerConnection]);

    const createAnswer = useCallback(async () => {
        const stream = await startLocalStream();
        const pc = createPeerConnection(stream);
        const answer = await pc.createAnswer();
        await pc.setLocalDescription(answer);
        return answer;
    }, [startLocalStream, createPeerConnection]);

    const handleRemoteOffer = useCallback(async (offer) => {
        if (!pcRef.current) {
            const stream = await startLocalStream();
            createPeerConnection(stream);
        }
        const pc = pcRef.current;
        await pc.setRemoteDescription(new RTCSessionDescription(offer));
        const answer = await pc.createAnswer();
        await pc.setLocalDescription(answer);
        return answer;
    }, [startLocalStream, createPeerConnection]);

    const handleRemoteAnswer = useCallback(async (answer) => {
        if (!pcRef.current) return;
        await pcRef.current.setRemoteDescription(new RTCSessionDescription(answer));
    }, []);

    const addIceCandidate = useCallback(async (candidate) => {
        if (!pcRef.current) return;
        try {
            await pcRef.current.addIceCandidate(new RTCIceCandidate(candidate));
        } catch {
            // ignore
        }
    }, []);

    const toggleMute = useCallback(() => {
        if (localStreamRef.current) {
            const audioTrack = localStreamRef.current.getAudioTracks()[0];
            if (audioTrack) {
                audioTrack.enabled = !audioTrack.enabled;
                setIsMicMuted(!audioTrack.enabled);
            }
        }
    }, []);

    useEffect(() => {
        if (!echo || !callId) return;

        const channel = echo.private(`calls.${callId}`);

        channel.listen('.call.ice-candidate', (e) => {
            if (e.user_id !== userId && e.candidate) {
                addIceCandidate(e.candidate);
            }
        });

        return () => {
            echo.leave(`calls.${callId}`);
        };
    }, [echo, callId, userId, addIceCandidate]);

    useEffect(() => {
        return cleanup;
    }, [cleanup]);

    return {
        localStream,
        remoteStream,
        isMicMuted,
        createOffer,
        createAnswer,
        handleRemoteOffer,
        handleRemoteAnswer,
        addIceCandidate,
        toggleMute,
        cleanup,
        startLocalStream,
    };
}

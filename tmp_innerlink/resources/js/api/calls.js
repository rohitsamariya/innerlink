import client from './client';

export async function initiateCall(receiverId) {
    const response = await client.post('/calls/initiate', { receiver_id: receiverId });
    return response.data;
}

export async function acceptCall(callId) {
    const response = await client.post(`/calls/${callId}/accept`);
    return response.data;
}

export async function rejectCall(callId) {
    const response = await client.post(`/calls/${callId}/reject`);
    return response.data;
}

export async function endCall(callId) {
    const response = await client.post(`/calls/${callId}/end`);
    return response.data;
}

export async function sendIceCandidate(callId, candidate) {
    await client.post(`/calls/${callId}/ice-candidate`, { candidate });
}

export async function fetchCallHistory() {
    const response = await client.get('/calls/history');
    return response.data;
}

export async function fetchActiveCall() {
    const response = await client.get('/calls/active');
    return response.data;
}

import client from './client';

export async function fetchMessages(groupId) {
    const response = await client.get(`/groups/${groupId}/messages`);
    return response.data;
}

export async function sendMessage(groupId, messageText) {
    const response = await client.post(`/groups/${groupId}/messages`, { message_text: messageText });
    return response.data;
}

export async function sendTyping(groupId, action) {
    await client.post(`/groups/${groupId}/typing`, { action });
}

export async function searchMessages(groupId, query) {
    const response = await client.get(`/groups/${groupId}/messages/search`, { params: { q: query } });
    return response.data;
}

export async function markMessagesRead(groupId, messageIds) {
    const response = await client.post(`/groups/${groupId}/messages/read`, { message_ids: messageIds });
    return response.data;
}

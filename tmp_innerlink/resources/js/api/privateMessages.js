import client from './client';

function unwrap(response) {
    return response.data?.data ?? response.data;
}

export async function fetchPrivateMessages(userId) {
    const response = await client.get(`/private-messages/${userId}`);
    return response.data?.data ?? response.data;
}

export async function sendPrivateMessage(userId, messageText) {
    const response = await client.post(`/private-messages/${userId}`, { message_text: messageText });
    return response.data?.data ?? response.data;
}

export async function markPrivateMessagesRead(userId) {
    await client.post(`/private-messages/${userId}/read`);
}

export async function fetchPrivateContacts() {
    const response = await client.get('/private-messages/contacts');
    return unwrap(response);
}

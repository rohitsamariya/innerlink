import client from './client';

function unwrap(response) {
    return response.data?.data ?? response.data;
}

export async function fetchUsers() {
    const response = await client.get('/users');
    return unwrap(response);
}

export async function toggleUserStatus(userId) {
    const response = await client.patch(`/users/${userId}/toggle-status`);
    return unwrap(response);
}

export async function updateUser(userId, data) {
    const response = await client.patch(`/users/${userId}`, data);
    return unwrap(response);
}

export async function fetchGroupMembers(groupId) {
    const response = await client.get(`/admin/groups/${groupId}/members`);
    return unwrap(response);
}

export async function addGroupMember(groupId, userId) {
    const response = await client.post(`/admin/groups/${groupId}/members`, { user_id: userId });
    return unwrap(response);
}

export async function removeGroupMember(groupId, userId) {
    const response = await client.delete(`/admin/groups/${groupId}/members/${userId}`);
    return unwrap(response);
}

export function getMessageDownloadUrl(groupId) {
    return `/api/admin/groups/${groupId}/messages/download`;
}

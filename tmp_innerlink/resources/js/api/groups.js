import client from './client';

export async function fetchGroups() {
    const response = await client.get('/groups');
    return response.data;
}

export async function createGroup(name) {
    const response = await client.post('/groups', { name });
    return response.data;
}

export async function fetchGroup(groupId) {
    const response = await client.get(`/groups/${groupId}`);
    return response.data;
}

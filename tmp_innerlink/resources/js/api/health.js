import client from './client';

export async function checkLive() {
    const response = await client.get('/health/live');
    return response.data;
}

export async function checkReady() {
    const response = await client.get('/health/ready');
    return response.data;
}

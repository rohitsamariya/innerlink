import client from './client';

export async function fetchExports() {
    const response = await client.get('/admin/exports');
    return response.data;
}

export async function createExport(format, filters = {}) {
    const response = await client.post('/admin/exports', { format, filters });
    return response.data;
}

export async function fetchExport(id) {
    const response = await client.get(`/admin/exports/${id}`);
    return response.data;
}

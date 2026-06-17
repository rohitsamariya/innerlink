import client, { fetchCsrfToken, setAuthToken } from './client';

export async function login(email, password) {
    await fetchCsrfToken();
    const response = await client.post('/auth/login', { email, password });
    const token = response.data.meta?.token;
    if (token) {
        setAuthToken(token);
        localStorage.setItem('auth_token', token);
    }
    return response.data;
}

export async function logout() {
    try {
        await client.post('/auth/logout');
    } finally {
        setAuthToken(null);
        localStorage.removeItem('auth_token');
    }
}

export async function fetchCurrentUser() {
    const token = localStorage.getItem('auth_token');
    if (!token) return null;
    setAuthToken(token);
    const response = await client.get('/auth/me');
    return response.data?.data ?? response.data;
}

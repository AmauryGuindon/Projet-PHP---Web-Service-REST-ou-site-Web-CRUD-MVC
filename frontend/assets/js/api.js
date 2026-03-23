const API_BASE = 'http://localhost:8000/api/v1';

const Api = {
    getToken: () => localStorage.getItem('auth_token'),
    setToken: (t) => localStorage.setItem('auth_token', t),
    clearToken: () => localStorage.removeItem('auth_token'),
    getUser: () => JSON.parse(localStorage.getItem('auth_user') || 'null'),
    setUser: (u) => localStorage.setItem('auth_user', JSON.stringify(u)),
    clearUser: () => localStorage.removeItem('auth_user'),

    isLoggedIn: () => !!localStorage.getItem('auth_token'),

    headers(auth = false) {
        const h = { 'Content-Type': 'application/json', 'Accept': 'application/json' };
        if (auth) h['Authorization'] = `Bearer ${this.getToken()}`;
        return h;
    },

    async get(path, auth = false) {
        const res = await fetch(`${API_BASE}${path}`, { headers: this.headers(auth) });
        if (!res.ok) {
            const data = await res.json().catch(() => ({}));
            throw { status: res.status, data };
        }
        return res.json();
    },

    async post(path, body, auth = false) {
        const res = await fetch(`${API_BASE}${path}`, {
            method: 'POST',
            headers: this.headers(auth),
            body: JSON.stringify(body),
        });
        if (!res.ok) {
            const data = await res.json().catch(() => ({}));
            throw { status: res.status, data };
        }
        if (res.status === 204) return null;
        return res.json();
    },

    async put(path, body, auth = false) {
        const res = await fetch(`${API_BASE}${path}`, {
            method: 'PUT',
            headers: this.headers(auth),
            body: JSON.stringify(body),
        });
        if (!res.ok) {
            const data = await res.json().catch(() => ({}));
            throw { status: res.status, data };
        }
        return res.json();
    },

    async delete(path, auth = false) {
        const res = await fetch(`${API_BASE}${path}`, {
            method: 'DELETE',
            headers: this.headers(auth),
        });
        return res.status;
    },
};

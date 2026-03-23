function updateNavAuth() {
    const user = Api.getUser();
    const navUser = document.getElementById('nav-user');
    const navLogin = document.getElementById('nav-login');
    const navLogout = document.getElementById('nav-logout');
    const navBets = document.getElementById('nav-bets');
    const navAdmin = document.getElementById('nav-admin');

    if (user) {
        if (navUser) navUser.textContent = user.name;
        if (navLogin) navLogin.style.display = 'none';
        if (navLogout) navLogout.style.display = '';
        if (navBets) navBets.style.display = '';
        if (navAdmin && user.role === 'admin') navAdmin.style.display = '';
    } else {
        if (navUser) navUser.textContent = '';
        if (navLogin) navLogin.style.display = '';
        if (navLogout) navLogout.style.display = 'none';
        if (navBets) navBets.style.display = 'none';
        if (navAdmin) navAdmin.style.display = 'none';
    }
}

async function logout() {
    try {
        await Api.post('/auth/logout', {}, true);
    } catch (_) {}
    Api.clearToken();
    Api.clearUser();
    window.location.href = 'login.html';
}

document.addEventListener('DOMContentLoaded', () => {
    updateNavAuth();
    const logoutBtn = document.getElementById('nav-logout');
    if (logoutBtn) logoutBtn.addEventListener('click', (e) => { e.preventDefault(); logout(); });
});

function updateNavAuth() {
    const user = Api.getUser();
    const navUser    = document.getElementById('nav-user');
    const navAvatar  = document.getElementById('nav-avatar');
    const navUserPill = document.getElementById('nav-user-pill');
    const navLogin   = document.getElementById('nav-login');
    const navLogout  = document.getElementById('nav-logout');
    const navBets    = document.getElementById('nav-bets');
    const navAdmin   = document.getElementById('nav-admin');

    if (user) {
        if (navUser) navUser.textContent = user.name;
        if (navAvatar) navAvatar.textContent = user.name.charAt(0).toUpperCase();
        if (navUserPill) navUserPill.style.display = '';
        if (navLogin)  navLogin.style.display  = 'none';
        if (navLogout) navLogout.style.display  = '';
        if (navBets)   navBets.style.display    = '';
        if (navAdmin && user.role === 'admin') navAdmin.style.display = '';
    } else {
        if (navUserPill) navUserPill.style.display = 'none';
        if (navLogin)  navLogin.style.display  = '';
        if (navLogout) navLogout.style.display  = 'none';
        if (navBets)   navBets.style.display    = 'none';
        if (navAdmin)  navAdmin.style.display   = 'none';
    }
}

async function logout() {
    try { await Api.post('/auth/logout', {}, true); } catch(_) {}
    Api.clearToken(); Api.clearUser();
    window.location.href = 'login.html';
}

document.addEventListener('DOMContentLoaded', () => {
    updateNavAuth();
    const logoutBtn = document.getElementById('nav-logout');
    if (logoutBtn) logoutBtn.addEventListener('click', e => { e.preventDefault(); logout(); });

    // Mobile menu toggle
    const toggle = document.getElementById('nav-toggle');
    const links  = document.getElementById('nav-links');
    if (toggle && links) {
        toggle.addEventListener('click', () => links.classList.toggle('open'));
    }
});

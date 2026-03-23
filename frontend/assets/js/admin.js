document.addEventListener('DOMContentLoaded', () => {
    const user = Api.getUser();
    if (!Api.isLoggedIn() || !user || user.role !== 'admin') {
        window.location.href = 'login.html';
        return;
    }
    showAdminTab('sports', document.querySelector('.tab-btn.active'));
});

function showAdminTab(tab, btn) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');
    const handlers = { sports: loadSportsAdmin, teams: loadTeamsAdmin, matches: loadMatchesAdmin, odds: loadOddsAdmin };
    if (handlers[tab]) handlers[tab]();
}

function showAlert(msg, type = 'success') {
    const el = document.getElementById('admin-alert');
    el.className = `alert alert-${type}`;
    el.textContent = msg;
    el.style.display = '';
    setTimeout(() => { el.style.display = 'none'; }, 3500);
}

async function loadSportsAdmin() {
    const content = document.getElementById('admin-content');
    content.innerHTML = `
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
            <h2>Sports</h2>
            <button class="btn btn-success" onclick="createSportForm()">+ Ajouter</button>
        </div>
        <div id="sports-table"><div class="loading">Chargement...</div></div>`;

    try {
        const data = await Api.get('/sports', true);
        const sports = data.data || [];
        document.getElementById('sports-table').innerHTML = `<table>
            <thead><tr><th>Nom</th><th>Slug</th><th>Actif</th><th>Actions</th></tr></thead>
            <tbody>${sports.map(s => `<tr>
                <td>${s.name}</td><td>${s.slug}</td>
                <td>${s.is_active ? '✅' : '❌'}</td>
                <td>
                    <button class="btn btn-danger btn-sm" onclick="deleteSport('${s.id}','${s.name}')">Supprimer</button>
                </td>
            </tr>`).join('')}</tbody>
        </table>`;
    } catch (e) { document.getElementById('sports-table').innerHTML = '<p class="alert alert-danger">Erreur.</p>'; }
}

function createSportForm() {
    const form = `<div class="card" style="margin-bottom:1rem">
        <h3 style="margin-bottom:1rem">Nouveau sport</h3>
        <div class="form-group"><label>Nom</label><input type="text" id="new-sport-name" required></div>
        <div class="form-group"><label>Slug</label><input type="text" id="new-sport-slug" required></div>
        <div style="display:flex;gap:.5rem">
            <button class="btn btn-success" onclick="submitCreateSport()">Créer</button>
            <button class="btn btn-outline" onclick="loadSportsAdmin()">Annuler</button>
        </div>
    </div>`;
    document.getElementById('admin-content').insertAdjacentHTML('afterbegin', form);
    document.getElementById('new-sport-name').addEventListener('input', e => {
        document.getElementById('new-sport-slug').value = e.target.value.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '');
    });
}

async function submitCreateSport() {
    try {
        await Api.post('/sports', {
            name:      document.getElementById('new-sport-name').value,
            slug:      document.getElementById('new-sport-slug').value,
            is_active: true,
        }, true);
        showAlert('Sport créé.');
        loadSportsAdmin();
    } catch (e) {
        showAlert(e.data?.message || 'Erreur.', 'danger');
    }
}

async function deleteSport(id, name) {
    if (!confirm(`Supprimer le sport "${name}" ?`)) return;
    try {
        await Api.delete(`/sports/${id}`, true);
        showAlert(`Sport "${name}" supprimé.`);
        loadSportsAdmin();
    } catch (e) { showAlert('Erreur lors de la suppression.', 'danger'); }
}

async function loadTeamsAdmin() {
    const content = document.getElementById('admin-content');
    content.innerHTML = '<div class="loading">Chargement...</div>';
    try {
        const [teamsData, sportsData] = await Promise.all([Api.get('/teams', true), Api.get('/sports', true)]);
        const teams  = teamsData.data || [];
        const sports = sportsData.data || [];
        const sportMap = Object.fromEntries(sports.map(s => [s.id, s.name]));

        content.innerHTML = `<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
            <h2>Équipes</h2></div>
            <table>
                <thead><tr><th>Nom</th><th>Abrév.</th><th>Sport</th><th>Pays</th><th>Actions</th></tr></thead>
                <tbody>${teams.map(t => `<tr>
                    <td>${t.name}</td><td>${t.short_name}</td>
                    <td>${sportMap[t.sport_id] ?? t.sport_id}</td>
                    <td>${t.country ?? '—'}</td>
                    <td><button class="btn btn-danger btn-sm" onclick="deleteTeam('${t.id}','${t.name}')">Supprimer</button></td>
                </tr>`).join('')}</tbody>
            </table>`;
    } catch (e) { content.innerHTML = '<p class="alert alert-danger">Erreur.</p>'; }
}

async function deleteTeam(id, name) {
    if (!confirm(`Supprimer l'équipe "${name}" ?`)) return;
    try {
        await Api.delete(`/teams/${id}`, true);
        showAlert(`Équipe "${name}" supprimée.`);
        loadTeamsAdmin();
    } catch (e) { showAlert('Erreur.', 'danger'); }
}

async function loadMatchesAdmin() {
    const content = document.getElementById('admin-content');
    content.innerHTML = '<div class="loading">Chargement...</div>';
    try {
        const data = await Api.get('/matches', true);
        const matches = data.data || [];
        content.innerHTML = `<table>
            <thead><tr><th>Match</th><th>Sport</th><th>Date</th><th>Statut</th><th>Score</th><th>Actions</th></tr></thead>
            <tbody>${matches.map(m => `<tr>
                <td>${m.home_team?.name ?? '?'} vs ${m.away_team?.name ?? '?'}</td>
                <td>${m.sport?.name ?? '?'}</td>
                <td>${new Date(m.starts_at).toLocaleDateString('fr-FR')}</td>
                <td><span class="badge badge-${m.status}">${m.status}</span></td>
                <td>${m.home_score != null ? m.home_score + '-' + m.away_score : '—'}</td>
                <td style="display:flex;gap:.4rem;flex-wrap:wrap">
                    ${m.status !== 'finished' ? `<button class="btn btn-primary btn-sm" onclick="openSettleModal('${m.id}')">Résoudre</button>` : ''}
                    <button class="btn btn-danger btn-sm" onclick="deleteMatch('${m.id}')">Supprimer</button>
                </td>
            </tr>`).join('')}</tbody>
        </table>`;
    } catch (e) { content.innerHTML = '<p class="alert alert-danger">Erreur.</p>'; }
}

async function openSettleModal(matchId) {
    const homeScore = prompt('Score domicile:');
    if (homeScore === null) return;
    const awayScore = prompt('Score extérieur:');
    if (awayScore === null) return;

    try {
        await Api.put(`/matches/${matchId}`, { home_score: parseInt(homeScore), away_score: parseInt(awayScore) }, true);
        await Api.post(`/matches/${matchId}/settle`, {}, true);
        showAlert('Match résolu, paris mis à jour.');
        loadMatchesAdmin();
    } catch (e) {
        showAlert(e.data?.message || 'Erreur lors de la résolution.', 'danger');
    }
}

async function deleteMatch(id) {
    if (!confirm('Supprimer ce match ?')) return;
    try {
        await Api.delete(`/matches/${id}`, true);
        showAlert('Match supprimé.');
        loadMatchesAdmin();
    } catch (e) { showAlert('Erreur.', 'danger'); }
}

async function loadOddsAdmin() {
    const content = document.getElementById('admin-content');
    content.innerHTML = '<div class="loading">Chargement...</div>';
    try {
        const data = await Api.get('/odds', true);
        const odds = data.data || [];
        content.innerHTML = `<table>
            <thead><tr><th>Match ID</th><th>1</th><th>N</th><th>2</th><th>Bookmaker</th><th>Source</th><th>Actions</th></tr></thead>
            <tbody>${odds.map(o => `<tr>
                <td><small>${o.match_id}</small></td>
                <td>${o.home_win}</td><td>${o.draw}</td><td>${o.away_win}</td>
                <td>${o.bookmaker}</td><td>${o.source ?? 'internal'}</td>
                <td><button class="btn btn-danger btn-sm" onclick="deleteOdd('${o.id}')">Supprimer</button></td>
            </tr>`).join('')}</tbody>
        </table>`;
    } catch (e) { content.innerHTML = '<p class="alert alert-danger">Erreur.</p>'; }
}

async function deleteOdd(id) {
    if (!confirm('Supprimer cette cote ?')) return;
    try {
        await Api.delete(`/odds/${id}`, true);
        showAlert('Cote supprimée.');
        loadOddsAdmin();
    } catch (e) { showAlert('Erreur.', 'danger'); }
}

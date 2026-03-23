document.addEventListener('DOMContentLoaded', () => {
    if (!Api.isLoggedIn()) {
        window.location.href = 'login.html';
        return;
    }
    loadBets();
    loadStats();
});

async function loadBets() {
    const status = document.getElementById('filter-status')?.value;
    const qs = status ? `?status=${status}` : '';
    const container = document.getElementById('bets-container');
    container.innerHTML = '<div class="loading">Chargement...</div>';

    try {
        const data = await Api.get('/bets' + qs, true);
        const bets = data.data || [];
        if (!bets.length) { container.innerHTML = '<p class="empty">Aucun pari trouvé.</p>'; return; }

        const outcomeLabels = { home_win: 'Victoire domicile', draw: 'Match nul', away_win: 'Victoire extérieur' };

        container.innerHTML = `<table>
            <thead><tr>
                <th>Match</th><th>Pronostic</th><th>Mise</th><th>Cote</th><th>Gain potentiel</th><th>Statut</th>
            </tr></thead>
            <tbody>${bets.map(b => `<tr>
                <td>${b.match?.home_team_id ?? '?'} vs ${b.match?.away_team_id ?? '?'}</td>
                <td>${outcomeLabels[b.predicted_outcome] ?? b.predicted_outcome}</td>
                <td>${Number(b.amount).toFixed(2)} €</td>
                <td>${b.odds_value}</td>
                <td>${Number(b.potential_gain).toFixed(2)} €</td>
                <td><span class="badge badge-${b.status}">${b.status}</span></td>
            </tr>`).join('')}</tbody>
        </table>`;
    } catch (e) {
        container.innerHTML = '<p class="alert alert-danger">Erreur de chargement.</p>';
    }
}

async function loadStats() {
    try {
        const data = await Api.get('/stats/user-performance', true);
        const results = data.data || [];
        const userId  = Api.getUser()?.id;
        const mine    = results.find(r => String(r._id) === String(userId));

        if (!mine) return;
        const bar = document.getElementById('stats-bar');
        bar.innerHTML = [
            { label: 'Paris', value: mine.total_bets },
            { label: 'Misé', value: Number(mine.total_staked || 0).toFixed(2) + ' €' },
            { label: 'Gagnés', value: mine.won_bets },
            { label: 'Gains', value: Number(mine.total_gained || 0).toFixed(2) + ' €' },
            { label: 'ROI', value: (mine.roi_percent ?? 0).toFixed(1) + ' %' },
        ].map(s => `<div class="card" style="flex:1;min-width:120px;text-align:center">
            <div style="font-size:1.4rem;font-weight:700;color:var(--primary)">${s.value}</div>
            <div style="font-size:.8rem;color:var(--text-muted)">${s.label}</div>
        </div>`).join('');
    } catch (_) {}
}

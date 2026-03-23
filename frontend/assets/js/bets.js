document.addEventListener('DOMContentLoaded', () => {
    if (!Api.isLoggedIn()) { window.location.href = 'login.html'; return; }
    loadStats();
    loadBets();
});

async function loadStats() {
    try {
        const data = await Api.get('/stats/user-performance', true);
        const results = data.data || [];
        const userId  = Api.getUser()?.id;
        const mine    = results.find(r => String(r._id) === String(userId));
        const bar     = document.getElementById('stats-bar');
        if (!bar) return;

        const stats = mine ? [
            { label: 'Total paris', value: mine.total_bets, icon: '🎯' },
            { label: 'Total misé', value: Number(mine.total_staked||0).toFixed(2)+' €', icon: '💰' },
            { label: 'Paris gagnés', value: mine.won_bets, icon: '🏆' },
            { label: 'ROI', value: (mine.roi_percent??0).toFixed(1)+'%', icon: '📈' },
        ] : [
            { label: 'Total paris', value: '0', icon: '🎯' },
            { label: 'Total misé', value: '0 €', icon: '💰' },
            { label: 'Paris gagnés', value: '0', icon: '🏆' },
            { label: 'ROI', value: '0%', icon: '📈' },
        ];
        bar.innerHTML = stats.map(s => `<div class="stat-card"><div style="font-size:1.5rem">${s.icon}</div><div class="stat-value">${s.value}</div><div class="stat-label">${s.label}</div></div>`).join('');
    } catch(_) {}
}

async function loadBets() {
    const status = document.getElementById('filter-status')?.value;
    const qs = status ? `?status=${status}` : '';
    const container = document.getElementById('bets-container');
    container.innerHTML = '<div class="loading"></div>';

    try {
        const data = await Api.get('/bets' + qs, true);
        const bets = data.data || [];
        if (!bets.length) { container.innerHTML = '<p class="empty">Aucun pari trouvé.</p>'; return; }

        const labels = { home_win:'Victoire dom.', draw:'Match nul', away_win:'Victoire ext.' };
        container.innerHTML = `<div class="table-wrapper"><table>
            <thead><tr><th>Match</th><th>Pronostic</th><th>Mise</th><th>Cote</th><th>Gain potentiel</th><th>Statut</th></tr></thead>
            <tbody>${bets.map(b => `<tr>
                <td style="font-weight:600">${b.match?.home_team_id??'?'} vs ${b.match?.away_team_id??'?'}</td>
                <td>${labels[b.predicted_outcome]||b.predicted_outcome}</td>
                <td>${Number(b.amount).toFixed(2)} €</td>
                <td style="color:var(--gold-light);font-weight:700">${b.odds_value}</td>
                <td style="font-weight:600">${Number(b.potential_gain).toFixed(2)} €</td>
                <td><span class="badge badge-${b.status}">${b.status}</span></td>
            </tr>`).join('')}</tbody>
        </table></div>`;
    } catch(e) { container.innerHTML = '<p class="alert alert-danger">Erreur de chargement.</p>'; }
}

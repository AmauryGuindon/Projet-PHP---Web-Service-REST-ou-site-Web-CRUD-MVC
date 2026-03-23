let currentMatchId = null;

async function loadSportsFilter() {
    try {
        const data = await Api.get('/sports');
        const sel = document.getElementById('filter-sport');
        (data.data || []).forEach(s => {
            const o = document.createElement('option');
            o.value = s.id; o.textContent = s.name;
            sel.appendChild(o);
        });
    } catch (_) {}
}

async function loadMatches() {
    const sportId = document.getElementById('filter-sport')?.value;
    const status  = document.getElementById('filter-status')?.value;
    let qs = [];
    if (sportId) qs.push(`sport_id=${sportId}`);
    if (status)  qs.push(`status=${status}`);

    const container = document.getElementById('matches-container');
    container.innerHTML = '<div class="loading">Chargement...</div>';

    try {
        const data = await Api.get('/matches' + (qs.length ? '?' + qs.join('&') : ''));
        const matches = data.data || [];
        if (!matches.length) { container.innerHTML = '<p class="empty">Aucun match trouvé.</p>'; return; }

        // Load odds for each match
        const oddsData = {};
        await Promise.all(matches.map(async m => {
            try {
                const o = await Api.get(`/odds?match_id=${m.id}`);
                oddsData[m.id] = (o.data || [])[0] || null;
            } catch (_) { oddsData[m.id] = null; }
        }));

        container.innerHTML = matches.map(m => {
            const odd = oddsData[m.id];
            const canBet = Api.isLoggedIn() && m.status === 'scheduled';
            return `<div class="match-card" style="margin-bottom:1rem">
                <div class="match-teams">${m.home_team?.name ?? '?'} <span style="color:var(--text-muted)">vs</span> ${m.away_team?.name ?? '?'}</div>
                <div class="match-meta">${m.sport?.name ?? ''} · ${new Date(m.starts_at).toLocaleString('fr-FR')}</div>
                <div style="margin:.4rem 0"><span class="badge badge-${m.status}">${m.status}</span></div>
                ${odd ? `<div class="match-odds">
                    <span class="odd-badge" title="Victoire domicile">1: ${odd.home_win}</span>
                    <span class="odd-badge" title="Nul">N: ${odd.draw}</span>
                    <span class="odd-badge" title="Victoire extérieur">2: ${odd.away_win}</span>
                    <small style="color:var(--text-muted);align-self:center">${odd.bookmaker}</small>
                </div>` : '<div class="match-meta">Aucune cote disponible</div>'}
                ${canBet ? `<button class="btn btn-primary btn-sm" onclick="openBetModal('${m.id}','${m.home_team?.name ?? '?'}','${m.away_team?.name ?? '?'}')">Parier</button>` : ''}
                ${!Api.isLoggedIn() && m.status === 'scheduled' ? '<a class="btn btn-outline btn-sm" href="login.html">Connexion pour parier</a>' : ''}
            </div>`;
        }).join('');
    } catch (e) {
        container.innerHTML = '<p class="alert alert-danger">Erreur de chargement.</p>';
    }
}

function openBetModal(matchId, homeName, awayName) {
    currentMatchId = matchId;
    document.getElementById('bet-match-id').value = matchId;
    document.getElementById('bet-modal-content').innerHTML =
        `<p style="margin-bottom:1rem;font-weight:600">${homeName} vs ${awayName}</p>`;
    document.getElementById('bet-form-error').style.display = 'none';
    document.getElementById('bet-modal').classList.add('open');
}

function closeBetModal() {
    document.getElementById('bet-modal').classList.remove('open');
    currentMatchId = null;
}

async function submitBet(event) {
    event.preventDefault();
    const matchId  = document.getElementById('bet-match-id').value;
    const outcome  = document.getElementById('bet-outcome').value;
    const amount   = parseFloat(document.getElementById('bet-amount').value);
    const errEl    = document.getElementById('bet-form-error');

    try {
        await Api.post('/bets', { match_id: matchId, predicted_outcome: outcome, amount }, true);
        closeBetModal();
        alert('Pari placé avec succès !');
    } catch (e) {
        const msg = e.data?.message || e.data?.errors?.match_id?.[0] || 'Erreur lors du pari.';
        errEl.textContent = msg;
        errEl.style.display = '';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    loadSportsFilter();
    const params = new URLSearchParams(window.location.search);
    if (params.get('match')) {
        // Pre-open bet modal for specific match (from index.html link)
    }
    loadMatches();
});

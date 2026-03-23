const sportIcons = { football:'⚽', basketball:'🏀', rugby:'🏉', tennis:'🎾', handball:'🤾', volleyball:'🏐', cyclisme:'🚴', hockey:'🏒' };
let currentOdds = null;
let currentMatchId = null;

async function loadSportsFilter() {
    try {
        const data = await Api.get('/sports');
        const sel = document.getElementById('filter-sport');
        (data.data || []).forEach(s => {
            const o = document.createElement('option');
            o.value = s.id;
            o.textContent = (sportIcons[s.slug]||'🏆') + ' ' + s.name;
            sel.appendChild(o);
        });
    } catch(_) {}
}

async function loadMatches() {
    const sportId = document.getElementById('filter-sport')?.value;
    const status  = document.getElementById('filter-status')?.value;
    let qs = [];
    if (sportId) qs.push(`sport_id=${sportId}`);
    if (status)  qs.push(`status=${status}`);

    const container = document.getElementById('matches-container');
    container.innerHTML = '<div class="loading"></div>';

    try {
        const data = await Api.get('/matches' + (qs.length ? '?' + qs.join('&') : ''));
        const matches = data.data || [];
        if (!matches.length) { container.innerHTML = '<p class="empty">Aucun match trouvé.</p>'; return; }

        const oddsData = {};
        await Promise.all(matches.map(async m => {
            try { const o = await Api.get(`/odds?match_id=${m.id}`); oddsData[m.id] = (o.data||[])[0]||null; } catch(_){}
        }));

        const canBet = Api.isLoggedIn();

        container.innerHTML = `<div class="grid-3">${matches.map(m => {
            const odd = oddsData[m.id];
            const isScheduled = m.status === 'scheduled';
            return `<div class="match-card">
                <div style="display:flex;justify-content:space-between;align-items:center">
                    <span class="match-sport-tag">${sportIcons[m.sport?.slug]||'🏆'} ${m.sport?.name||''}</span>
                    <span class="badge badge-${m.status}">${m.status}</span>
                </div>
                <div class="match-teams">
                    <div class="team-name">${m.home_team?.name||'?'}</div>
                    <div class="vs-badge">VS</div>
                    <div class="team-name away">${m.away_team?.name||'?'}</div>
                </div>
                ${m.status==='finished' && m.home_score!=null ? `<div style="text-align:center;font-size:1.5rem;font-weight:800;letter-spacing:0.1em">${m.home_score} — ${m.away_score}</div>` : `<div class="match-date">📅 ${new Date(m.starts_at).toLocaleString('fr-FR',{weekday:'short',day:'numeric',month:'short',hour:'2-digit',minute:'2-digit'})}</div>`}
                ${odd ? `<div class="match-odds">
                    <button class="odd-btn" onclick="${canBet&&isScheduled?`openBetModal('${m.id}','${(m.home_team?.name||'').replace(/'/g,"\\'")}','${(m.away_team?.name||'').replace(/'/g,"\\'")}','home_win',${odd.home_win},${odd.draw},${odd.away_win})`:'void(0)'}">
                        <span class="odd-label">1</span><span class="odd-value">${odd.home_win}</span>
                    </button>
                    <button class="odd-btn" onclick="${canBet&&isScheduled?`openBetModal('${m.id}','${(m.home_team?.name||'').replace(/'/g,"\\'")}','${(m.away_team?.name||'').replace(/'/g,"\\'")}','draw',${odd.home_win},${odd.draw},${odd.away_win})`:'void(0)'}">
                        <span class="odd-label">N</span><span class="odd-value">${odd.draw}</span>
                    </button>
                    <button class="odd-btn" onclick="${canBet&&isScheduled?`openBetModal('${m.id}','${(m.home_team?.name||'').replace(/'/g,"\\'")}','${(m.away_team?.name||'').replace(/'/g,"\\'")}','away_win',${odd.home_win},${odd.draw},${odd.away_win})`:'void(0)'}">
                        <span class="odd-label">2</span><span class="odd-value">${odd.away_win}</span>
                    </button>
                </div>` : `<div class="match-date" style="color:var(--text-dim)">Cotes bientôt disponibles</div>`}
                ${!canBet && isScheduled ? `<a href="login.html" class="btn btn-ghost btn-sm" style="width:100%;justify-content:center">Connexion pour parier</a>` : ''}
            </div>`;
        }).join('')}</div>`;
    } catch(e) { container.innerHTML = '<p class="alert alert-danger">Erreur de chargement.</p>'; }
}

function openBetModal(matchId, homeName, awayName, defaultOutcome, homeWin, draw, awayWin) {
    currentMatchId = matchId;
    currentOdds = { home_win: homeWin, draw, away_win: awayWin };
    document.getElementById('bet-match-id').value = matchId;
    document.getElementById('bet-modal-content').innerHTML = `
        <div style="background:var(--bg-secondary);border-radius:8px;padding:.85rem;margin-bottom:.5rem">
            <div style="font-size:.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:.3rem">Match</div>
            <div style="font-weight:700">${homeName} <span style="color:var(--text-muted)">vs</span> ${awayName}</div>
        </div>`;
    if (defaultOutcome) document.getElementById('bet-outcome').value = defaultOutcome;
    document.getElementById('bet-form-error').style.display = 'none';
    updatePotentialGain();
    document.getElementById('bet-modal').classList.add('open');
}

function closeBetModal() {
    document.getElementById('bet-modal').classList.remove('open');
    currentMatchId = null; currentOdds = null;
}

function updatePotentialGain() {
    if (!currentOdds) return;
    const outcome = document.getElementById('bet-outcome').value;
    const amount  = parseFloat(document.getElementById('bet-amount').value) || 0;
    const oddVal  = currentOdds[outcome] || 1;
    const gain    = (amount * oddVal).toFixed(2);
    const el      = document.getElementById('bet-potential');
    const gainEl  = document.getElementById('bet-gain-value');
    if (amount > 0) { gainEl.textContent = gain + ' €'; el.style.display = ''; }
    else { el.style.display = 'none'; }
}

async function submitBet(event) {
    event.preventDefault();
    const matchId = document.getElementById('bet-match-id').value;
    const outcome = document.getElementById('bet-outcome').value;
    const amount  = parseFloat(document.getElementById('bet-amount').value);
    const errEl   = document.getElementById('bet-form-error');
    const btn     = event.target.querySelector('button[type=submit]');
    btn.disabled = true; btn.textContent = 'Validation...';
    try {
        await Api.post('/bets', { match_id: matchId, predicted_outcome: outcome, amount }, true);
        closeBetModal();
        // Show success toast
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-success';
        alertDiv.style.cssText = 'position:fixed;bottom:1.5rem;right:1.5rem;z-index:300;min-width:280px';
        alertDiv.textContent = '✅ Pari placé avec succès !';
        document.body.appendChild(alertDiv);
        setTimeout(() => alertDiv.remove(), 3500);
    } catch(e) {
        errEl.textContent = e.data?.message || e.data?.errors?.match_id?.[0] || 'Erreur lors du pari.';
        errEl.style.display = '';
    }
    btn.disabled = false; btn.textContent = 'Confirmer le pari';
}

document.addEventListener('DOMContentLoaded', () => {
    loadSportsFilter();
    loadMatches();
    document.getElementById('bet-outcome')?.addEventListener('change', updatePotentialGain);
    document.getElementById('bet-amount')?.addEventListener('input', updatePotentialGain);
});

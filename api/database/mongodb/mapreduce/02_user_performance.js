// =============================================================================
// Map Reduce 2 : Performance ROI par utilisateur
// =============================================================================
// Exécution : mongosh paris_sportifs --file 02_user_performance.js
//
// Pour chaque pari résolu (won/lost), on émet (user_id, statistiques).
// Le reducer agrège mises, gains, nombre de paris gagnés.
// Le finalizer calcule le ROI en pourcentage.
//
// Résultat stocké dans la collection `mr_user_performance`.
// =============================================================================

db = db.getSiblingDB('paris_sportifs');

const mapFn = function () {
    if (this.status !== 'won' && this.status !== 'lost') {
        return;
    }
    emit(this.user_id, {
        total_staked: this.amount,
        total_gained: this.status === 'won' ? this.potential_gain : 0,
        bet_count: 1,
        won_count: this.status === 'won' ? 1 : 0,
        lost_count: this.status === 'lost' ? 1 : 0,
    });
};

const reduceFn = function (key, values) {
    const result = {
        total_staked: 0,
        total_gained: 0,
        bet_count: 0,
        won_count: 0,
        lost_count: 0,
    };
    values.forEach(function (v) {
        result.total_staked += v.total_staked;
        result.total_gained += v.total_gained;
        result.bet_count += v.bet_count;
        result.won_count += v.won_count;
        result.lost_count += v.lost_count;
    });
    return result;
};

const finalizeFn = function (key, reduced) {
    reduced.net_result = reduced.total_gained - reduced.total_staked;
    reduced.roi_percent = reduced.total_staked > 0
        ? (reduced.net_result / reduced.total_staked) * 100
        : 0;
    reduced.win_rate_percent = reduced.bet_count > 0
        ? (reduced.won_count / reduced.bet_count) * 100
        : 0;
    return reduced;
};

const res = db.bets.mapReduce(mapFn, reduceFn, {
    out: 'mr_user_performance',
    finalize: finalizeFn,
    query: { status: { $in: ['won', 'lost'] } },
});

print('=== Map Reduce 2 : Performance ROI par utilisateur ===');
printjson(res);
print('\nTop 5 utilisateurs par gain net :');
db.mr_user_performance.find().sort({ 'value.net_result': -1 }).limit(5).forEach(printjson);

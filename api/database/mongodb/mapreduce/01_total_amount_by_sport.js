// =============================================================================
// Map Reduce 1 : Total des mises et nombre de paris regroupés par sport
// =============================================================================
// Exécution : mongosh paris_sportifs --file 01_total_amount_by_sport.js
// Ou dans mongosh : load("api/database/mongodb/mapreduce/01_total_amount_by_sport.js")
//
// Pour chaque pari (collection `bets`), on émet (sport_id, amount + 1 pari).
// Le reducer additionne les mises et le nombre de paris par sport.
// Le finalizer calcule la mise moyenne par pari.
//
// Résultat stocké dans la collection `mr_bets_by_sport`.
// =============================================================================

db = db.getSiblingDB('paris_sportifs');

// Comme les paris référencent un match (et non un sport directement),
// on dénormalise d'abord en construisant une vue temporaire enrichie.
db.bets_enriched.drop();
db.bets.aggregate([
    {
        $lookup: {
            from: 'sport_matches',
            let: { matchIdStr: '$match_id' },
            pipeline: [
                { $match: { $expr: { $eq: [{ $toString: '$_id' }, '$$matchIdStr'] } } },
            ],
            as: 'match',
        },
    },
    { $unwind: '$match' },
    {
        $project: {
            amount: 1,
            potential_gain: 1,
            status: 1,
            sport_id: '$match.sport_id',
        },
    },
    { $out: 'bets_enriched' },
]);

const mapFn = function () {
    emit(this.sport_id, {
        total_amount: this.amount,
        total_potential: this.potential_gain,
        bet_count: 1,
        won_count: this.status === 'won' ? 1 : 0,
    });
};

const reduceFn = function (key, values) {
    const result = {
        total_amount: 0,
        total_potential: 0,
        bet_count: 0,
        won_count: 0,
    };
    values.forEach(function (v) {
        result.total_amount += v.total_amount;
        result.total_potential += v.total_potential;
        result.bet_count += v.bet_count;
        result.won_count += v.won_count;
    });
    return result;
};

const finalizeFn = function (key, reduced) {
    reduced.average_stake = reduced.bet_count > 0
        ? reduced.total_amount / reduced.bet_count
        : 0;
    reduced.win_rate_percent = reduced.bet_count > 0
        ? (reduced.won_count / reduced.bet_count) * 100
        : 0;
    return reduced;
};

const res = db.bets_enriched.mapReduce(mapFn, reduceFn, {
    out: 'mr_bets_by_sport',
    finalize: finalizeFn,
});

print('=== Map Reduce 1 : Total des mises par sport ===');
printjson(res);
print('\nRésultats (collection mr_bets_by_sport) :');
db.mr_bets_by_sport.find().forEach(printjson);

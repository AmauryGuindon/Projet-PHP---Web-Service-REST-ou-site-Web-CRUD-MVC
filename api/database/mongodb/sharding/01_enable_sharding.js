// =============================================================================
// Sharding MongoDB - Activation et configuration des clés de shard
// =============================================================================
// À exécuter sur le mongos (router) une fois le cluster shardé déployé.
// Voir setup_cluster.sh pour la mise en place de l'infrastructure.
//
// Exécution : mongosh --host <mongos-host>:27017 < 01_enable_sharding.js
// =============================================================================

// 1) Activer le sharding sur la base
sh.enableSharding('paris_sportifs');

// 2) Créer les index nécessaires AVANT le sharding (clé de shard requise)
db = db.getSiblingDB('paris_sportifs');

db.bets.createIndex({ user_id: 1, _id: 1 });
db.matches.createIndex({ sport_id: 1, _id: 1 });
db.odds.createIndex({ match_id: 1 });

// 3) Sharder les collections volumineuses
//
// bets : clé composée (user_id, _id) = hashed range
//   -> répartition équilibrée par utilisateur, range sur _id pour les requêtes ciblées
sh.shardCollection('paris_sportifs.bets', { user_id: 'hashed', _id: 1 });

// matches : clé hashed sur sport_id
//   -> répartition par sport (e.g. football, basket sur des shards différents)
sh.shardCollection('paris_sportifs.matches', { sport_id: 'hashed' });

// odds : co-localisées avec leur match via match_id
sh.shardCollection('paris_sportifs.odds', { match_id: 'hashed' });

// 4) Vérifier la configuration
print('=== Statut du cluster shardé ===');
printjson(sh.status());

print('\n=== Répartition des chunks ===');
db.bets.getShardDistribution();
db.matches.getShardDistribution();
db.odds.getShardDistribution();

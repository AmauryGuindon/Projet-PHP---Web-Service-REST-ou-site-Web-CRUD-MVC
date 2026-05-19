# Sharding MongoDB

Configuration et démonstration du sharding horizontal pour BetZone.

## Pourquoi sharder ?

À l'échelle d'un site de paris sportifs réel, la collection `bets` croît
linéairement avec le nombre d'utilisateurs × le nombre de matchs. Au-delà de
quelques To, un seul nœud ne suffit plus en stockage ni en IOPS. Le sharding
permet :

- **Scalabilité horizontale** : ajouter un shard = ajouter de la capacité.
- **Parallélisme** : agrégations et Map Reduce exécutés en parallèle.
- **Isolation par sport / utilisateur** selon la clé.

## Choix des clés de shard

| Collection | Clé de shard            | Type      | Justification                                          |
|------------|-------------------------|-----------|--------------------------------------------------------|
| `bets`     | `{ user_id, _id }`      | hashed+range | Distribution équilibrée par utilisateur, scans ciblés possibles |
| `matches`  | `{ sport_id }`          | hashed    | Sports populaires (foot) ont assez de volume pour bénéficier de plusieurs chunks |
| `odds`     | `{ match_id }`          | hashed    | Co-locality avec les matchs lors des lookups          |

Collections **non shardées** (taille faible et lectures globales) : `sports`,
`teams`. Elles restent dans le shard primaire et bénéficient d'un balancer
plus simple.

## Topologie de démonstration

3 shards, 1 config server, 1 mongos router (cf. `setup_cluster.sh`).

```
                ┌──────────────┐
   Client API ──┤ mongos:27017 │
                └──────┬───────┘
                       │
        ┌──────────────┼──────────────────────────┐
        │              │                          │
  shard1:27018   shard2:27020              shard3:27021
        │              │                          │
        └──────────────┴──────────────────────────┘
                       │
                config:27019
```

## Scripts

- `setup_cluster.sh` — démarre l'infrastructure (Linux/macOS, Git Bash sur Windows)
- `01_enable_sharding.js` — active le sharding et déclare les clés

## Exécution

```bash
# 1. Lancer le cluster (laisse tourner)
bash api/database/mongodb/sharding/setup_cluster.sh

# 2. Activer le sharding sur la base + collections
mongosh --port 27017 < api/database/mongodb/sharding/01_enable_sharding.js

# 3. Pointer Laravel sur mongos (modifier .env)
# MONGO_DB_PORT=27017
# Puis : php artisan migrate:fresh --seed

# 4. Vérifier la répartition
mongosh --port 27017 --eval "sh.status()"
mongosh --port 27017 --eval "use paris_sportifs; db.bets.getShardDistribution()"
```

## Limites de la démo

Cette config tourne en local avec un seul membre par replica set : **aucune
tolérance de panne**. En production il faut un minimum de 3 membres par
shard (Primary + 2 Secondary) pour le quorum. Cf. dossier `../replica_set/`.

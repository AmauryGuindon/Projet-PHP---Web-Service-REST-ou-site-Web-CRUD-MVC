#!/usr/bin/env bash
# =============================================================================
# Démonstration : cluster MongoDB shardé en local (3 shards + config + mongos)
# =============================================================================
# Ce script lance un cluster shardé minimal sur la machine locale en utilisant
# des ports distincts. Chaque "shard" est un mongod simple ; en production on
# utiliserait un replica set par shard.
#
# Topologie :
#   - Config server  : port 27019 (replica set "configReplSet", 1 membre)
#   - Shard 1        : port 27018 (replica set "shard1ReplSet", 1 membre)
#   - Shard 2        : port 27020 (replica set "shard2ReplSet", 1 membre)
#   - Shard 3        : port 27021 (replica set "shard3ReplSet", 1 membre)
#   - Mongos router  : port 27017
#
# Usage : bash setup_cluster.sh
# =============================================================================

set -e

BASE_DIR="${BASE_DIR:-./mongo-cluster}"
mkdir -p "$BASE_DIR"/{config,shard1,shard2,shard3,logs}

echo "==> 1. Démarrage du config server (port 27019)"
mongod --configsvr --replSet configReplSet --port 27019 \
    --dbpath "$BASE_DIR/config" --logpath "$BASE_DIR/logs/config.log" --fork --bind_ip localhost

echo "==> 2. Initialisation du replica set du config server"
mongosh --port 27019 --eval '
rs.initiate({
    _id: "configReplSet",
    configsvr: true,
    members: [{ _id: 0, host: "localhost:27019" }]
});'

echo "==> 3. Démarrage des shards (ports 27018, 27020, 27021)"
mongod --shardsvr --replSet shard1ReplSet --port 27018 \
    --dbpath "$BASE_DIR/shard1" --logpath "$BASE_DIR/logs/shard1.log" --fork --bind_ip localhost
mongod --shardsvr --replSet shard2ReplSet --port 27020 \
    --dbpath "$BASE_DIR/shard2" --logpath "$BASE_DIR/logs/shard2.log" --fork --bind_ip localhost
mongod --shardsvr --replSet shard3ReplSet --port 27021 \
    --dbpath "$BASE_DIR/shard3" --logpath "$BASE_DIR/logs/shard3.log" --fork --bind_ip localhost

echo "==> 4. Initialisation des replica sets de chaque shard"
mongosh --port 27018 --eval 'rs.initiate({_id:"shard1ReplSet",members:[{_id:0,host:"localhost:27018"}]});'
mongosh --port 27020 --eval 'rs.initiate({_id:"shard2ReplSet",members:[{_id:0,host:"localhost:27020"}]});'
mongosh --port 27021 --eval 'rs.initiate({_id:"shard3ReplSet",members:[{_id:0,host:"localhost:27021"}]});'

sleep 3

echo "==> 5. Démarrage du mongos router (port 27017)"
mongos --configdb configReplSet/localhost:27019 --port 27017 \
    --logpath "$BASE_DIR/logs/mongos.log" --fork --bind_ip localhost

echo "==> 6. Ajout des shards au cluster"
mongosh --port 27017 --eval '
sh.addShard("shard1ReplSet/localhost:27018");
sh.addShard("shard2ReplSet/localhost:27020");
sh.addShard("shard3ReplSet/localhost:27021");
'

echo ""
echo "Cluster shardé prêt. Connecter l'API sur mongodb://localhost:27017"
echo "Pour configurer les clés de shard : mongosh --port 27017 < 01_enable_sharding.js"

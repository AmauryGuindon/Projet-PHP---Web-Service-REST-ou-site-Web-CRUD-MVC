#!/usr/bin/env bash
# =============================================================================
# Replica Set MongoDB - Démonstration locale (3 membres)
# =============================================================================
# Topologie : 1 Primary + 2 Secondary sur ports 27017 / 27018 / 27019.
# Tolérance de panne : 1 nœud peut tomber, le quorum (2/3) reste.
#
# Usage : bash setup_replica_set.sh
# =============================================================================

set -e

BASE_DIR="${BASE_DIR:-./mongo-rs}"
mkdir -p "$BASE_DIR"/{node1,node2,node3,logs}

echo "==> 1. Démarrage des 3 instances mongod"
mongod --replSet betzoneReplSet --port 27017 \
    --dbpath "$BASE_DIR/node1" --logpath "$BASE_DIR/logs/node1.log" \
    --fork --bind_ip localhost --oplogSize 128

mongod --replSet betzoneReplSet --port 27018 \
    --dbpath "$BASE_DIR/node2" --logpath "$BASE_DIR/logs/node2.log" \
    --fork --bind_ip localhost --oplogSize 128

mongod --replSet betzoneReplSet --port 27019 \
    --dbpath "$BASE_DIR/node3" --logpath "$BASE_DIR/logs/node3.log" \
    --fork --bind_ip localhost --oplogSize 128

sleep 2

echo "==> 2. Initialisation du replica set"
mongosh --port 27017 --eval '
rs.initiate({
    _id: "betzoneReplSet",
    members: [
        { _id: 0, host: "localhost:27017", priority: 2 },
        { _id: 1, host: "localhost:27018", priority: 1 },
        { _id: 2, host: "localhost:27019", priority: 1 }
    ]
});'

echo "==> 3. Attente élection du primary (≈ 10 s)"
sleep 10

echo "==> 4. Statut du replica set"
mongosh --port 27017 --eval 'rs.status()'

echo ""
echo "Replica set prêt."
echo "Chaîne de connexion à utiliser dans .env :"
echo "MONGODB_DSN=mongodb://localhost:27017,localhost:27018,localhost:27019/paris_sportifs?replicaSet=betzoneReplSet"

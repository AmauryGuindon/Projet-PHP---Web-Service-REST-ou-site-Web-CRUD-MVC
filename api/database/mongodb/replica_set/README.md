# Replica Set MongoDB

Configuration et documentation du clustering pour BetZone.

## Pourquoi un replica set ?

- **Haute disponibilité** : si le Primary tombe, élection automatique d'un nouveau Primary parmi les Secondary.
- **Tolérance de panne** : un cluster à 3 membres survit à la perte d'un nœud.
- **Lecture distribuée** : `readPreference=secondary` décharge les lectures non critiques sur les replicas.
- **Sauvegarde non bloquante** : snapshot d'un Secondary sans impact sur le Primary.

## Topologie cible

```
                ┌──────────────┐
                │   Primary    │ ◄── écritures
                │ localhost:27017│
                └───────┬──────┘
                        │ oplog
            ┌───────────┴───────────┐
            ▼                       ▼
    ┌──────────────┐        ┌──────────────┐
    │  Secondary   │        │  Secondary   │
    │ :27018       │        │ :27019       │
    └──────────────┘        └──────────────┘
```

| Membre | Port  | Priorité | Rôle initial |
|--------|-------|----------|--------------|
| node1  | 27017 | 2        | Primary      |
| node2  | 27018 | 1        | Secondary    |
| node3  | 27019 | 1        | Secondary    |

## Fichiers

- `setup_replica_set.sh` — démarre 3 instances locales et initie le replica set
- `mongod-rs.conf` — exemple de fichier de config par membre (production)

## Exécution

```bash
# Démarrage
bash api/database/mongodb/replica_set/setup_replica_set.sh

# Vérification
mongosh --port 27017 --eval "rs.status()"
mongosh --port 27017 --eval "rs.isMaster()"

# Simuler une panne du Primary
mongosh --port 27017 --eval "rs.stepDown(60)"
# -> un Secondary devient Primary
mongosh --port 27018 --eval "rs.status()"
```

## Connexion depuis Laravel

Dans `api/.env` :

```dotenv
MONGODB_DSN=mongodb://localhost:27017,localhost:27018,localhost:27019/paris_sportifs?replicaSet=betzoneReplSet
MONGODB_DATABASE=paris_sportifs
```

Le driver routera automatiquement les écritures vers le Primary élu et
détectera les failovers.

## Read preference

| Préférence         | Usage                                                    |
|--------------------|----------------------------------------------------------|
| `primary`          | Cohérence forte (paiements, mise à jour de solde)        |
| `primaryPreferred` | Défaut Laravel                                           |
| `secondary`        | Stats, dashboards, exports (tolère un léger lag)         |
| `nearest`          | Lectures géo-distribuées                                 |

## Limites de la démo

- 3 nœuds sur la même machine = un crash physique perd tout.
- Pas de keyFile / TLS : à activer en prod (cf. commentaires de `mongod-rs.conf`).

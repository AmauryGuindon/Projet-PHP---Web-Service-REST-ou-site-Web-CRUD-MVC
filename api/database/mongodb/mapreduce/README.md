# Scripts Map Reduce MongoDB

Deux opérations Map Reduce sur la collection `bets` de BetZone.

## Pourquoi Map Reduce ici ?

Le framework Map Reduce convient à des agrégations distribuables — chaque shard
exécute la phase Map en parallèle, puis le mongos consolide via la phase Reduce.
Sur un cluster shardé (cf. `../sharding/`), ces deux opérations passent à
l'échelle horizontalement sans changement de code.

> Note : depuis MongoDB 5+, `mapReduce` est remplacé en pratique par
> `aggregate` (plus performant). Ici on conserve les deux pour répondre à
> l'exigence pédagogique de l'énoncé. Les agrégations équivalentes sont
> exposées via l'API REST dans `StatsController`.

## 1. `01_total_amount_by_sport.js`

Calcule pour chaque sport :
- nombre total de paris
- montant total misé
- gain potentiel cumulé
- nombre de paris gagnants
- mise moyenne (finalize)
- taux de victoire % (finalize)

Stocke le résultat dans `mr_bets_by_sport`.

## 2. `02_user_performance.js`

Calcule pour chaque utilisateur (sur paris résolus) :
- nombre total de paris
- mises et gains totaux
- résultat net (finalize)
- ROI % (finalize)
- taux de victoire % (finalize)

Stocke le résultat dans `mr_user_performance`.

## Exécution

```bash
# Depuis la racine du projet
mongosh paris_sportifs --file api/database/mongodb/mapreduce/01_total_amount_by_sport.js
mongosh paris_sportifs --file api/database/mongodb/mapreduce/02_user_performance.js

# Consulter les résultats
mongosh paris_sportifs --eval "db.mr_bets_by_sport.find().pretty()"
mongosh paris_sportifs --eval "db.mr_user_performance.find().sort({'value.net_result': -1}).pretty()"
```

## Pré-requis

Avoir exécuté `php artisan migrate:fresh --seed` côté API pour disposer de
~50 documents `bets` répartis sur plusieurs sports et utilisateurs.

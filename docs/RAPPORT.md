# Rapport de projet — BetZone

**Formation** : UC D43 — Projet PHP : Web Service REST
**Auteur** : Amaury Guindon
**Période** : Février – Avril 2026
**Référente** : Morgane Flamant

---

## 1. Introduction

### Contexte métier

Les plateformes de paris sportifs en ligne (Betclic, Winamax, Unibet) manipulent
des volumes importants : milliers de matchs simultanés, cotes mises à jour en
temps réel, millions de paris journaliers. Ce contexte impose un backend
robuste, scalable, capable d'agréger rapidement des statistiques sur de gros
volumes.

### Problématique

Comment concevoir un web service REST modulaire, sécurisé, et capable d'exposer
des statistiques agrégées sur un volume de données amené à croître ?

### Objectifs

- Implémenter une API REST complète (CRUD, auth, rôles, pagination).
- Stocker les données métier dans MongoDB (NoSQL) pour le côté scalable et
  schéma flexible des cotes.
- Mobiliser les patterns OO (Repository, Strategy, Service) et les capacités
  avancées de MongoDB (aggregation, Map Reduce, sharding).

---

## 2. Analyse des besoins

### Fonctionnels

| Acteur          | Besoin principal                                                  |
|-----------------|-------------------------------------------------------------------|
| Visiteur        | Consulter sports, équipes, matchs et cotes                        |
| Utilisateur     | Placer / consulter ses paris, voir ses stats personnelles         |
| Admin           | CRUD complet, synchroniser cotes externes, settler les matchs     |

### Non fonctionnels

- Auth par token (Sanctum), rôles `admin` / `user`.
- Rate limiting sur les routes publiques et auth.
- Versioning d'API (`/api/v1/...`).
- Documentation OpenAPI exploitable (Swagger UI).
- Couverture de tests ≥ 20 (cible : > 80 %).

---

## 3. Choix techniques

| Décision                     | Choix                       | Raison principale                                 |
|------------------------------|-----------------------------|---------------------------------------------------|
| Framework                    | **Laravel 12**              | Maturité, écosystème (Sanctum, Eloquent, Swagger) |
| Langage                      | PHP 8.3                     | Types stricts, readonly, enums                    |
| BDD principale               | **MongoDB 6+**              | Schéma flexible (cotes variables), aggregation    |
| Driver MongoDB               | `mongodb/laravel-mongodb` v5 | Intégration Eloquent native                       |
| Auth                         | Laravel Sanctum (SQLite)    | Tokens stateless simples, séparation des concerns |
| Doc API                      | `darkaonline/l5-swagger`    | Génération automatique depuis attributs PHP 8     |
| Frontend                     | HTML/CSS/JS vanilla         | Démontrer AJAX/Fetch sans framework               |
| Tests                        | PHPUnit 11                  | Standard Laravel                                  |

### MongoDB vs SQL

Choix d'un hybride :
- **MongoDB** pour le métier (sports, teams, matches, odds, bets) — schéma
  flexible (différentes cotes selon les sports), agrégations rapides, sharding.
- **SQLite** pour l'auth uniquement — Sanctum nécessite des tables relationnelles
  pour les `personal_access_tokens`, et c'est plus simple de garder le défaut.

---

## 4. Architecture détaillée

Voir [docs/ARCHITECTURE.md](ARCHITECTURE.md) pour le détail couche par couche.

### Patterns implémentés

1. **Repository** (`app/Repositories/`) : `BetRepository`, `OddRepository`,
   `SportRepository`. Encapsule l'accès Eloquent, simplifie les controllers.
2. **Strategy** (`app/Strategies/`) : `BetOutcomeStrategy` + 3 implémentations
   pour résoudre l'issue d'un pari selon le type de pronostic.
3. **Service** (`app/Services/`) : `BetSettlementService` orchestre la
   résolution d'un match ; `ExternalOddsService` consomme une API tierce.

### Conventions

- PSR-1, PSR-4, PSR-12 (vérifiables avec `composer require --dev laravel/pint`)
- Namespacing `App\...` calqué sur le dossier `app/`
- Tests : `tests/Unit/` (sans DB) et `tests/Feature/` (avec DB)

---

## 5. Implémentation — fonctionnalités clés

### 5.1 Auth + rôles

```php
Route::middleware(['auth:sanctum', 'role:admin'])->group(function (): void {
    Route::post('matches/{matchItem}/settle', [SportMatchController::class, 'settle']);
    // ...
});
```

Middleware custom `role` qui rejette en 403 si le user n'a pas le rôle requis.

### 5.2 Pagination, filtrage, tri

```php
// BetRepository::forUser
return Bet::query()
    ->where('user_id', $userId)
    ->when(isset($filters['status']), fn($q) => $q->where('status', $filters['status']))
    ->with('match')
    ->orderByDesc('created_at')
    ->paginate(min((int) ($filters['per_page'] ?? 15), 50));
```

### 5.3 Strategy pattern

```php
interface BetOutcomeStrategy {
    public function evaluate(SportMatch $match, string $predictedOutcome): string;
}
```

3 implémentations : `HomeWinStrategy`, `AwayWinStrategy`, `DrawStrategy`.
Le `BetSettlementService` peut être injecté avec n'importe laquelle.

---

## 6. MongoDB

### 6.1 Modélisation

| Collection | Embedded / Referenced | Justification                                              |
|------------|------------------------|------------------------------------------------------------|
| `sports`   | Standalone            | Référence simple, peu de relations                         |
| `teams`    | Referenced (sport_id) | Lecture indépendante des sports nécessaire                 |
| `matches`  | Referenced            | Liens vers sport et 2 équipes ; mise à jour fréquente      |
| `odds`     | Referenced (match_id) | Cotes évolutives, écriture isolée du match                 |
| `bets`     | Referenced            | Croît linéairement ; séparation requise pour le sharding   |

**Pas d'embedded** : à chaque relation modifiable indépendamment, le referenced
évite de réécrire un gros document à chaque update.

### 6.2 Index

| Collection | Index                                  | Usage                                |
|------------|-----------------------------------------|--------------------------------------|
| bets       | `user_id`, `match_id`, `status`        | Listing par user / résolution        |
| odds       | `match_id`                             | Lookup pour le calcul de gain        |
| matches    | `sport_id`, `starts_at`, `status`      | Listing filtré et trié               |
| teams      | `sport_id`                             | Listing par sport                    |

### 6.3 Agrégation et Map Reduce

- **Aggregation pipelines** : `StatsController` expose `/stats/bets-by-sport` et
  `/stats/user-performance` via `$group`, `$lookup`, `$addFields`.
- **Map Reduce** : 2 scripts dans `api/database/mongodb/mapreduce/` répondant
  à l'exigence pédagogique (cf. README dédié).

### 6.4 Sharding et clustering

- Scripts complets dans `api/database/mongodb/sharding/` et `replica_set/`.
- Cluster local de démonstration : 3 shards + 1 config server + mongos.
- Replica set 3 membres avec failover testable via `rs.stepDown()`.

---

## 7. Sécurité

| Risque                | Mitigation                                                       |
|-----------------------|------------------------------------------------------------------|
| Brute force login     | `throttle:auth` (5 req/min)                                      |
| Injection NoSQL       | Eloquent / driver MongoDB paramétrise les requêtes               |
| XSS frontend          | Données rendues via `textContent`, jamais `innerHTML` non échappé |
| CSRF                  | API stateless, tokens Bearer (non-cookie) → CSRF non applicable  |
| Mass assignment       | `$fillable` strict sur chaque modèle                             |
| Mot de passe en clair | `Hash::make` + `Hash::check` (bcrypt)                            |
| Token vol             | TTL configurable Sanctum, révocation via `/logout`               |
| CORS                  | Config explicite `config/cors.php` (origines whitelistées)       |

---

## 8. Tests

### Stratégie

- **Unit** : Strategies, Models (casts, fillable), logique pure sans I/O.
- **Feature** : routes API end-to-end avec MongoDB réel.
- **CI** : workflow GitHub Actions exécute la suite sur chaque push.

### État

| Fichier                                | Tests |
|----------------------------------------|-------|
| Feature/AuthApiTest.php                | 5     |
| Feature/BetApiTest.php                 | 4     |
| Feature/OddApiTest.php                 | 5     |
| Feature/StatsApiTest.php               | 3     |
| Feature/ExternalApiTest.php            | 3     |
| Feature/SprintTwoCrudTest.php          | 6     |
| Unit/Strategies/HomeWinStrategyTest    | 6     |
| Unit/Strategies/AwayWinStrategyTest    | 3     |
| Unit/Strategies/DrawStrategyTest       | 3     |
| Unit/Models/BetTest                    | 5     |
| Unit/Services/BetSettlementServiceTest | 3     |
| **Total**                              | **46** |

Exigence (20 tests) largement dépassée.

---

## 9. Difficultés et solutions

| Difficulté                                          | Solution adoptée                                                      |
|-----------------------------------------------------|-----------------------------------------------------------------------|
| Sanctum stocke ses tokens en SQL (pas MongoDB)      | Hybride SQLite (auth) + MongoDB (métier) — un connection par concern  |
| Eloquent relations entre MongoDB et SQL impossibles | Stockage du `user_id` en string, jointures applicatives quand besoin  |
| `with()` MongoDB peu fiable selon driver            | Remplacement par requêtes individuelles (`find()` puis hydratation)   |
| Spinners persistants côté frontend                  | `finally { hideSpinner() }` systématique sur les fetch                |
| Map Reduce sur les paris (sport_id absent)          | Vue dénormalisée intermédiaire `bets_enriched` via `$lookup` + `$out` |

---

## 10. Perspectives

- **WebSockets** (Laravel Reverb) : cotes en push temps réel.
- **Cache Redis** sur les endpoints `sports` / `teams` (lecture massive).
- **Queue Horizon** pour les sync externes asynchrones.
- **Frontend SPA** (Vue ou React) si l'UI grossit.
- **Auth Multi-facteur** : Google Authenticator via `pragmarx/google2fa`.
- **Stripe** pour les recharges de cagnotte virtuelle.

---

## 11. Conclusion

Ce projet m'a permis de :

- Mettre en pratique POO + Laravel + 3 design patterns concrets.
- Modéliser un domaine métier non trivial (relations, états, transitions).
- Découvrir MongoDB en profondeur (agrégation, Map Reduce, sharding, replica set).
- Sécuriser une API REST avec auth, rôles et rate limiting.
- Produire une documentation exploitable (UML, Swagger, README pas-à-pas).

Au-delà de la note, c'est l'occasion d'avoir une base de code que je peux faire
évoluer (WebSocket, frontend SPA, paiement Stripe) au-delà de la formation.

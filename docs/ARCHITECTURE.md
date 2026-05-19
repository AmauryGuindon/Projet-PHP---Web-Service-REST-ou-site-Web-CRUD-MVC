# Guide d'architecture — BetZone

## Vue d'ensemble

BetZone est découpé en deux applications dans un même dépôt :

```
/
├── api/         Backend REST Laravel 12 (PHP 8.3 + MongoDB + SQLite)
├── frontend/    Client web statique (HTML/CSS/JS vanilla, fetch API)
└── docs/        Documentation projet (UML, rapport, ce guide)
```

## Couches de l'API

```
HTTP request
    │
    ▼
┌──────────────────────────────────────────────────────────────┐
│  Routing / Middleware (routes/api.php)                       │
│   - throttle:api / throttle:auth                             │
│   - auth:sanctum                                             │
│   - role:admin                                               │
└──────────────────────────────────────────────────────────────┘
    │
    ▼
┌──────────────────────────────────────────────────────────────┐
│  Controllers (app/Http/Controllers/Api/V1/)                  │
│   - Fins, délèguent à Repositories / Services                │
│   - Validation via FormRequest                               │
│   - Réponses JSON normalisées                                │
└──────────────────────────────────────────────────────────────┘
    │
    ├──────────────┐
    ▼              ▼
┌─────────────┐ ┌──────────────────────────────────────────────┐
│ Repositories│ │ Services                                     │
│ Accès data  │ │ Logique métier multi-modèle                  │
└─────────────┘ └──────────────────┬───────────────────────────┘
    │                              │
    │                              ▼
    │                  ┌─────────────────────────┐
    │                  │ Strategies              │
    │                  │ Décisions interchangeables│
    │                  └─────────────────────────┘
    ▼
┌──────────────────────────────────────────────────────────────┐
│  Models (Eloquent + MongoDB-Laravel)                         │
│   - Bet, Sport, Team, SportMatch, Odd (mongodb)              │
│   - User (sqlite via Sanctum)                                │
└──────────────────────────────────────────────────────────────┘
    │
    ▼
┌──────────────────────────────────────────────────────────────┐
│  Persistance                                                 │
│   - MongoDB (collections métier)                             │
│   - SQLite (users + personal_access_tokens)                  │
└──────────────────────────────────────────────────────────────┘
```

## Design patterns utilisés

### 1. Repository

**Pourquoi** : isoler les requêtes Eloquent du controller, faciliter le test et
la réutilisation.

```php
final class BetRepository {
    public function forUser(mixed $userId, array $filters = []): LengthAwarePaginator;
    public function create(array $data): Bet;
    public function update(Bet $bet, array $data): Bet;
}
```

### 2. Strategy

**Pourquoi** : la résolution d'un pari dépend du type de pronostic. Plutôt
qu'un gros `switch`, chaque type a sa classe.

```php
interface BetOutcomeStrategy {
    public function evaluate(SportMatch $match, string $predictedOutcome): string;
}
```

Avantage : ajouter un type de pari (paris combinés, handicap…) = nouvelle
classe sans toucher au reste.

### 3. Service

**Pourquoi** : orchestration multi-modèle qui ne tient pas dans un controller
ni un repository.

```php
final class BetSettlementService {
    public function settleMatch(SportMatch $match): int;
}

final class ExternalOddsService {
    public function fetchSports(): array;
}
```

## Flux clé — placement d'un pari

1. `POST /api/v1/bets` → middleware `auth:sanctum` extrait le user.
2. `BetController::store` valide le payload via `StoreBetRequest`.
3. Controller appelle `Odd::where('match_id', $matchId)->first()` pour
   récupérer les cotes en cours.
4. Calcule `potential_gain = amount × odds_value`.
5. `BetRepository::create([...])` persiste avec `status = pending`.
6. Retour 201 + représentation du pari.

## Flux clé — résolution d'un match

1. `POST /api/v1/matches/{id}/settle` (admin only).
2. Le match passe en `finished` avec scores finaux.
3. `BetSettlementService::settleMatch($match)` itère sur les paris `pending`.
4. Chaque pari est passé à une `BetOutcomeStrategy` qui renvoie `won` ou `lost`.
5. Update individuel des paris.

## Persistance — pourquoi cet hybride MongoDB + SQLite

- **Sanctum** suppose une table `personal_access_tokens` relationnelle.
  Plutôt que de la porter en MongoDB (et de réécrire le `TokenGuard`), on
  garde le défaut SQLite — c'est 1 fichier, zéro maintenance.
- **Le métier** profite réellement de MongoDB : schéma flexible des cotes,
  agrégations rapides, futur sharding.

Trade-off : pas de jointure SQL entre `users` et `bets`. On stocke le
`user_id` en string et on hydrate manuellement quand besoin. Acceptable parce
que le flux d'auth ne touche jamais les paris en relation directe.

## Sécurité

- Auth par token Bearer (Sanctum), pas de session ni cookie → CSRF non applicable.
- Mot de passe haché bcrypt (`Hash::make`).
- Rate limit : `throttle:auth` sur login/register, `throttle:api` partout.
- Validation systématique via `FormRequest` côté entrée.
- Mass assignment protégé par `$fillable`.
- CORS : whitelist d'origines configurée dans `config/cors.php`.

## Tests

- `tests/Unit/` : logique pure, pas de DB, ultra rapide.
- `tests/Feature/` : routes API end-to-end, MongoDB réel, `cleanMongoCollections()`
  dans `setUp`.
- Exécution : `php artisan test` ou `composer test`.

## Évolutions envisagées

| Évolution                  | Impact code                                                 |
|----------------------------|-------------------------------------------------------------|
| Paris combinés             | Nouvelle `BetOutcomeStrategy` + tableau `match_ids[]` dans  |
|                            | le modèle Bet                                                |
| Cotes en temps réel        | Laravel Reverb + nouvel endpoint WebSocket                  |
| Sharding production        | Migration vers `mongodb+srv://` Atlas, déjà préparé         |
| Cache lecture              | Redis + middleware `cache.headers` sur sports/teams         |

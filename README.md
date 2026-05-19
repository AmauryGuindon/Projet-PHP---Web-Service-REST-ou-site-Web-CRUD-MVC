# BetZone — Paris Sportifs

Projet PHP Master 1 S6 — Web Service REST Laravel 12 + MongoDB + Frontend vanilla JS.

---

## Prérequis

- **PHP 8.3+** accessible dans le terminal (`php -v`)
- **Composer** installé
- **MongoDB 6+** installé et le dossier de données créé (`C:\data\db` sur Windows)
- **Node.js** installé (pour `npx serve`)

---

## Démarrage (3 terminaux)

### Terminal 1 — MongoDB
```bash
mongod --dbpath "C:\data\db"
```
> Laisser tourner. Ne pas fermer.

### Terminal 2 — API Laravel
```bash
cd api
php artisan serve
```
> API disponible sur http://localhost:8000

### Terminal 3 — Frontend
```bash
cd frontend
npx serve .
```
> Frontend disponible sur http://localhost:3000

---

## Première installation (ou réinitialisation des données)

```bash
cd api
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
```

> Crée toutes les collections MongoDB, insère 5 sports, ~40 équipes, 25 matchs, cotes et paris de démonstration.

> Après un `migrate:fresh --seed`, **se déconnecter et reconnecter** dans le navigateur (le token Sanctum est invalidé).

---

## Comptes de test

| Rôle  | Email                      | Mot de passe |
|-------|---------------------------|--------------|
| Admin | admin@paris-sportifs.test | password     |
| User  | (généré par factory)      | password     |

---

## Stack technique

| Couche     | Technologie                         |
|------------|-------------------------------------|
| API        | Laravel 12, PHP 8.3                 |
| Auth       | Laravel Sanctum (tokens)            |
| Base SQL   | SQLite (users, tokens)              |
| Base NoSQL | MongoDB 6+ (sports, matchs, paris)  |
| Patterns   | Repository, Strategy, Factory       |
| Stats      | Aggregation pipeline MongoDB        |
| Docs API   | Swagger (`/api/documentation`)      |
| Frontend   | HTML/CSS/JS vanilla, thème sombre   |

---

## Routes API principales

```
POST   /api/v1/auth/register
POST   /api/v1/auth/login
POST   /api/v1/auth/logout

GET    /api/v1/sports
GET    /api/v1/teams
GET    /api/v1/matches
GET    /api/v1/odds
GET    /api/v1/bets           (auth)
POST   /api/v1/bets           (auth)

POST   /api/v1/matches/{id}/settle  (admin)

GET    /api/v1/stats/bets-by-sport
GET    /api/v1/stats/user-performance

GET    /api/v1/external/sync-odds
```

Documentation Swagger : http://localhost:8000/api/documentation

---

## Structure du projet

```
api/                          Laravel 12 (backend REST)
  database/mongodb/
    mapreduce/                Scripts Map Reduce MongoDB (×2)
    sharding/                 Configuration sharding + script cluster
    replica_set/              Configuration replica set + script HA
frontend/                     HTML/CSS/JS (client web)
docs/
  RAPPORT.md                  Rapport de projet
  ARCHITECTURE.md             Guide d'architecture
  uml/                        Diagrammes UML (Mermaid)
.github/workflows/ci.yml      Pipeline CI (tests + lint Pint)
```

## Documentation

- [Rapport de projet](docs/RAPPORT.md)
- [Guide d'architecture](docs/ARCHITECTURE.md)
- [Diagrammes UML](docs/uml/README.md)
- [Scripts MongoDB Map Reduce](api/database/mongodb/mapreduce/README.md)
- [Sharding](api/database/mongodb/sharding/README.md)
- [Replica Set](api/database/mongodb/replica_set/README.md)

## Tests

```bash
cd api
php artisan test                 # exécute Feature + Unit
php artisan test --coverage      # avec couverture (Xdebug requis)
./vendor/bin/pint --test         # lint PSR-12
```

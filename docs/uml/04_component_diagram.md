# Diagramme de composants — BetZone

Vue d'architecture haut niveau du système.

```mermaid
flowchart TB
    subgraph Client[Client]
        Browser[Navigateur web]
        FE[Frontend statique<br/>HTML/CSS/JS vanilla<br/>fetch / AJAX]
    end

    subgraph Server[Serveur d'application]
        subgraph Laravel[Laravel 12 - PHP 8.3]
            Router[Routing<br/>routes/api.php<br/>middleware: throttle, auth:sanctum, role]
            Controllers[Controllers<br/>AuthController<br/>SportController<br/>BetController<br/>StatsController<br/>...]
            Services[Services<br/>BetSettlementService<br/>ExternalOddsService]
            Repos[Repositories<br/>BetRepository<br/>OddRepository<br/>SportRepository]
            Strategies[Strategies<br/>HomeWinStrategy<br/>AwayWinStrategy<br/>DrawStrategy]
            Models[Eloquent Models]
            Sanctum[Laravel Sanctum<br/>Auth tokens]
            Swagger[L5-Swagger<br/>/api/documentation]
        end
    end

    subgraph Persistence[Persistance]
        SQLite[(SQLite<br/>users<br/>personal_access_tokens)]
        Mongo[(MongoDB 6+<br/>sports, teams,<br/>matches, odds, bets)]
    end

    subgraph External[Tiers]
        ExtAPI[API Cotes externes<br/>HTTP / JSON]
    end

    Browser --> FE
    FE -->|fetch JSON| Router
    Router --> Controllers
    Controllers --> Services
    Controllers --> Repos
    Services --> Strategies
    Services --> Models
    Repos --> Models
    Models -->|connection: mongodb| Mongo
    Sanctum --> SQLite
    Controllers --> Sanctum
    Services --> ExtAPI
    Swagger -.->|annotations OA\\Attributes| Controllers
```

## Découpage en couches

| Couche          | Responsabilité                                                         |
|-----------------|------------------------------------------------------------------------|
| Présentation    | Frontend statique (`frontend/`), pages HTML + JS fetch                 |
| Routing / HTTP  | `routes/api.php`, FormRequests, middlewares                            |
| Application     | Controllers fins, orchestration                                        |
| Domaine         | Services, Strategies, règles métier                                    |
| Infrastructure  | Repositories, Eloquent Models, connecteur MongoDB                      |
| Données         | MongoDB (NoSQL document), SQLite (auth uniquement)                     |

## Communications externes

- **Frontend → API** : appels `fetch` JSON, token Bearer dans le header
- **API → MongoDB** : driver `mongodb/laravel-mongodb`
- **API → API externe** : `Illuminate\Support\Facades\Http` (Guzzle)
- **Auth** : tokens Sanctum stockés en SQLite, vérifiés à chaque requête

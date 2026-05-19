# Diagrammes de séquences — BetZone

Trois scénarios principaux illustrant les interactions client / API / MongoDB.

## Scénario 1 — Authentification (login)

```mermaid
sequenceDiagram
    actor User as Utilisateur
    participant FE as Frontend (login.html)
    participant API as Laravel /api/v1/auth
    participant SQ as SQLite (users + tokens)

    User->>FE: Saisit email + mot de passe
    FE->>API: POST /auth/login (email, password)
    API->>SQ: SELECT user WHERE email=...
    SQ-->>API: User row
    API->>API: Hash::check(password)
    alt mot de passe valide
        API->>SQ: INSERT personal_access_token
        SQ-->>API: token id
        API-->>FE: 200 { token, user }
        FE->>FE: localStorage.setItem('token')
        FE-->>User: Redirige vers index.html
    else mot de passe invalide
        API-->>FE: 401 { message }
        FE-->>User: Affiche erreur
    end
```

## Scénario 2 — Placer un pari

```mermaid
sequenceDiagram
    actor User as Utilisateur
    participant FE as Frontend (matches.html)
    participant API as BetController
    participant Repo as BetRepository
    participant Mongo as MongoDB (bets)

    User->>FE: Clique "Parier" sur un match
    FE->>API: POST /bets (Bearer token)<br/>{ match_id, amount, predicted_outcome }
    API->>API: Validation (FormRequest)
    API->>Mongo: Odd::where('match_id', ...)->first()
    Mongo-->>API: Cotes en cours
    API->>API: Calcul potential_gain = amount × odds
    API->>Repo: create([...])
    Repo->>Mongo: INSERT bet (status=pending)
    Mongo-->>Repo: Bet créé
    Repo-->>API: Bet
    API-->>FE: 201 { bet }
    FE-->>User: Toast "Pari placé"
```

## Scénario 3 — Résolution d'un match (admin)

```mermaid
sequenceDiagram
    actor Admin
    participant FE as Frontend (admin.html)
    participant API as SportMatchController
    participant SVC as BetSettlementService
    participant Strat as HomeWinStrategy
    participant Mongo as MongoDB (matches, bets)

    Admin->>FE: Saisit score final + clique "Settle"
    FE->>API: POST /matches/{id}/settle (Bearer admin token)
    API->>Mongo: Update match (status=finished, scores)
    API->>SVC: settleMatch(match)
    SVC->>Mongo: SELECT bets WHERE match_id=... AND status=pending
    Mongo-->>SVC: Collection<Bet>
    loop pour chaque pari
        SVC->>Strat: evaluate(match, bet.predicted_outcome)
        Strat-->>SVC: 'won' | 'lost'
        SVC->>Mongo: UPDATE bet SET status=...
    end
    SVC-->>API: nb paris résolus
    API-->>FE: 200 { settled: N }
    FE-->>Admin: Confirmation
```

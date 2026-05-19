# Diagramme de classes — BetZone

```mermaid
classDiagram
    class User {
        +ObjectId _id
        +string name
        +string email
        +string password
        +Role role
        +tokens()
        +bets()
    }

    class Sport {
        +ObjectId _id
        +string name
        +string slug
        +matches()
    }

    class Team {
        +ObjectId _id
        +string name
        +string sport_id
        +sport()
    }

    class SportMatch {
        +ObjectId _id
        +string sport_id
        +string home_team_id
        +string away_team_id
        +DateTime starts_at
        +MatchStatus status
        +int home_score
        +int away_score
        +sport()
        +homeTeam()
        +awayTeam()
        +odds()
    }

    class Odd {
        +ObjectId _id
        +string match_id
        +float home_win
        +float draw
        +float away_win
        +match()
    }

    class Bet {
        +ObjectId _id
        +string user_id
        +string match_id
        +float amount
        +string predicted_outcome
        +float odds_value
        +float potential_gain
        +BetStatus status
        +user()
        +match()
    }

    class BetOutcomeStrategy {
        <<interface>>
        +evaluate(SportMatch, string) string
    }
    class HomeWinStrategy
    class AwayWinStrategy
    class DrawStrategy

    class BetSettlementService {
        -BetOutcomeStrategy strategy
        +settleMatch(SportMatch) int
    }

    class ExternalOddsService {
        +fetchSports() array
        +fetchOddsFor(string) array
    }

    class BetRepository {
        +forUser(mixed, array) LengthAwarePaginator
        +create(array) Bet
        +update(Bet, array) Bet
    }

    class SportRepository
    class OddRepository

    BetOutcomeStrategy <|.. HomeWinStrategy
    BetOutcomeStrategy <|.. AwayWinStrategy
    BetOutcomeStrategy <|.. DrawStrategy
    BetSettlementService --> BetOutcomeStrategy

    User "1" -- "*" Bet
    Bet "*" --> "1" SportMatch
    SportMatch "*" --> "1" Sport
    Team "*" --> "1" Sport
    SportMatch "1" --> "1" Team : home
    SportMatch "1" --> "1" Team : away
    Odd "*" --> "1" SportMatch
```

## Notes

- **Persistance** : User en SQLite (Laravel Sanctum) ; Sport, Team, SportMatch,
  Odd, Bet en MongoDB. Les références inter-collections utilisent l'`_id`
  MongoDB sous forme de string.
- **Pattern Strategy** : 3 implémentations interchangeables de
  `BetOutcomeStrategy` pour résoudre l'issue d'un pari.
- **Pattern Repository** : encapsule l'accès à la persistance pour Bet, Odd,
  Sport.
- **Pattern Service** : `BetSettlementService` orchestre la résolution d'un
  match ; `ExternalOddsService` consomme une API tierce via Guzzle/HTTP.

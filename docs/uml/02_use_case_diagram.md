# Diagramme de cas d'utilisation — BetZone

```mermaid
flowchart LR
    Guest((Invité))
    User((Utilisateur))
    Admin((Admin))
    ExtAPI((API Externe<br/>Cotes))

    subgraph BetZone
        UC1[S'inscrire]
        UC2[Se connecter]
        UC3[Consulter sports / équipes / matchs]
        UC4[Consulter cotes en cours]
        UC5[Placer un pari]
        UC6[Consulter ses paris]
        UC7[Consulter stats personnelles]
        UC8[Créer/modifier sport / équipe / match]
        UC9[Mettre à jour cotes]
        UC10[Résoudre un match - settle]
        UC11[Synchroniser cotes externes]
        UC12[Consulter stats globales]
    end

    Guest --> UC1
    Guest --> UC2
    Guest --> UC3
    Guest --> UC4

    User --> UC3
    User --> UC4
    User --> UC5
    User --> UC6
    User --> UC7

    Admin --> UC8
    Admin --> UC9
    Admin --> UC10
    Admin --> UC11
    Admin --> UC12
    Admin -.->|hérite| User

    UC11 --> ExtAPI
```

## Acteurs

| Acteur       | Rôle                                                                   |
|--------------|------------------------------------------------------------------------|
| **Invité**   | Non authentifié, consultation publique uniquement                      |
| **Utilisateur** | Compte standard, peut parier et consulter ses statistiques          |
| **Admin**    | Tous les droits + administration des données + résolution des matchs   |
| **API externe** | Système externe fournissant cotes / résultats (sync planifié)        |

## Règles métier clés

- Un pari ne peut être placé que sur un match en statut `scheduled` (à venir).
- Une fois un match `settled`, ses paris passent en `won` ou `lost` selon la
  stratégie appliquée — plus de modification possible.
- Seul un admin peut déclencher le settlement d'un match.

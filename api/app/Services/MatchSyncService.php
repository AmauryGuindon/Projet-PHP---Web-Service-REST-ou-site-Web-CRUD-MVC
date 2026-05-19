<?php

namespace App\Services;

use App\Models\Odd;
use App\Models\Sport;
use App\Models\SportMatch;
use App\Models\Team;
use Illuminate\Support\Carbon;

class MatchSyncService
{
    public function __construct(
        private readonly ExternalOddsService $externalOdds,
        private readonly BetSettlementService $settlement,
    ) {}

    /**
     * Synchronise toutes les compétitions mappées dans ExternalOddsService::SPORT_KEYS.
     * Récupère les matchs à venir + cotes, puis met à jour les scores des matchs récents.
     *
     * @return array{leagues_synced:int, matches_created:int, matches_updated:int, odds_created:int, bets_settled:int}
     */
    public function syncAll(): array
    {
        $stats = [
            'leagues_synced'   => 0,
            'matches_created'  => 0,
            'matches_updated'  => 0,
            'odds_created'     => 0,
            'bets_settled'     => 0,
        ];

        foreach (ExternalOddsService::SPORT_KEYS as $sportKey => $slug) {
            $sport = Sport::query()->where('slug', $slug)->first();
            if (!$sport) continue;

            $stats['leagues_synced']++;

            $events = $this->externalOdds->fetchOddsForSport($sportKey);
            foreach ($events as $rawEvent) {
                $this->upsertMatch($sport, ExternalOddsService::normalizeEvent($rawEvent), $stats);
            }

            $scores = $this->externalOdds->fetchScoresForSport($sportKey);
            foreach ($scores as $rawScore) {
                $this->updateScoresFor(ExternalOddsService::normalizeEvent($rawScore), $stats);
            }
        }

        return $stats;
    }

    private function upsertMatch(Sport $sport, array $event, array &$stats): void
    {
        if (!$event['external_id'] || !$event['home_team_name'] || !$event['away_team_name']) {
            return;
        }

        $homeTeam = $this->upsertTeam($sport, $event['home_team_name']);
        $awayTeam = $this->upsertTeam($sport, $event['away_team_name']);

        $existing = SportMatch::query()->where('external_id', $event['external_id'])->first();

        $startsAt = $event['starts_at'] ? Carbon::parse($event['starts_at']) : null;
        $isCompleted = $event['completed'] || ($startsAt && $startsAt->isPast() && $event['home_score'] !== null);

        $payload = [
            'external_id'  => $event['external_id'],
            'sport_id'     => $sport->id,
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
            'starts_at'    => $startsAt,
            'status'       => $isCompleted ? 'finished' : 'scheduled',
            'home_score'   => $event['home_score'],
            'away_score'   => $event['away_score'],
        ];

        if ($existing) {
            $existing->update($payload);
            $stats['matches_updated']++;
        } else {
            $match = SportMatch::query()->create($payload);
            $stats['matches_created']++;

            $this->upsertOddsFor($match, $event['odds'], $stats);
        }
    }

    private function upsertOddsFor(SportMatch $match, array $odds, array &$stats): void
    {
        if (!$odds['home_win'] || !$odds['away_win']) {
            return;
        }

        Odd::query()->create([
            'match_id'  => (string) $match->id,
            'home_win'  => $odds['home_win'],
            'draw'      => $odds['draw'] ?? 3.0,
            'away_win'  => $odds['away_win'],
            'bookmaker' => $odds['bookmaker'] ?? 'The Odds API',
            'source'    => 'external',
        ]);

        $stats['odds_created']++;
    }

    private function updateScoresFor(array $event, array &$stats): void
    {
        if (!$event['external_id']) return;

        $match = SportMatch::query()->where('external_id', $event['external_id'])->first();
        if (!$match) return;

        $wasScheduled = $match->status === 'scheduled';

        $match->update([
            'home_score' => $event['home_score'],
            'away_score' => $event['away_score'],
            'status'     => $event['completed'] ? 'finished' : $match->status,
        ]);

        if ($wasScheduled && $event['completed'] && $event['home_score'] !== null) {
            $stats['bets_settled'] += $this->settlement->settleMatch($match->fresh());
        }
    }

    private function upsertTeam(Sport $sport, string $name): Team
    {
        $team = Team::query()
            ->where('sport_id', $sport->id)
            ->where('name', $name)
            ->first();

        if ($team) return $team;

        return Team::query()->create([
            'sport_id'   => $sport->id,
            'name'       => $name,
            'short_name' => mb_substr($name, 0, 3),
        ]);
    }
}

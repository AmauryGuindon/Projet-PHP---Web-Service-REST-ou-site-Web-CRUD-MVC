<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Client The Odds API (https://the-odds-api.com).
 * Fournit la liste des sports, le calendrier des rencontres et les cotes
 * de bookmakers réels.
 *
 * Toutes les méthodes retournent un fallback statique en cas d'échec réseau
 * ou de réponse invalide, afin que l'API BetZone reste fonctionnelle même
 * sans connectivité.
 */
class ExternalOddsService
{
    private string $baseUrl = 'https://api.the-odds-api.com/v4';
    private string $apiKey;

    /**
     * Mapping : sport_key The Odds API → slug interne BetZone.
     * Étendre la liste pour synchroniser plus de compétitions.
     */
    public const SPORT_KEYS = [
        'soccer_france_ligue_one' => 'football',
        'soccer_epl'              => 'football',
        'soccer_spain_la_liga'    => 'football',
        'soccer_italy_serie_a'    => 'football',
        'soccer_germany_bundesliga' => 'football',
        'soccer_uefa_champs_league' => 'football',
        'basketball_nba'          => 'basketball',
        'tennis_atp_french_open'  => 'tennis',
    ];

    public function __construct()
    {
        $this->apiKey = config('services.odds_api.key', '');
    }

    /**
     * Récupère la liste des sports disponibles côté The Odds API.
     * Utilise Http facade (CURL sous le capot).
     */
    public function fetchSports(): array
    {
        try {
            $response = Http::timeout(10)->get("{$this->baseUrl}/sports", [
                'apiKey' => $this->apiKey,
            ]);

            if ($response->failed()) {
                Log::warning('ExternalOddsService: appel API échoué', ['status' => $response->status()]);
                return $this->getFallbackSports();
            }

            return $response->json() ?? $this->getFallbackSports();
        } catch (\Exception $e) {
            Log::error('ExternalOddsService: exception', ['message' => $e->getMessage()]);
            return $this->getFallbackSports();
        }
    }

    /**
     * Récupère les rencontres à venir + cotes pour une compétition.
     * Returns un tableau d'events normalisés.
     */
    public function fetchOddsForSport(string $sportKey, string $regions = 'eu', string $markets = 'h2h'): array
    {
        try {
            $response = Http::timeout(15)->get("{$this->baseUrl}/sports/{$sportKey}/odds", [
                'apiKey'      => $this->apiKey,
                'regions'     => $regions,
                'markets'     => $markets,
                'oddsFormat'  => 'decimal',
                'dateFormat'  => 'iso',
            ]);

            if ($response->failed()) {
                Log::warning('ExternalOddsService::fetchOddsForSport échoué', [
                    'status'    => $response->status(),
                    'sport_key' => $sportKey,
                    'body'      => $response->body(),
                ]);
                return [];
            }

            return $response->json() ?? [];
        } catch (\Exception $e) {
            Log::error('ExternalOddsService::fetchOddsForSport', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Récupère les scores des matchs récents (terminés ou en direct).
     */
    public function fetchScoresForSport(string $sportKey, int $daysFrom = 3): array
    {
        try {
            $response = Http::timeout(15)->get("{$this->baseUrl}/sports/{$sportKey}/scores", [
                'apiKey'    => $this->apiKey,
                'daysFrom'  => $daysFrom,
                'dateFormat' => 'iso',
            ]);

            if ($response->failed()) {
                return [];
            }

            return $response->json() ?? [];
        } catch (\Exception $e) {
            Log::error('ExternalOddsService::fetchScoresForSport', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Normalise un event The Odds API en payload exploitable par MatchSyncService.
     * Sélectionne le premier bookmaker disponible pour les cotes h2h.
     */
    public static function normalizeEvent(array $raw): array
    {
        $homeTeam = $raw['home_team'] ?? null;
        $awayTeam = $raw['away_team'] ?? null;

        $odds = ['home_win' => null, 'draw' => null, 'away_win' => null, 'bookmaker' => null];

        if (!empty($raw['bookmakers'][0]['markets'])) {
            $h2h = collect($raw['bookmakers'][0]['markets'])->firstWhere('key', 'h2h');
            if ($h2h && !empty($h2h['outcomes'])) {
                foreach ($h2h['outcomes'] as $outcome) {
                    $name = $outcome['name'] ?? '';
                    $price = (float) ($outcome['price'] ?? 0);
                    if ($name === $homeTeam) {
                        $odds['home_win'] = $price;
                    } elseif ($name === $awayTeam) {
                        $odds['away_win'] = $price;
                    } elseif (strcasecmp($name, 'Draw') === 0) {
                        $odds['draw'] = $price;
                    }
                }
                $odds['bookmaker'] = $raw['bookmakers'][0]['title'] ?? null;
            }
        }

        return [
            'external_id'    => (string) ($raw['id'] ?? ''),
            'sport_key'      => $raw['sport_key'] ?? null,
            'home_team_name' => $homeTeam,
            'away_team_name' => $awayTeam,
            'starts_at'      => $raw['commence_time'] ?? null,
            'completed'      => (bool) ($raw['completed'] ?? false),
            'home_score'     => self::extractScore($raw, $homeTeam),
            'away_score'     => self::extractScore($raw, $awayTeam),
            'odds'           => $odds,
        ];
    }

    private static function extractScore(array $raw, ?string $teamName): ?int
    {
        if (!$teamName || empty($raw['scores'])) {
            return null;
        }
        foreach ($raw['scores'] as $score) {
            if (($score['name'] ?? null) === $teamName && isset($score['score'])) {
                return (int) $score['score'];
            }
        }
        return null;
    }

    /**
     * Factory method (kept for tests) — construit un tableau de cotes normalisées.
     */
    public static function createOddFromExternal(array $rawOdd): array
    {
        return [
            'home_win'  => $rawOdd['home'] ?? 2.0,
            'draw'      => $rawOdd['draw'] ?? 3.0,
            'away_win'  => $rawOdd['away'] ?? 2.0,
            'bookmaker' => $rawOdd['bookmaker'] ?? 'External',
            'source'    => 'external',
        ];
    }

    private function getFallbackSports(): array
    {
        return [
            ['key' => 'soccer_france_ligue_one', 'title' => 'Ligue 1',    'active' => true],
            ['key' => 'basketball_nba',          'title' => 'NBA',         'active' => true],
            ['key' => 'soccer_epl',              'title' => 'Premier League', 'active' => true],
        ];
    }
}

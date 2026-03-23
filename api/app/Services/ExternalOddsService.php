<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExternalOddsService
{
    private string $baseUrl = 'https://api.the-odds-api.com/v4';
    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.odds_api.key', '');
    }

    /**
     * Récupère la liste des sports depuis l'API externe.
     * Utilise Http facade (CURL sous le capot).
     */
    public function fetchSports(): array
    {
        try {
            $response = Http::timeout(10)
                ->get("{$this->baseUrl}/sports", [
                    'apiKey' => $this->apiKey,
                ]);

            if ($response->failed()) {
                Log::warning('ExternalOddsService: appel API échoué', [
                    'status' => $response->status(),
                ]);
                return $this->getFallbackSports();
            }

            return $response->json() ?? $this->getFallbackSports();
        } catch (\Exception $e) {
            Log::error('ExternalOddsService: exception', ['message' => $e->getMessage()]);
            return $this->getFallbackSports();
        }
    }

    /**
     * Factory method — construit un tableau de cotes normalisées depuis la réponse externe.
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
            ['key' => 'soccer_france_ligue1', 'title' => 'Ligue 1', 'active' => true],
            ['key' => 'basketball_nba', 'title' => 'NBA', 'active' => true],
            ['key' => 'rugby_union', 'title' => 'Rugby Union', 'active' => true],
        ];
    }
}

<?php

namespace Database\Seeders;

use App\Models\Sport;
use App\Models\Team;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    private array $teams = [
        'football' => [
            ['Paris Saint-Germain', 'PSG', 'France'],
            ['Olympique de Marseille', 'OM', 'France'],
            ['Olympique Lyonnais', 'OL', 'France'],
            ['AS Monaco', 'ASM', 'Monaco'],
            ['LOSC Lille', 'LOSC', 'France'],
            ['OGC Nice', 'OGCN', 'France'],
            ['Stade Rennais', 'SRFC', 'France'],
            ['RC Lens', 'RCL', 'France'],
            ['FC Nantes', 'FCN', 'France'],
            ['Stade de Reims', 'SDR', 'France'],
            ['Montpellier HSC', 'MHSC', 'France'],
            ['RC Strasbourg', 'RCSA', 'France'],
        ],
        'basketball' => [
            ['Real Madrid', 'RMA', 'Espagne'],
            ['FC Barcelona', 'FCB', 'Espagne'],
            ['Olympiacos', 'OLY', 'Grèce'],
            ['CSKA Moscou', 'CSK', 'Russie'],
            ['Fenerbahçe', 'FEN', 'Turquie'],
            ['Anadolu Efes', 'EFS', 'Turquie'],
            ['Panathinaikos', 'PAN', 'Grèce'],
            ['Maccabi Tel Aviv', 'MAC', 'Israël'],
        ],
        'rugby' => [
            ['Stade Toulousain', 'STL', 'France'],
            ['Racing 92', 'R92', 'France'],
            ['La Rochelle', 'LRO', 'France'],
            ['Stade Rochelais', 'SR', 'France'],
            ['Union Bordeaux-Bègles', 'UBB', 'France'],
            ['RC Toulon', 'RCT', 'France'],
            ['Montpellier HR', 'MHR', 'France'],
            ['Clermont Auvergne', 'ASM', 'France'],
        ],
        'tennis' => [
            ['Novak Djokovic', 'DJO', 'Serbie'],
            ['Carlos Alcaraz', 'ALC', 'Espagne'],
            ['Jannik Sinner', 'SIN', 'Italie'],
            ['Rafael Nadal', 'NAD', 'Espagne'],
            ['Daniil Medvedev', 'MED', 'Russie'],
            ['Alexander Zverev', 'ZVE', 'Allemagne'],
        ],
        'handball' => [
            ['Paris Saint-Germain HB', 'PSGHB', 'France'],
            ['Montpellier HB', 'MHB', 'France'],
            ['HBC Nantes', 'HBC', 'France'],
            ['Chambéry Savoie', 'CSM', 'France'],
            ['Toulouse HB', 'THB', 'France'],
            ['FC Barcelona Handbol', 'BARÇA', 'Espagne'],
        ],
    ];

    public function run(): void
    {
        $sports = Sport::all()->keyBy('slug');

        foreach ($this->teams as $slug => $teams) {
            if (!isset($sports[$slug])) continue;
            $sport = $sports[$slug];

            foreach ($teams as [$name, $short, $country]) {
                Team::create([
                    'sport_id'   => $sport->id,
                    'name'       => $name,
                    'short_name' => $short,
                    'country'    => $country,
                    'logo_url'   => null,
                ]);
            }
        }
    }
}

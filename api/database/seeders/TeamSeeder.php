<?php

namespace Database\Seeders;

use App\Models\Sport;
use App\Models\Team;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    private array $teamsBySlug = [
        'football'         => [['Paris FC', 'PFC'], ['Lyon United', 'LYO'], ['Marseille AS', 'MAS'], ['Bordeaux FC', 'BDX'], ['Nice SC', 'NSC']],
        'basketball'       => [['Paris Bulls', 'PBL'], ['Lyon Lakers', 'LLA'], ['Marseille Heat', 'MHE'], ['Strasbourg Nets', 'SNE'], ['Dijon Stars', 'DST']],
        'tennis'           => [['Club Tennis Paris', 'CTP'], ['Lyon Tennis', 'LTE'], ['Nice Tennis', 'NTE'], ['Bordeaux TC', 'BTC'], ['Grenoble TC', 'GTC']],
        'rugby'            => [['Stade Toulousain', 'STL'], ['Racing 92', 'R92'], ['La Rochelle', 'LRO'], ['Bordeaux Bègles', 'UBB'], ['Toulon RC', 'RCT']],
        'handball'         => [['Paris HB', 'PHB'], ['Montpellier HB', 'MHB'], ['Nantes HB', 'NHB'], ['Nîmes HB', 'NHB2'], ['Chambéry HB', 'CHB']],
        'volleyball'       => [['Paris Volley', 'PVO'], ['Tours VB', 'TVB'], ['Chaumont VB', 'CVB'], ['Montpellier UC', 'MUC'], ['Cannes RC', 'CRC']],
        'hockey-sur-glace' => [['Rouen Dragons', 'RDR'], ['Grenoble BH', 'GBH'], ['Angers FH', 'AFH'], ['Bordeaux GF', 'BGF'], ['Gap HP', 'GHP']],
        'cyclisme'         => [['Team Cofidis', 'COF'], ['AG2R Citroën', 'AG2'], ['Groupama FDJ', 'FDJ'], ['Decathlon AG2R', 'DAG'], ['Arkéa Samsic', 'ARK']],
    ];

    public function run(): void
    {
        $sports = Sport::all()->keyBy('slug');

        foreach ($this->teamsBySlug as $slug => $teams) {
            if (!isset($sports[$slug])) continue;
            $sport = $sports[$slug];

            foreach ($teams as [$name, $short]) {
                Team::create([
                    'sport_id'   => $sport->id,
                    'name'       => $name,
                    'short_name' => $short,
                    'country'    => 'France',
                    'logo_url'   => null,
                ]);
            }
        }
    }
}

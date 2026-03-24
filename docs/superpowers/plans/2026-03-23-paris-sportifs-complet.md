# Paris Sportifs API - Plan d'implémentation complet

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Compléter l'API de paris sportifs Laravel pour satisfaire toutes les exigences du projet (MongoDB, 5 ressources, Map Reduce, Swagger, tests ≥ 20, frontend HTML/JS).

**Architecture:** Dual-DB — SQLite pour l'auth (users + Sanctum tokens), MongoDB pour toutes les entités métier (Sport, Team, Match, Odd, Bet). Repository pattern pour l'accès aux données des paris. Frontend statique HTML/CSS/JS dans `frontend/` consommant l'API via Fetch.

**Tech Stack:** Laravel 12, PHP 8.2, SQLite (auth), MongoDB 6+ via `mongodb/laravel-mongodb`, Swagger via `darkaonline/l5-swagger`, HTML/CSS/JS vanilla (frontend).

---

## Fichiers créés / modifiés

```
api/
  app/
    Models/
      Sport.php              (modifié → MongoDB)
      Team.php               (modifié → MongoDB)
      SportMatch.php         (modifié → MongoDB)
      Odd.php                (créé → MongoDB)
      Bet.php                (créé → MongoDB)
    Http/Controllers/Api/V1/
      OddController.php      (créé)
      BetController.php      (créé)
      StatsController.php    (créé - Map Reduce)
    Http/Requests/
      StoreOddRequest.php    (créé)
      UpdateOddRequest.php   (créé)
      StoreBetRequest.php    (créé)
      UpdateBetRequest.php   (créé)
    Repositories/
      BetRepository.php      (créé - Repository pattern)
      OddRepository.php      (créé - Repository pattern)
      SportRepository.php    (créé - Repository pattern)
    Services/
      ExternalOddsService.php (créé - CURL / API externe)
      BetSettlementService.php (créé - Strategy pattern)
    Strategies/
      BetOutcomeStrategy.php  (créé - interface)
      HomeWinStrategy.php     (créé)
      DrawStrategy.php        (créé)
      AwayWinStrategy.php     (créé)
  config/
    database.php             (modifié - ajout MongoDB)
    cors.php                 (modifié)
  routes/
    api.php                  (modifié - ajout routes Odd, Bet, Stats)
  database/
    migrations/
      *_create_odds_collection.php  (créé)
      *_create_bets_collection.php  (créé)
    seeders/
      DatabaseSeeder.php     (modifié - 50+ documents)
      SportSeeder.php        (créé)
      TeamSeeder.php         (créé)
      MatchSeeder.php        (créé)
      OddSeeder.php          (créé)
      BetSeeder.php          (créé)
  tests/
    Feature/
      OddApiTest.php         (créé)
      BetApiTest.php         (créé)
      StatsApiTest.php       (créé)
      ExternalApiTest.php    (créé)

frontend/
  index.html                 (créé - liste des sports/matchs)
  matches.html               (créé - matchs avec filtres)
  login.html                 (créé - login/register)
  my-bets.html               (créé - mes paris)
  admin.html                 (créé - panel admin CRUD)
  assets/
    css/style.css            (créé)
    js/api.js                (créé - client API centralisé)
    js/matches.js            (créé)
    js/bets.js               (créé)
    js/admin.js              (créé)
    js/auth.js               (créé)
```

---

## Task 1 : MongoDB — Installation et configuration

**Files:**
- Modify: `api/composer.json`
- Modify: `api/config/database.php`
- Modify: `api/.env` + `api/.env.example`

- [ ] **Step 1: Installer le package MongoDB pour Laravel**

```bash
cd "api"
composer require mongodb/laravel-mongodb
```

Expected: Package installé sans erreur, `composer.json` mis à jour.

- [ ] **Step 2: Ajouter la connexion MongoDB dans config/database.php**

Dans `api/config/database.php`, ajouter dans le tableau `connections` :

```php
'mongodb' => [
    'driver'   => 'mongodb',
    'dsn'      => env('MONGODB_DSN', 'mongodb://localhost:27017'),
    'database' => env('MONGODB_DATABASE', 'paris_sportifs'),
],
```

- [ ] **Step 3: Ajouter les variables MongoDB dans .env**

```
MONGODB_DSN=mongodb://localhost:27017
MONGODB_DATABASE=paris_sportifs
```

Et dans `.env.example` :
```
MONGODB_DSN=mongodb://localhost:27017
MONGODB_DATABASE=paris_sportifs
```

- [ ] **Step 4: Vérifier que Laravel démarre toujours**

```bash
php artisan config:clear && php artisan route:list --path=api/v1
```

Expected: liste des routes affichée sans erreur.

- [ ] **Step 5: Commit**

```bash
git add api/composer.json api/composer.lock api/config/database.php api/.env.example
git commit -m "feat: add MongoDB connection via mongodb/laravel-mongodb"
```

---

## Task 2 : Migrer Sport vers MongoDB

**Files:**
- Modify: `api/app/Models/Sport.php`
- Modify: `api/database/factories/SportFactory.php`
- Create: `api/database/migrations/2026_03_23_000001_create_sports_collection.php`

- [ ] **Step 1: Mettre à jour le modèle Sport**

Remplacer le contenu de `api/app/Models/Sport.php` :

```php
<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Sport extends Model
{
    use HasFactory;

    protected $connection = 'mongodb';
    protected $collection = 'sports';

    protected $fillable = [
        'name',
        'slug',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function teams()
    {
        return $this->hasMany(Team::class, 'sport_id');
    }

    public function matches()
    {
        return $this->hasMany(SportMatch::class, 'sport_id');
    }
}
```

- [ ] **Step 2: Créer la migration MongoDB pour sports**

Créer `api/database/migrations/2026_03_23_000001_create_sports_collection.php` :

```php
<?php

use Illuminate\Database\Migrations\Migration;
use MongoDB\Laravel\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mongodb';

    public function up(): void
    {
        Schema::connection('mongodb')->create('sports', function (Blueprint $collection) {
            $collection->index('slug', null, null, ['unique' => true]);
            $collection->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::connection('mongodb')->drop('sports');
    }
};
```

- [ ] **Step 3: Mettre à jour SportFactory**

Remplacer `api/database/factories/SportFactory.php` :

```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SportFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->unique()->randomElement([
            'Football', 'Basketball', 'Tennis', 'Rugby', 'Handball',
            'Volleyball', 'Baseball', 'Hockey', 'Cycling', 'Boxing',
        ]);

        return [
            'name'      => $name,
            'slug'      => \Illuminate\Support\Str::slug($name),
            'is_active' => true,
        ];
    }
}
```

- [ ] **Step 4: Lancer la migration MongoDB**

```bash
php artisan migrate --path=database/migrations/2026_03_23_000001_create_sports_collection.php
```

- [ ] **Step 5: Lancer les tests existants**

```bash
php artisan test --filter=SprintTwoCrudTest
```

Expected: tests passent (ajuster si besoin RefreshDatabase → utiliser `RefreshDatabase` avec config de test pointant sur SQLite pour users et MongoDB de test).

- [ ] **Step 6: Commit**

```bash
git add api/app/Models/Sport.php api/database/factories/SportFactory.php api/database/migrations/2026_03_23_000001_create_sports_collection.php
git commit -m "feat: migrate Sport model to MongoDB"
```

---

## Task 3 : Migrer Team et SportMatch vers MongoDB

**Files:**
- Modify: `api/app/Models/Team.php`
- Modify: `api/app/Models/SportMatch.php`
- Modify: `api/database/factories/TeamFactory.php`
- Modify: `api/database/factories/SportMatchFactory.php`
- Create: `api/database/migrations/2026_03_23_000002_create_teams_collection.php`
- Create: `api/database/migrations/2026_03_23_000003_create_matches_collection.php`

- [ ] **Step 1: Mettre à jour Team**

```php
<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Team extends Model
{
    use HasFactory;

    protected $connection = 'mongodb';
    protected $collection = 'teams';

    protected $fillable = [
        'sport_id',
        'name',
        'short_name',
        'country',
        'logo_url',
    ];

    public function sport()
    {
        return $this->belongsTo(Sport::class, 'sport_id');
    }

    public function homeMatches()
    {
        return $this->hasMany(SportMatch::class, 'home_team_id');
    }

    public function awayMatches()
    {
        return $this->hasMany(SportMatch::class, 'away_team_id');
    }
}
```

- [ ] **Step 2: Mettre à jour SportMatch**

```php
<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SportMatch extends Model
{
    use HasFactory;

    protected $connection = 'mongodb';
    protected $collection = 'matches';

    protected $fillable = [
        'sport_id',
        'home_team_id',
        'away_team_id',
        'starts_at',
        'status',
        'home_score',
        'away_score',
    ];

    protected function casts(): array
    {
        return [
            'starts_at'  => 'datetime',
            'home_score' => 'integer',
            'away_score' => 'integer',
        ];
    }

    public function sport()
    {
        return $this->belongsTo(Sport::class, 'sport_id');
    }

    public function homeTeam()
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function awayTeam()
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }
}
```

- [ ] **Step 3: Créer les migrations MongoDB pour teams et matches**

`api/database/migrations/2026_03_23_000002_create_teams_collection.php` :

```php
<?php

use Illuminate\Database\Migrations\Migration;
use MongoDB\Laravel\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mongodb';

    public function up(): void
    {
        Schema::connection('mongodb')->create('teams', function (Blueprint $collection) {
            $collection->index('sport_id');
            $collection->index('name');
        });
    }

    public function down(): void
    {
        Schema::connection('mongodb')->drop('teams');
    }
};
```

`api/database/migrations/2026_03_23_000003_create_matches_collection.php` :

```php
<?php

use Illuminate\Database\Migrations\Migration;
use MongoDB\Laravel\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mongodb';

    public function up(): void
    {
        Schema::connection('mongodb')->create('matches', function (Blueprint $collection) {
            $collection->index(['sport_id', 'starts_at']);
            $collection->index('status');
        });
    }

    public function down(): void
    {
        Schema::connection('mongodb')->drop('matches');
    }
};
```

- [ ] **Step 4: Mettre à jour les factories Team et SportMatch**

`TeamFactory.php` :
```php
<?php
namespace Database\Factories;
use App\Models\Sport;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeamFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sport_id'   => Sport::factory(),
            'name'       => $this->faker->unique()->company(),
            'short_name' => strtoupper($this->faker->lexify('???')),
            'country'    => $this->faker->country(),
            'logo_url'   => null,
        ];
    }
}
```

`SportMatchFactory.php` :
```php
<?php
namespace Database\Factories;
use App\Models\Sport;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

class SportMatchFactory extends Factory
{
    public function definition(): array
    {
        $sport = Sport::factory()->create();
        return [
            'sport_id'     => $sport->id,
            'home_team_id' => Team::factory()->create(['sport_id' => $sport->id])->id,
            'away_team_id' => Team::factory()->create(['sport_id' => $sport->id])->id,
            'starts_at'    => $this->faker->dateTimeBetween('+1 day', '+30 days'),
            'status'       => 'scheduled',
            'home_score'   => null,
            'away_score'   => null,
        ];
    }
}
```

- [ ] **Step 5: Lancer les migrations**

```bash
php artisan migrate --path=database/migrations/2026_03_23_000002_create_teams_collection.php
php artisan migrate --path=database/migrations/2026_03_23_000003_create_matches_collection.php
```

- [ ] **Step 6: Lancer les tests**

```bash
php artisan test
```

Expected: tous les tests passent.

- [ ] **Step 7: Commit**

```bash
git add api/app/Models/ api/database/factories/ api/database/migrations/2026_03_23_000002* api/database/migrations/2026_03_23_000003*
git commit -m "feat: migrate Team and SportMatch models to MongoDB"
```

---

## Task 4 : Ressource Odd (Cotes)

**Files:**
- Create: `api/app/Models/Odd.php`
- Create: `api/app/Http/Controllers/Api/V1/OddController.php`
- Create: `api/app/Http/Requests/StoreOddRequest.php`
- Create: `api/app/Http/Requests/UpdateOddRequest.php`
- Create: `api/database/factories/OddFactory.php`
- Create: `api/database/migrations/2026_03_23_000004_create_odds_collection.php`
- Modify: `api/routes/api.php`
- Create: `api/tests/Feature/OddApiTest.php`

- [ ] **Step 1: Créer le modèle Odd**

```php
<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Odd extends Model
{
    use HasFactory;

    protected $connection = 'mongodb';
    protected $collection = 'odds';

    protected $fillable = [
        'match_id',
        'home_win',
        'draw',
        'away_win',
        'bookmaker',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'home_win' => 'float',
            'draw'     => 'float',
            'away_win' => 'float',
        ];
    }

    public function match()
    {
        return $this->belongsTo(SportMatch::class, 'match_id');
    }
}
```

- [ ] **Step 2: Créer OddController**

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOddRequest;
use App\Http\Requests\UpdateOddRequest;
use App\Models\Odd;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class OddController extends Controller
{
    public function index(): JsonResponse
    {
        $odds = Odd::query()
            ->when(request()->filled('match_id'), fn($q) => $q->where('match_id', request('match_id')))
            ->with('match')
            ->orderByDesc('created_at')
            ->paginate(min((int) request('per_page', 15), 50));

        return response()->json($odds);
    }

    public function store(StoreOddRequest $request): JsonResponse
    {
        $odd = Odd::query()->create($request->validated());
        return response()->json($odd->load('match'), 201);
    }

    public function show(Odd $odd): JsonResponse
    {
        return response()->json($odd->load('match'));
    }

    public function update(UpdateOddRequest $request, Odd $odd): JsonResponse
    {
        $odd->update($request->validated());
        return response()->json($odd->fresh()->load('match'));
    }

    public function destroy(Odd $odd): Response
    {
        $odd->delete();
        return response()->noContent();
    }
}
```

- [ ] **Step 3: Créer StoreOddRequest et UpdateOddRequest**

`StoreOddRequest.php` :
```php
<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;

class StoreOddRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'match_id' => ['required', 'string'],
            'home_win' => ['required', 'numeric', 'min:1.01'],
            'draw'     => ['required', 'numeric', 'min:1.01'],
            'away_win' => ['required', 'numeric', 'min:1.01'],
            'bookmaker' => ['required', 'string', 'max:100'],
            'source'   => ['nullable', 'in:internal,external'],
        ];
    }
}
```

`UpdateOddRequest.php` :
```php
<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOddRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'home_win'  => ['sometimes', 'numeric', 'min:1.01'],
            'draw'      => ['sometimes', 'numeric', 'min:1.01'],
            'away_win'  => ['sometimes', 'numeric', 'min:1.01'],
            'bookmaker' => ['sometimes', 'string', 'max:100'],
            'source'    => ['sometimes', 'in:internal,external'],
        ];
    }
}
```

- [ ] **Step 4: Créer OddFactory**

```php
<?php
namespace Database\Factories;
use App\Models\SportMatch;
use Illuminate\Database\Eloquent\Factories\Factory;

class OddFactory extends Factory
{
    public function definition(): array
    {
        return [
            'match_id'  => SportMatch::factory(),
            'home_win'  => $this->faker->randomFloat(2, 1.10, 5.00),
            'draw'      => $this->faker->randomFloat(2, 2.50, 4.50),
            'away_win'  => $this->faker->randomFloat(2, 1.10, 5.00),
            'bookmaker' => $this->faker->randomElement(['BetFrance', 'Unibet', 'PMU', 'Winamax']),
            'source'    => 'internal',
        ];
    }
}
```

- [ ] **Step 5: Créer la migration MongoDB**

```php
<?php
use Illuminate\Database\Migrations\Migration;
use MongoDB\Laravel\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mongodb';

    public function up(): void
    {
        Schema::connection('mongodb')->create('odds', function (Blueprint $collection) {
            $collection->index('match_id');
            $collection->index('bookmaker');
        });
    }

    public function down(): void
    {
        Schema::connection('mongodb')->drop('odds');
    }
};
```

- [ ] **Step 6: Ajouter les routes Odd dans api.php**

Dans le groupe public :
```php
Route::apiResource('odds', OddController::class)->only(['index', 'show']);
```

Dans le groupe admin :
```php
Route::apiResource('odds', OddController::class)->except(['index', 'show']);
```

- [ ] **Step 7: Écrire et lancer les tests OddApiTest**

`tests/Feature/OddApiTest.php` :
```php
<?php
namespace Tests\Feature;

use App\Models\Odd;
use App\Models\Sport;
use App\Models\SportMatch;
use App\Models\Team;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OddApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_list_odds(): void
    {
        Odd::factory()->count(3)->create();
        $this->getJson('/api/v1/odds')->assertOk()->assertJsonStructure(['data']);
    }

    public function test_guest_can_filter_odds_by_match(): void
    {
        $match = SportMatch::factory()->create();
        Odd::factory()->create(['match_id' => $match->id]);
        Odd::factory()->count(2)->create();

        $this->getJson('/api/v1/odds?match_id=' . $match->id)
            ->assertOk()
            ->assertJsonPath('total', 1);
    }

    public function test_admin_can_create_odd(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $match = SportMatch::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/odds', [
                'match_id' => (string) $match->id,
                'home_win' => 1.85,
                'draw'     => 3.50,
                'away_win' => 4.20,
                'bookmaker' => 'Winamax',
            ])
            ->assertCreated()
            ->assertJsonPath('home_win', 1.85);
    }

    public function test_non_admin_cannot_create_odd(): void
    {
        $user = User::factory()->create(['role' => UserRole::USER->value]);
        $match = SportMatch::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/odds', [
                'match_id' => (string) $match->id,
                'home_win' => 1.85,
                'draw'     => 3.50,
                'away_win' => 4.20,
                'bookmaker' => 'Winamax',
            ])
            ->assertForbidden();
    }

    public function test_admin_can_delete_odd(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $odd = Odd::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/v1/odds/' . $odd->id)
            ->assertNoContent();
    }
}
```

```bash
php artisan test --filter=OddApiTest
```

- [ ] **Step 8: Commit**

```bash
git add api/app/Models/Odd.php api/app/Http/Controllers/Api/V1/OddController.php api/app/Http/Requests/StoreOddRequest.php api/app/Http/Requests/UpdateOddRequest.php api/database/factories/OddFactory.php api/database/migrations/2026_03_23_000004* api/routes/api.php api/tests/Feature/OddApiTest.php
git commit -m "feat: add Odd (cotes) resource with CRUD and tests"
```

---

## Task 5 : Ressource Bet (Paris)

**Files:**
- Create: `api/app/Models/Bet.php`
- Create: `api/app/Http/Controllers/Api/V1/BetController.php`
- Create: `api/app/Http/Requests/StoreBetRequest.php`
- Create: `api/app/Http/Requests/UpdateBetRequest.php`
- Create: `api/database/factories/BetFactory.php`
- Create: `api/database/migrations/2026_03_23_000005_create_bets_collection.php`
- Modify: `api/routes/api.php`
- Create: `api/tests/Feature/BetApiTest.php`

- [ ] **Step 1: Créer le modèle Bet**

```php
<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Bet extends Model
{
    use HasFactory;

    protected $connection = 'mongodb';
    protected $collection = 'bets';

    protected $fillable = [
        'user_id',
        'match_id',
        'amount',
        'predicted_outcome',
        'odds_value',
        'potential_gain',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'amount'        => 'float',
            'odds_value'    => 'float',
            'potential_gain' => 'float',
        ];
    }

    public function match()
    {
        return $this->belongsTo(SportMatch::class, 'match_id');
    }
}
```

- [ ] **Step 2: Créer BetController**

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBetRequest;
use App\Http\Requests\UpdateBetRequest;
use App\Models\Bet;
use App\Models\Odd;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class BetController extends Controller
{
    public function index(): JsonResponse
    {
        $bets = Bet::query()
            ->where('user_id', request()->user()->id)
            ->when(request()->filled('status'), fn($q) => $q->where('status', request('status')))
            ->with('match')
            ->orderByDesc('created_at')
            ->paginate(min((int) request('per_page', 15), 50));

        return response()->json($bets);
    }

    public function store(StoreBetRequest $request): JsonResponse
    {
        $data = $request->validated();

        $odd = Odd::query()->where('match_id', $data['match_id'])->latest()->first();
        if (!$odd) {
            throw ValidationException::withMessages([
                'match_id' => ['No odds available for this match.'],
            ]);
        }

        $oddsValue = match ($data['predicted_outcome']) {
            'home_win' => $odd->home_win,
            'draw'     => $odd->draw,
            'away_win' => $odd->away_win,
        };

        $bet = Bet::query()->create([
            ...$data,
            'user_id'       => $request->user()->id,
            'odds_value'    => $oddsValue,
            'potential_gain' => round($data['amount'] * $oddsValue, 2),
            'status'        => 'pending',
        ]);

        return response()->json($bet->load('match'), 201);
    }

    public function show(Bet $bet): JsonResponse
    {
        $this->authorizeOwner($bet);
        return response()->json($bet->load('match'));
    }

    public function update(UpdateBetRequest $request, Bet $bet): JsonResponse
    {
        abort_if($bet->status !== 'pending', 422, 'Cannot update a settled bet.');
        $this->authorizeOwner($bet);
        $bet->update($request->validated());
        return response()->json($bet->fresh()->load('match'));
    }

    public function destroy(Bet $bet): Response
    {
        abort_if($bet->status !== 'pending', 422, 'Cannot cancel a settled bet.');
        $this->authorizeOwner($bet);
        $bet->update(['status' => 'cancelled']);
        return response()->noContent();
    }

    private function authorizeOwner(Bet $bet): void
    {
        $user = request()->user();
        if ($bet->user_id !== $user->id && $user->role !== 'admin') {
            abort(403, 'Forbidden.');
        }
    }
}
```

- [ ] **Step 3: Créer StoreBetRequest et UpdateBetRequest**

`StoreBetRequest.php` :
```php
<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;

class StoreBetRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'match_id'          => ['required', 'string'],
            'amount'            => ['required', 'numeric', 'min:1', 'max:10000'],
            'predicted_outcome' => ['required', 'in:home_win,draw,away_win'],
        ];
    }
}
```

`UpdateBetRequest.php` :
```php
<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBetRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'amount'            => ['sometimes', 'numeric', 'min:1', 'max:10000'],
            'predicted_outcome' => ['sometimes', 'in:home_win,draw,away_win'],
        ];
    }
}
```

- [ ] **Step 4: Créer BetFactory**

```php
<?php
namespace Database\Factories;
use App\Models\SportMatch;
use Illuminate\Database\Eloquent\Factories\Factory;

class BetFactory extends Factory
{
    public function definition(): array
    {
        $amount = $this->faker->randomFloat(2, 5, 200);
        $oddsValue = $this->faker->randomFloat(2, 1.10, 5.00);

        return [
            'user_id'          => 1,
            'match_id'         => SportMatch::factory(),
            'amount'           => $amount,
            'predicted_outcome' => $this->faker->randomElement(['home_win', 'draw', 'away_win']),
            'odds_value'       => $oddsValue,
            'potential_gain'   => round($amount * $oddsValue, 2),
            'status'           => $this->faker->randomElement(['pending', 'won', 'lost']),
        ];
    }
}
```

- [ ] **Step 5: Créer la migration et les routes**

Migration `2026_03_23_000005_create_bets_collection.php` :
```php
<?php
use Illuminate\Database\Migrations\Migration;
use MongoDB\Laravel\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mongodb';

    public function up(): void
    {
        Schema::connection('mongodb')->create('bets', function (Blueprint $collection) {
            $collection->index('user_id');
            $collection->index('match_id');
            $collection->index('status');
        });
    }

    public function down(): void
    {
        Schema::connection('mongodb')->drop('bets');
    }
};
```

Routes dans `api.php`, dans le groupe `auth:sanctum` :
```php
Route::apiResource('bets', BetController::class);
```

- [ ] **Step 6: Tests BetApiTest**

```php
<?php
namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Bet;
use App\Models\Odd;
use App\Models\SportMatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BetApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_place_a_bet(): void
    {
        $user = User::factory()->create(['role' => UserRole::USER->value]);
        $match = SportMatch::factory()->create(['status' => 'scheduled']);
        Odd::factory()->create([
            'match_id' => $match->id,
            'home_win' => 1.85,
            'draw'     => 3.50,
            'away_win' => 4.20,
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/bets', [
                'match_id'          => (string) $match->id,
                'amount'            => 50,
                'predicted_outcome' => 'home_win',
            ])
            ->assertCreated()
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('amount', 50.0)
            ->assertJsonPath('odds_value', 1.85)
            ->assertJsonPath('potential_gain', 92.5);
    }

    public function test_bet_fails_without_odds(): void
    {
        $user = User::factory()->create(['role' => UserRole::USER->value]);
        $match = SportMatch::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/bets', [
                'match_id'          => (string) $match->id,
                'amount'            => 50,
                'predicted_outcome' => 'home_win',
            ])
            ->assertStatus(422);
    }

    public function test_user_can_list_own_bets(): void
    {
        $user = User::factory()->create(['role' => UserRole::USER->value]);
        Bet::factory()->count(3)->create(['user_id' => $user->id]);
        Bet::factory()->count(2)->create(['user_id' => 999]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/bets')
            ->assertOk()
            ->assertJsonPath('total', 3);
    }

    public function test_unauthenticated_user_cannot_place_bet(): void
    {
        $this->postJson('/api/v1/bets', ['match_id' => '1', 'amount' => 50, 'predicted_outcome' => 'home_win'])
            ->assertUnauthorized();
    }
}
```

```bash
php artisan test --filter=BetApiTest
```

- [ ] **Step 7: Commit**

```bash
git add api/app/Models/Bet.php api/app/Http/Controllers/Api/V1/BetController.php api/app/Http/Requests/StoreBetRequest.php api/app/Http/Requests/UpdateBetRequest.php api/database/factories/BetFactory.php api/database/migrations/2026_03_23_000005* api/routes/api.php api/tests/Feature/BetApiTest.php
git commit -m "feat: add Bet (paris) resource with business logic and tests"
```

---

## Task 6 : Repository Pattern (Design Pattern #1)

**Files:**
- Create: `api/app/Repositories/BetRepository.php`
- Create: `api/app/Repositories/OddRepository.php`
- Create: `api/app/Repositories/SportRepository.php`
- Modify: `api/app/Http/Controllers/Api/V1/BetController.php`
- Modify: `api/app/Http/Controllers/Api/V1/OddController.php`

- [ ] **Step 1: Créer BetRepository**

```php
<?php

namespace App\Repositories;

use App\Models\Bet;
use Illuminate\Pagination\LengthAwarePaginator;

class BetRepository
{
    public function forUser(int $userId, array $filters = []): LengthAwarePaginator
    {
        return Bet::query()
            ->where('user_id', $userId)
            ->when(isset($filters['status']), fn($q) => $q->where('status', $filters['status']))
            ->with('match')
            ->orderByDesc('created_at')
            ->paginate(min((int) ($filters['per_page'] ?? 15), 50));
    }

    public function create(array $data): Bet
    {
        return Bet::query()->create($data);
    }

    public function update(Bet $bet, array $data): Bet
    {
        $bet->update($data);
        return $bet->fresh();
    }
}
```

- [ ] **Step 2: Créer OddRepository**

```php
<?php

namespace App\Repositories;

use App\Models\Odd;

class OddRepository
{
    public function latestForMatch(string $matchId): ?Odd
    {
        return Odd::query()->where('match_id', $matchId)->latest()->first();
    }

    public function create(array $data): Odd
    {
        return Odd::query()->create($data);
    }
}
```

- [ ] **Step 3: Créer SportRepository**

```php
<?php

namespace App\Repositories;

use App\Models\Sport;
use Illuminate\Pagination\LengthAwarePaginator;

class SportRepository
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return Sport::query()
            ->when(isset($filters['is_active']), fn($q) => $q->where('is_active', (bool) $filters['is_active']))
            ->orderBy('name')
            ->paginate(min((int) ($filters['per_page'] ?? 15), 50));
    }
}
```

- [ ] **Step 4: Injecter BetRepository dans BetController**

Mettre à jour le constructeur de BetController :
```php
public function __construct(private readonly BetRepository $bets, private readonly OddRepository $odds) {}
```
Et remplacer les appels directs au modèle par les méthodes du repository.

- [ ] **Step 5: Bind les repositories dans AppServiceProvider**

Dans `app/Providers/AppServiceProvider.php`, méthode `register()` :
```php
$this->app->singleton(\App\Repositories\BetRepository::class);
$this->app->singleton(\App\Repositories\OddRepository::class);
$this->app->singleton(\App\Repositories\SportRepository::class);
```

- [ ] **Step 6: Lancer les tests**

```bash
php artisan test
```

Expected: tous les tests passent toujours.

- [ ] **Step 7: Commit**

```bash
git add api/app/Repositories/ api/app/Http/Controllers/ api/app/Providers/AppServiceProvider.php
git commit -m "refactor: introduce Repository pattern for Bet, Odd, Sport"
```

---

## Task 7 : Strategy Pattern (Design Pattern #2) — Résolution des paris

**Files:**
- Create: `api/app/Strategies/BetOutcomeStrategy.php` (interface)
- Create: `api/app/Strategies/HomeWinStrategy.php`
- Create: `api/app/Strategies/DrawStrategy.php`
- Create: `api/app/Strategies/AwayWinStrategy.php`
- Create: `api/app/Services/BetSettlementService.php`
- Modify: `api/routes/api.php` (endpoint admin pour settle un match)
- Modify: `api/app/Http/Controllers/Api/V1/SportMatchController.php`

- [ ] **Step 1: Créer l'interface BetOutcomeStrategy**

```php
<?php

namespace App\Strategies;

use App\Models\SportMatch;

interface BetOutcomeStrategy
{
    public function evaluate(SportMatch $match, string $predictedOutcome): string;
}
```

- [ ] **Step 2: Créer les stratégies concrètes**

`HomeWinStrategy.php` :
```php
<?php
namespace App\Strategies;
use App\Models\SportMatch;

class HomeWinStrategy implements BetOutcomeStrategy
{
    public function evaluate(SportMatch $match, string $predictedOutcome): string
    {
        $actualOutcome = $match->home_score > $match->away_score ? 'home_win'
            : ($match->home_score < $match->away_score ? 'away_win' : 'draw');

        return $predictedOutcome === $actualOutcome ? 'won' : 'lost';
    }
}
```

`DrawStrategy.php` et `AwayWinStrategy.php` — même logique, réutilisent HomeWinStrategy.

- [ ] **Step 3: Créer BetSettlementService**

```php
<?php

namespace App\Services;

use App\Models\Bet;
use App\Models\SportMatch;
use App\Strategies\HomeWinStrategy;

class BetSettlementService
{
    private HomeWinStrategy $strategy;

    public function __construct()
    {
        $this->strategy = new HomeWinStrategy();
    }

    public function settleMatch(SportMatch $match): int
    {
        $bets = Bet::query()->where('match_id', (string) $match->id)->where('status', 'pending')->get();

        foreach ($bets as $bet) {
            $result = $this->strategy->evaluate($match, $bet->predicted_outcome);
            $bet->update(['status' => $result]);
        }

        return $bets->count();
    }
}
```

- [ ] **Step 4: Ajouter l'endpoint de résolution dans SportMatchController**

```php
public function settle(SportMatch $matchItem, BetSettlementService $settlementService): JsonResponse
{
    abort_if($matchItem->status === 'finished', 422, 'Match already settled.');
    abort_if(is_null($matchItem->home_score) || is_null($matchItem->away_score), 422, 'Scores must be set before settling.');

    $matchItem->update(['status' => 'finished']);
    $count = $settlementService->settleMatch($matchItem);

    return response()->json([
        'message'       => 'Match settled.',
        'bets_resolved' => $count,
        'match'         => $matchItem->fresh(),
    ]);
}
```

Route dans le groupe admin :
```php
Route::post('matches/{matchItem}/settle', [SportMatchController::class, 'settle']);
```

- [ ] **Step 5: Commit**

```bash
git add api/app/Strategies/ api/app/Services/BetSettlementService.php api/app/Http/Controllers/Api/V1/SportMatchController.php api/routes/api.php
git commit -m "feat: add Strategy pattern for bet settlement on match finish"
```

---

## Task 8 : Map Reduce — Statistiques MongoDB (2 opérations)

**Files:**
- Create: `api/app/Http/Controllers/Api/V1/StatsController.php`
- Modify: `api/routes/api.php`
- Create: `api/tests/Feature/StatsApiTest.php`

- [ ] **Step 1: Créer StatsController avec 2 agrégations Map Reduce**

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Bet;
use App\Models\SportMatch;
use Illuminate\Http\JsonResponse;

class StatsController extends Controller
{
    /**
     * Map Reduce #1 : Total des mises et gains potentiels par sport
     * Agrégation MongoDB : group by sport_id, sum amount et potential_gain
     */
    public function betsBySport(): JsonResponse
    {
        $pipeline = [
            ['$match' => ['status' => ['$in' => ['pending', 'won', 'lost']]]],
            ['$group' => [
                '_id'             => '$sport_id',
                'total_bets'      => ['$sum' => 1],
                'total_amount'    => ['$sum' => '$amount'],
                'total_potential' => ['$sum' => '$potential_gain'],
                'won_bets'        => ['$sum' => ['$cond' => [['$eq' => ['$status', 'won']], 1, 0]]],
            ]],
            ['$sort' => ['total_amount' => -1]],
        ];

        $results = Bet::raw(fn($c) => $c->aggregate($pipeline))->toArray();

        return response()->json([
            'operation'   => 'bets_by_sport',
            'description' => 'Total des paris et mises regroupés par sport',
            'data'        => $results,
        ]);
    }

    /**
     * Map Reduce #2 : Performance par utilisateur (victoires, pertes, ROI)
     */
    public function userPerformance(): JsonResponse
    {
        $pipeline = [
            ['$match' => ['status' => ['$in' => ['won', 'lost']]]],
            ['$group' => [
                '_id'          => '$user_id',
                'total_bets'   => ['$sum' => 1],
                'total_staked' => ['$sum' => '$amount'],
                'won_bets'     => ['$sum' => ['$cond' => [['$eq' => ['$status', 'won']], 1, 0]]],
                'total_gained' => ['$sum' => ['$cond' => [['$eq' => ['$status', 'won']], '$potential_gain', 0]]],
            ]],
            ['$addFields' => [
                'roi_percent' => [
                    '$multiply' => [
                        ['$divide' => [
                            ['$subtract' => ['$total_gained', '$total_staked']],
                            '$total_staked',
                        ]],
                        100,
                    ],
                ],
            ]],
            ['$sort' => ['total_gained' => -1]],
        ];

        $results = Bet::raw(fn($c) => $c->aggregate($pipeline))->toArray();

        return response()->json([
            'operation'   => 'user_performance',
            'description' => 'Performance ROI par utilisateur sur les paris résolus',
            'data'        => $results,
        ]);
    }
}
```

- [ ] **Step 2: Ajouter les routes stats**

Dans le groupe `auth:sanctum` :
```php
Route::prefix('stats')->group(function () {
    Route::get('/bets-by-sport', [StatsController::class, 'betsBySport']);
    Route::get('/user-performance', [StatsController::class, 'userPerformance']);
});
```

- [ ] **Step 3: Écrire et lancer les tests StatsApiTest**

```php
<?php
namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Bet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_get_bets_by_sport_stats(): void
    {
        $user = User::factory()->create(['role' => UserRole::USER->value]);
        Bet::factory()->count(5)->create(['user_id' => $user->id, 'status' => 'won']);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/stats/bets-by-sport')
            ->assertOk()
            ->assertJsonStructure(['operation', 'description', 'data']);
    }

    public function test_authenticated_user_can_get_performance_stats(): void
    {
        $user = User::factory()->create(['role' => UserRole::USER->value]);
        Bet::factory()->count(3)->create(['user_id' => $user->id, 'status' => 'won']);
        Bet::factory()->count(2)->create(['user_id' => $user->id, 'status' => 'lost']);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/stats/user-performance')
            ->assertOk()
            ->assertJsonPath('operation', 'user_performance');
    }

    public function test_unauthenticated_user_cannot_access_stats(): void
    {
        $this->getJson('/api/v1/stats/bets-by-sport')->assertUnauthorized();
    }
}
```

```bash
php artisan test --filter=StatsApiTest
```

- [ ] **Step 4: Commit**

```bash
git add api/app/Http/Controllers/Api/V1/StatsController.php api/routes/api.php api/tests/Feature/StatsApiTest.php
git commit -m "feat: add Map Reduce stats endpoints (bets by sport, user performance)"
```

---

## Task 9 : Service Factory Pattern (Design Pattern #3) + Intégration API externe (CURL)

**Files:**
- Create: `api/app/Services/ExternalOddsService.php`
- Create: `api/app/Http/Controllers/Api/V1/ExternalSyncController.php`
- Modify: `api/routes/api.php`
- Create: `api/tests/Feature/ExternalApiTest.php`

- [ ] **Step 1: Créer ExternalOddsService (CURL via Http facade)**

Ce service consomme The Odds API (gratuite) pour récupérer des cotes réelles.

```php
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
        $this->apiKey = config('services.odds_api.key', 'demo');
    }

    /**
     * Récupère la liste des sports disponibles depuis l'API externe.
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
                Log::warning('ExternalOddsService: API call failed', [
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
```

- [ ] **Step 2: Créer ExternalSyncController**

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ExternalOddsService;
use Illuminate\Http\JsonResponse;

class ExternalSyncController extends Controller
{
    public function __construct(private readonly ExternalOddsService $externalOddsService) {}

    public function sports(): JsonResponse
    {
        $sports = $this->externalOddsService->fetchSports();

        return response()->json([
            'source' => 'external_api',
            'count'  => count($sports),
            'data'   => $sports,
        ]);
    }
}
```

- [ ] **Step 3: Ajouter la config et les routes**

Dans `config/services.php` :
```php
'odds_api' => [
    'key' => env('ODDS_API_KEY', ''),
],
```

Dans `.env` et `.env.example` :
```
ODDS_API_KEY=
```

Routes (public, dans le groupe v1) :
```php
Route::get('/external/sports', [ExternalSyncController::class, 'sports']);
```

- [ ] **Step 4: Tests ExternalApiTest**

```php
<?php
namespace Tests\Feature;

use App\Services\ExternalOddsService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExternalApiTest extends TestCase
{
    public function test_external_sports_endpoint_returns_data(): void
    {
        Http::fake([
            '*the-odds-api*' => Http::response([
                ['key' => 'soccer', 'title' => 'Soccer', 'active' => true],
            ], 200),
        ]);

        $this->getJson('/api/v1/external/sports')
            ->assertOk()
            ->assertJsonStructure(['source', 'count', 'data']);
    }

    public function test_external_service_returns_fallback_on_api_failure(): void
    {
        Http::fake([
            '*the-odds-api*' => Http::response([], 500),
        ]);

        $this->getJson('/api/v1/external/sports')
            ->assertOk()
            ->assertJsonPath('source', 'external_api');
    }

    public function test_factory_method_normalizes_external_odd(): void
    {
        $raw = ['home' => 1.95, 'draw' => 3.20, 'away' => 4.10, 'bookmaker' => 'TestBook'];
        $odd = ExternalOddsService::createOddFromExternal($raw);

        $this->assertEquals(1.95, $odd['home_win']);
        $this->assertEquals('external', $odd['source']);
        $this->assertEquals('TestBook', $odd['bookmaker']);
    }
}
```

```bash
php artisan test --filter=ExternalApiTest
```

- [ ] **Step 5: Commit**

```bash
git add api/app/Services/ExternalOddsService.php api/app/Http/Controllers/Api/V1/ExternalSyncController.php api/config/services.php api/.env.example api/routes/api.php api/tests/Feature/ExternalApiTest.php
git commit -m "feat: add ExternalOddsService with CURL/Http and Factory pattern"
```

---

## Task 10 : CORS et Rate Limiting

**Files:**
- Modify: `api/config/cors.php`
- Modify: `api/bootstrap/app.php` (throttle middleware)
- Modify: `api/routes/api.php`

- [ ] **Step 1: Configurer CORS**

Dans `api/config/cors.php` :
```php
'paths' => ['api/*'],
'allowed_methods' => ['*'],
'allowed_origins' => ['*'],
'allowed_origins_patterns' => [],
'allowed_headers' => ['*'],
'exposed_headers' => [],
'max_age' => 0,
'supports_credentials' => false,
```

- [ ] **Step 2: Configurer le rate limiting**

Dans `api/bootstrap/app.php`, dans `withRouting` ou via `RateLimiter` dans `AppServiceProvider` :

Dans `AppServiceProvider::boot()` :
```php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;

RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});

RateLimiter::for('auth', function (Request $request) {
    return Limit::perMinute(10)->by($request->ip());
});
```

- [ ] **Step 3: Appliquer throttle sur les routes**

Dans `api/routes/api.php`, wrapper le groupe v1 :
```php
Route::prefix('v1')->middleware('throttle:api')->group(function () {
    // ...
    Route::prefix('auth')->middleware('throttle:auth')->group(function () {
        // register/login
    });
});
```

- [ ] **Step 4: Vérifier que les tests passent**

```bash
php artisan test
```

- [ ] **Step 5: Commit**

```bash
git add api/config/cors.php api/bootstrap/app.php api/routes/api.php api/app/Providers/AppServiceProvider.php
git commit -m "feat: configure CORS and rate limiting (60 req/min API, 10 req/min auth)"
```

---

## Task 11 : Swagger / OpenAPI Documentation

**Files:**
- Modify: `api/composer.json` (ajout darkaonline/l5-swagger)
- Create: `api/config/l5-swagger.php`
- Modify: controllers pour ajouter les annotations OpenAPI

- [ ] **Step 1: Installer l5-swagger**

```bash
composer require darkaonline/l5-swagger
php artisan vendor:publish --provider "L5Swagger\L5SwaggerServiceProvider"
```

- [ ] **Step 2: Configurer dans .env**

```
L5_SWAGGER_GENERATE_ALWAYS=true
L5_SWAGGER_UI_DOC_EXPANSION=list
```

- [ ] **Step 3: Ajouter les annotations OpenAPI sur AuthController**

Ajouter en tête du fichier `AuthController.php` :
```php
/**
 * @OA\Info(
 *   title="Paris Sportifs API",
 *   version="1.0.0",
 *   description="API REST de gestion de paris sportifs — Laravel 12 + MongoDB"
 * )
 * @OA\SecurityScheme(
 *   securityScheme="bearerAuth",
 *   type="http",
 *   scheme="bearer",
 *   bearerFormat="Sanctum"
 * )
 */
```

Annoter chaque endpoint avec `@OA\Post`, `@OA\Get`, etc.

- [ ] **Step 4: Générer la doc**

```bash
php artisan l5-swagger:generate
```

Expected: fichier `storage/api-docs/api-docs.json` généré. Swagger UI accessible sur `/api/documentation`.

- [ ] **Step 5: Commit**

```bash
git add api/composer.json api/composer.lock api/config/l5-swagger.php api/app/Http/Controllers/ api/.env.example
git commit -m "feat: add Swagger/OpenAPI documentation via l5-swagger"
```

---

## Task 12 : Seeder MongoDB (50+ documents)

**Files:**
- Create: `api/database/seeders/SportSeeder.php`
- Create: `api/database/seeders/TeamSeeder.php`
- Create: `api/database/seeders/MatchSeeder.php`
- Create: `api/database/seeders/OddSeeder.php`
- Create: `api/database/seeders/BetSeeder.php`
- Modify: `api/database/seeders/DatabaseSeeder.php`

- [ ] **Step 1: Créer les seeders**

`DatabaseSeeder.php` :
```php
<?php
namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        User::factory()->create([
            'name'  => 'Admin',
            'email' => 'admin@paris-sportifs.test',
            'role'  => UserRole::ADMIN->value,
        ]);

        // Users
        User::factory()->count(10)->create(['role' => UserRole::USER->value]);

        $this->call([
            SportSeeder::class,
            TeamSeeder::class,
            MatchSeeder::class,
            OddSeeder::class,
            BetSeeder::class,
        ]);
    }
}
```

`SportSeeder.php` — 8 sports
`TeamSeeder.php` — 5 équipes par sport = 40 équipes
`MatchSeeder.php` — 2 matchs par sport = 16 matchs
`OddSeeder.php` — 1 cote par match = 16 cotes
`BetSeeder.php` — 3 paris par user = 30 paris

Total: 8+40+16+16+30+11 = **121 documents** (bien > 50).

- [ ] **Step 2: Lancer le seeder**

```bash
php artisan db:seed
```

Expected: pas d'erreur, données insérées dans MongoDB + SQLite.

- [ ] **Step 3: Commit**

```bash
git add api/database/seeders/
git commit -m "feat: add comprehensive MongoDB seeders (120+ documents)"
```

---

## Task 13 : Tests supplémentaires (atteindre ≥ 20 tests)

Tests actuels: ~10 (AuthApiTest + SprintTwoCrudTest)
Après Tasks 4-9: +5 (Odd) +4 (Bet) +3 (Stats) +3 (External) = **~25 tests**

- [ ] **Step 1: Vérifier le total**

```bash
php artisan test --list-tests | wc -l
```

Expected: ≥ 20 tests listés.

- [ ] **Step 2: Si < 20, ajouter des tests dans SprintTwoCrudTest**

Tests à ajouter si besoin :
- `test_guest_can_list_teams`
- `test_guest_can_list_matches`
- `test_guest_can_filter_matches_by_status`
- `test_admin_can_update_match_score`
- `test_unauthenticated_user_cannot_logout`

- [ ] **Step 3: Lancer tous les tests**

```bash
php artisan test
```

Expected: ≥ 20 tests, tous PASS.

- [ ] **Step 4: Commit**

```bash
git add api/tests/
git commit -m "test: reach 20+ tests covering all resources and auth flows"
```

---

## Task 14 : Frontend HTML/JS — Site Web

**Files:** dans `frontend/`
- `index.html` — Accueil : liste des sports et matchs en cours
- `matches.html` — Matchs avec filtres dynamiques (Fetch)
- `login.html` — Login / Inscription
- `my-bets.html` — Mes paris (auth required)
- `admin.html` — Panel admin CRUD
- `assets/css/style.css`
- `assets/js/api.js` — Client API centralisé (baseUrl, token management)
- `assets/js/auth.js`
- `assets/js/matches.js`
- `assets/js/bets.js`
- `assets/js/admin.js`

- [ ] **Step 1: Créer api.js (client API centralisé)**

```javascript
// assets/js/api.js
const API_BASE = 'http://localhost:8000/api/v1';

const Api = {
    getToken: () => localStorage.getItem('token'),
    setToken: (t) => localStorage.setItem('token', t),
    clearToken: () => localStorage.removeItem('token'),

    headers(auth = false) {
        const h = { 'Content-Type': 'application/json', 'Accept': 'application/json' };
        if (auth) h['Authorization'] = `Bearer ${this.getToken()}`;
        return h;
    },

    async get(path, auth = false) {
        const res = await fetch(`${API_BASE}${path}`, { headers: this.headers(auth) });
        if (!res.ok) throw { status: res.status, data: await res.json() };
        return res.json();
    },

    async post(path, body, auth = false) {
        const res = await fetch(`${API_BASE}${path}`, {
            method: 'POST',
            headers: this.headers(auth),
            body: JSON.stringify(body),
        });
        if (!res.ok) throw { status: res.status, data: await res.json() };
        return res.json();
    },

    async delete(path, auth = false) {
        const res = await fetch(`${API_BASE}${path}`, {
            method: 'DELETE',
            headers: this.headers(auth),
        });
        return res.status;
    },
};
```

- [ ] **Step 2: Créer index.html**

Page d'accueil avec :
- Navigation (Matchs, Mes paris, Admin, Login)
- Fetch automatique des sports actifs
- Affichage des prochains matchs (Fetch)
- Design CSS propre avec variables CSS (pas de framework externe obligatoire)

- [ ] **Step 3: Créer matches.html**

- Filtres sport/status (AJAX Fetch #1 — reload sans rechargement page)
- Liste des matchs avec cotes
- Bouton "Parier" pour les matchs à venir (auth required)

- [ ] **Step 4: Créer login.html**

- Formulaire login/register (Fetch #2 — POST async)
- Stockage token dans localStorage
- Redirect après login

- [ ] **Step 5: Créer my-bets.html**

- Vérification auth au chargement
- Fetch des paris de l'utilisateur (Fetch #3)
- Filtres par status (pending/won/lost)
- Affichage ROI personnel

- [ ] **Step 6: Créer admin.html**

- CRUD sports, équipes, matchs via Fetch (Fetch #4+)
- Créer des cotes pour un match
- Résoudre un match (POST /settle)

- [ ] **Step 7: Créer style.css**

Design sobre avec CSS variables :
```css
:root {
  --primary: #1a56db;
  --success: #0e9f6e;
  --danger: #e02424;
  --bg: #f9fafb;
  --card: #ffffff;
  --text: #111827;
  --muted: #6b7280;
}
```

- [ ] **Step 8: Commit**

```bash
git add frontend/
git commit -m "feat: add HTML/CSS/JS frontend with 4+ Fetch functionalities"
```

---

## Task 15 : README et documentation finale

**Files:**
- Create/Modify: `api/README.md`

- [ ] **Step 1: Écrire le README**

Sections :
1. Présentation du projet (paris sportifs API)
2. Prérequis (PHP 8.2, Composer, MongoDB 6+, Git)
3. Installation pas à pas
4. Configuration (.env)
5. Lancer les migrations + seeders
6. Lancer les tests
7. Accéder au Swagger UI
8. Ouvrir le frontend
9. Architecture & design patterns utilisés
10. Endpoints principaux

- [ ] **Step 2: Commit**

```bash
git add api/README.md
git commit -m "docs: add complete README with installation, architecture and API guide"
```

---

## Récapitulatif des commits attendus (15 minimum)

1. `feat: add MongoDB connection via mongodb/laravel-mongodb`
2. `feat: migrate Sport model to MongoDB`
3. `feat: migrate Team and SportMatch models to MongoDB`
4. `feat: add Odd (cotes) resource with CRUD and tests`
5. `feat: add Bet (paris) resource with business logic and tests`
6. `refactor: introduce Repository pattern for Bet, Odd, Sport`
7. `feat: add Strategy pattern for bet settlement on match finish`
8. `feat: add Map Reduce stats endpoints (bets by sport, user performance)`
9. `feat: add ExternalOddsService with CURL/Http and Factory pattern`
10. `feat: configure CORS and rate limiting (60 req/min API, 10 req/min auth)`
11. `feat: add Swagger/OpenAPI documentation via l5-swagger`
12. `feat: add comprehensive MongoDB seeders (120+ documents)`
13. `test: reach 20+ tests covering all resources and auth flows`
14. `feat: add HTML/CSS/JS frontend with 4+ Fetch functionalities`
15. `docs: add complete README with installation, architecture and API guide`

## Ressources couvertes (5/5)
1. Sport, 2. Team, 3. SportMatch, 4. Odd, 5. Bet

## Design Patterns (3/3 min)
1. Repository (BetRepository, OddRepository, SportRepository)
2. Strategy (BetOutcomeStrategy — HomeWin, Draw, AwayWin)
3. Factory (ExternalOddsService::createOddFromExternal)
+ Factory Method de Laravel (model factories) = bonus

## Critères d'évaluation couverts
- [x] 5 ressources avec relations
- [x] ≥ 15 endpoints
- [x] Auth Sanctum (token-based)
- [x] Rôles admin/user/guest
- [x] Pagination + filtrage + tri
- [x] Validation avec messages d'erreur
- [x] Codes HTTP corrects
- [x] Swagger/OpenAPI
- [x] ≥ 20 tests
- [x] MongoDB obligatoire
- [x] Map Reduce (2 opérations)
- [x] Index MongoDB
- [x] CORS + Rate Limiting
- [x] CURL / API externe
- [x] Design patterns (3+)
- [x] Frontend HTML/JS avec Fetch (4+)
- [x] Versioning API (v1)
- [x] ≥ 15 commits

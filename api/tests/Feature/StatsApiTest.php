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

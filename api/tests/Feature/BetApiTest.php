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
        $user  = User::factory()->create(['role' => UserRole::USER->value]);
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
        $user  = User::factory()->create(['role' => UserRole::USER->value]);
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
        Bet::factory()->count(2)->create(['user_id' => '999']);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/bets')
            ->assertOk()
            ->assertJsonPath('total', 3);
    }

    public function test_unauthenticated_user_cannot_place_bet(): void
    {
        $this->postJson('/api/v1/bets', [
            'match_id'          => '1',
            'amount'            => 50,
            'predicted_outcome' => 'home_win',
        ])->assertUnauthorized();
    }
}

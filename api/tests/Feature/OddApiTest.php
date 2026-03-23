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
                'match_id'  => (string) $match->id,
                'home_win'  => 1.85,
                'draw'      => 3.50,
                'away_win'  => 4.20,
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
                'match_id'  => (string) $match->id,
                'home_win'  => 1.85,
                'draw'      => 3.50,
                'away_win'  => 4.20,
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

<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Sport;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SprintTwoCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_list_sports(): void
    {
        Sport::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/sports');

        $response
            ->assertOk()
            ->assertJsonStructure(['data', 'current_page', 'last_page', 'per_page']);
    }

    public function test_non_admin_cannot_create_sport(): void
    {
        $user = User::factory()->create(['role' => UserRole::USER->value]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/sports', [
            'name' => 'Football',
            'slug' => 'football',
            'is_active' => true,
        ]);

        $response->assertForbidden();
    }

    public function test_admin_can_crud_sport(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);

        $createResponse = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/sports', [
            'name' => 'Football',
            'slug' => 'football',
            'is_active' => true,
        ]);

        $createResponse->assertCreated();
        $sportId = $createResponse->json('id');

        $updateResponse = $this->actingAs($admin, 'sanctum')->putJson('/api/v1/sports/'.$sportId, [
            'name' => 'Soccer',
            'slug' => 'soccer',
        ]);

        $updateResponse
            ->assertOk()
            ->assertJsonPath('name', 'Soccer');

        $deleteResponse = $this->actingAs($admin, 'sanctum')->deleteJson('/api/v1/sports/'.$sportId);
        $deleteResponse->assertNoContent();
    }

    public function test_admin_can_create_team_on_a_sport(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $sport = Sport::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/teams', [
            'sport_id' => $sport->id,
            'name' => 'Paris FC',
            'short_name' => 'PFC',
            'country' => 'France',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('sport_id', $sport->id)
            ->assertJsonPath('name', 'Paris FC');
    }

    public function test_admin_can_create_match_when_teams_share_same_sport(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $sport = Sport::factory()->create();
        $home = Team::factory()->create(['sport_id' => $sport->id]);
        $away = Team::factory()->create(['sport_id' => $sport->id]);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/matches', [
            'sport_id' => $sport->id,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'starts_at' => now()->addDay()->toDateTimeString(),
            'status' => 'scheduled',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('sport_id', $sport->id)
            ->assertJsonPath('home_team_id', $home->id)
            ->assertJsonPath('away_team_id', $away->id);
    }

    public function test_match_creation_fails_when_teams_belong_to_different_sports(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $sportA = Sport::factory()->create();
        $sportB = Sport::factory()->create();
        $home = Team::factory()->create(['sport_id' => $sportA->id]);
        $away = Team::factory()->create(['sport_id' => $sportB->id]);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/matches', [
            'sport_id' => $sportA->id,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'starts_at' => now()->addDay()->toDateTimeString(),
            'status' => 'scheduled',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['sport_id']);
    }
}

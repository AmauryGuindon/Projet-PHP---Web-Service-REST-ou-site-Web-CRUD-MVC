<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_receive_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Amaury',
            'email' => 'amaury@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response
            ->assertCreated()
            ->assertJsonStructure([
                'token',
                'token_type',
                'user' => ['id', 'name', 'email', 'role'],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'amaury@example.com',
            'role' => UserRole::USER->value,
        ]);
    }

    public function test_user_can_login_and_receive_token(): void
    {
        User::factory()->create([
            'email' => 'amaury@example.com',
            'password' => 'password123',
            'role' => UserRole::USER->value,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'amaury@example.com',
            'password' => 'password123',
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure(['token', 'token_type', 'user']);
    }

    public function test_authenticated_user_can_read_profile(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::USER->value,
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/me');

        $response
            ->assertOk()
            ->assertJsonPath('user.email', $user->email)
            ->assertJsonPath('user.role', UserRole::USER->value);
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::USER->value,
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/logout');

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Logged out.');
    }

    public function test_non_admin_user_cannot_access_admin_route(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::USER->value,
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->getJson('/api/v1/admin/ping');

        $response
            ->assertForbidden()
            ->assertJsonPath('message', 'Forbidden.');
    }
}


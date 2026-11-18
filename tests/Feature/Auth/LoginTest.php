<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email'    => 'user@example.com',
            'password' => 'password',
        ]);

        $response = $this->postJson('/api/login', [
            'email'    => 'user@example.com',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', 'user@example.com')
            ->assertJsonPath('data.role', 'editor')
            ->assertJsonMissingPath('data.password');

        $this->assertAuthenticatedAs($user);
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email'    => 'user@example.com',
            'password' => 'password',
        ]);

        $response = $this->postJson('/api/login', [
            'email'    => 'user@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('data', null);

        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->inactive()->create([
            'email'    => 'inactive@example.com',
            'password' => 'password',
        ]);

        $response = $this->postJson('/api/login', [
            'email'    => 'inactive@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(401)->assertJsonPath('data', null);

        $this->assertGuest();
    }

    public function test_login_requires_email_and_password(): void
    {
        $response = $this->postJson('/api/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_login_is_rate_limited_after_too_many_attempts(): void
    {
        RateLimiter::clear('user@example.com|127.0.0.1');

        User::factory()->create(['email' => 'user@example.com', 'password' => 'password']);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', [
                'email'    => 'user@example.com',
                'password' => 'wrong-password',
            ]);
        }

        $response = $this->postJson('/api/login', [
            'email'    => 'user@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(429);
    }

    public function test_registration_endpoint_no_longer_exists(): void
    {
        $response = $this->postJson('/api/register', [
            'name'                  => 'New User',
            'email'                 => 'new@example.com',
            'password'              => 'password',
            'password_confirmation' => 'password',
        ]);

        // Since Phase 4's routing restructure, web.php no longer has a
        // universal GET catch-all — /{slug} only matches a single path
        // segment, so "/api/register" (two segments) matches nothing at
        // all now, for any verb. Plain 404, no route handles it either way.
        $response->assertStatus(404);

        $this->assertDatabaseMissing('users', ['email' => 'new@example.com']);
    }

    public function test_authenticated_user_can_fetch_their_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/me');

        $response->assertOk()->assertJsonPath('data.id', $user->id);
    }

    public function test_guest_cannot_fetch_profile(): void
    {
        $response = $this->getJson('/api/me');

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/logout')
            ->assertOk();

        // Asserted against the 'web' guard explicitly: auth:sanctum's
        // Authenticate middleware switches the app's *default* guard to
        // 'sanctum' for the duration of the request, and Sanctum's guard
        // memoizes its result per-instance. In a real request cycle every
        // request re-resolves guards fresh, so this only matters here
        // because Laravel's test harness reuses one container across
        // chained calls — checking 'web' (what AuthService actually logs
        // out) is the accurate assertion.
        $this->assertGuest('web');
    }
}

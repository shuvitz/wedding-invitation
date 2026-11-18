<?php

namespace Tests\Feature\Security;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * config/cors.php (added in Phase B1) replaced Laravel's unpublished,
 * wildcard-origin vendor default with an explicit, scoped policy: only
 * api/public/* is cross-origin at all, and only for configured origins.
 * These tests exercise the actual HTTP behavior, not just the config array,
 * since HandleCors reads config('cors') fresh per request.
 */
class CorsTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_api_reflects_a_configured_origin(): void
    {
        Config::set('cors.allowed_origins', ['https://example-frontend.test']);

        $response = $this->getJson('/api/public/settings', [
            'Origin' => 'https://example-frontend.test',
        ]);

        $response->assertOk();
        $response->assertHeader('Access-Control-Allow-Origin', 'https://example-frontend.test');
    }

    public function test_public_api_does_not_reflect_an_unconfigured_origin(): void
    {
        // Two configured origins (e.g. production + a preview deploy) puts
        // the underlying CORS library into its dynamic-origin-matching
        // path, where it actually compares the request's Origin against
        // the allow-list. With exactly one configured origin, the library
        // instead always emits that single value regardless of the
        // request's Origin — safe (browsers still only honor it for an
        // exact match) but not what this test needs to exercise.
        Config::set('cors.allowed_origins', ['https://example-frontend.test', 'https://staging.example-frontend.test']);

        $response = $this->getJson('/api/public/settings', [
            'Origin' => 'https://not-allowed.test',
        ]);

        $response->assertOk();
        $response->assertHeaderMissing('Access-Control-Allow-Origin');
    }

    public function test_public_api_reflects_the_matching_origin_when_multiple_are_configured(): void
    {
        Config::set('cors.allowed_origins', ['https://example-frontend.test', 'https://staging.example-frontend.test']);

        $response = $this->getJson('/api/public/settings', [
            'Origin' => 'https://staging.example-frontend.test',
        ]);

        $response->assertOk();
        $response->assertHeader('Access-Control-Allow-Origin', 'https://staging.example-frontend.test');
    }

    public function test_public_api_cors_does_not_support_credentials(): void
    {
        Config::set('cors.allowed_origins', ['https://example-frontend.test']);

        $response = $this->getJson('/api/public/settings', [
            'Origin' => 'https://example-frontend.test',
        ]);

        $response->assertHeaderMissing('Access-Control-Allow-Credentials');
    }

    public function test_authenticated_admin_api_is_not_covered_by_the_cors_policy(): void
    {
        Config::set('cors.allowed_origins', ['https://example-frontend.test']);

        $response = $this->getJson('/api/settings', [
            'Origin' => 'https://example-frontend.test',
        ]);

        $response->assertHeaderMissing('Access-Control-Allow-Origin');
    }

    public function test_allowed_origins_default_to_the_local_nuxt_dev_server(): void
    {
        // No CORS_ALLOWED_ORIGINS override in phpunit.xml/.env.testing, so
        // this exercises config/cors.php's actual default value.
        $this->assertSame(['http://localhost:3000'], config('cors.allowed_origins'));
    }

    public function test_cors_paths_only_cover_the_public_api(): void
    {
        $this->assertSame(['api/public/*'], config('cors.paths'));
    }
}

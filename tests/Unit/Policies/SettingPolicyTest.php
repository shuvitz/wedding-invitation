<?php

namespace Tests\Unit\Policies;

use App\Models\Setting;
use App\Models\User;
use App\Policies\SettingPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class SettingPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_bypasses_every_ability_via_gate_before(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue(Gate::forUser($admin)->allows('view', Setting::class));
        $this->assertTrue(Gate::forUser($admin)->allows('update', Setting::class));
    }

    public function test_editor_can_view_settings(): void
    {
        $editor = User::factory()->create();

        $this->assertTrue((new SettingPolicy)->view($editor));
    }

    public function test_editor_cannot_update_settings(): void
    {
        $editor = User::factory()->create();

        $this->assertFalse((new SettingPolicy)->update($editor));
    }

    public function test_policy_is_auto_discovered_through_the_gate(): void
    {
        $editor = User::factory()->create();

        $this->assertTrue(Gate::forUser($editor)->allows('view', Setting::class));
        $this->assertTrue(Gate::forUser($editor)->denies('update', Setting::class));
    }
}

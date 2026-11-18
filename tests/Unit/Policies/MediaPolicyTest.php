<?php

namespace Tests\Unit\Policies;

use App\Models\Media;
use App\Models\User;
use App\Policies\MediaPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class MediaPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_bypasses_every_ability_via_gate_before(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue(Gate::forUser($admin)->allows('viewAny', Media::class));
        $this->assertTrue(Gate::forUser($admin)->allows('create', Media::class));
    }

    public function test_editor_can_view_media(): void
    {
        $editor = User::factory()->create();
        $media = Media::factory()->create();

        $this->assertTrue((new MediaPolicy)->viewAny($editor));
        $this->assertTrue((new MediaPolicy)->view($editor, $media));
    }

    public function test_editor_cannot_create_update_or_delete_media(): void
    {
        $editor = User::factory()->create();
        $media = Media::factory()->create();

        $policy = new MediaPolicy;

        $this->assertFalse($policy->create($editor));
        $this->assertFalse($policy->update($editor, $media));
        $this->assertFalse($policy->delete($editor, $media));
    }

    public function test_policy_is_auto_discovered_through_the_gate(): void
    {
        $editor = User::factory()->create();
        $media = Media::factory()->create();

        $this->assertTrue(Gate::forUser($editor)->denies('delete', $media));
    }
}

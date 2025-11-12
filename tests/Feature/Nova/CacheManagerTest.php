<?php

namespace Tests\Feature\Nova;

use App\CacheManager\CacheManager;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CacheManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->editor = User::factory()->create(['role' => 'editor']);
    }

    public function test_admin_can_see_cache_manager_tool(): void
    {
        $tool = new CacheManager;
        $request = Request::create('/');
        $request->setUserResolver(fn () => $this->admin);

        $this->assertTrue($tool->authorize($request));
    }

    public function test_non_admin_cannot_see_cache_manager_tool(): void
    {
        $tool = new CacheManager;
        $request = Request::create('/');
        $request->setUserResolver(fn () => $this->editor);

        $this->assertFalse($tool->authorize($request));
    }

    public function test_unauthenticated_user_cannot_see_cache_manager_tool(): void
    {
        $tool = new CacheManager;
        $request = Request::create('/');
        $request->setUserResolver(fn () => null);

        $this->assertFalse($tool->authorize($request));
    }

    public function test_admin_can_clear_application_cache(): void
    {
        Cache::put('test_key', 'test_value', 3600);
        $this->assertTrue(Cache::has('test_key'));

        $response = $this->actingAs($this->admin)
            ->postJson('/nova-vendor/cache-manager/clear/application', ['type' => 'application']);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $this->assertFalse(Cache::has('test_key'));
    }

    public function test_admin_can_clear_config_cache(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/nova-vendor/cache-manager/clear/config', ['type' => 'config']);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
    }

    public function test_admin_can_clear_route_cache(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/nova-vendor/cache-manager/clear/route', ['type' => 'route']);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
    }

    public function test_admin_can_clear_view_cache(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/nova-vendor/cache-manager/clear/view', ['type' => 'view']);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
    }

    public function test_admin_can_clear_event_cache(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/nova-vendor/cache-manager/clear/event', ['type' => 'event']);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
    }

    public function test_admin_can_clear_optimize_cache(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/nova-vendor/cache-manager/clear/optimize', ['type' => 'optimize']);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
    }

    public function test_admin_can_clear_all_caches(): void
    {
        Cache::put('test_key', 'test_value', 3600);

        $response = $this->actingAs($this->admin)
            ->postJson('/nova-vendor/cache-manager/clear-all');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $this->assertFalse(Cache::has('test_key'));
    }

    public function test_cache_clearing_stores_timestamp(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/nova-vendor/cache-manager/clear/application', ['type' => 'application']);

        $response->assertStatus(200);

        $timestamp = Cache::store('file')->get('cache_manager:last_cleared:application');
        $this->assertNotNull($timestamp);
        $this->assertIsString($timestamp);
    }

    public function test_admin_can_get_timestamps(): void
    {
        Cache::store('file')->put('cache_manager:last_cleared:application', now()->toIso8601String(), 3600);
        Cache::store('file')->put('cache_manager:last_cleared:config', now()->toIso8601String(), 3600);

        $response = $this->actingAs($this->admin)
            ->getJson('/nova-vendor/cache-manager/timestamps');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'timestamps' => [
                'application',
                'config',
                'route',
                'view',
                'event',
                'optimize',
            ],
        ]);
    }

    public function test_non_admin_cannot_clear_cache(): void
    {
        $response = $this->actingAs($this->editor)
            ->postJson('/nova-vendor/cache-manager/clear/application', ['type' => 'application']);

        $response->assertStatus(403);
    }

    public function test_non_admin_cannot_clear_all_caches(): void
    {
        $response = $this->actingAs($this->editor)
            ->postJson('/nova-vendor/cache-manager/clear-all');

        $response->assertStatus(403);
    }

    public function test_non_admin_cannot_get_timestamps(): void
    {
        $response = $this->actingAs($this->editor)
            ->getJson('/nova-vendor/cache-manager/timestamps');

        $response->assertStatus(403);
    }

    public function test_invalid_cache_type_returns_validation_error(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/nova-vendor/cache-manager/clear/invalid', ['type' => 'invalid']);

        $response->assertStatus(422);
    }

    public function test_clear_all_stores_timestamps_for_all_types(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/nova-vendor/cache-manager/clear-all');

        $response->assertStatus(200);

        $types = ['application', 'config', 'route', 'view', 'event', 'optimize'];
        foreach ($types as $type) {
            $timestamp = Cache::store('file')->get("cache_manager:last_cleared:{$type}");
            $this->assertNotNull($timestamp, "Timestamp for {$type} should be stored");
        }
    }
}

<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\PerformanceMetricsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PerformanceControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_performance_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.performance'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.performance.index');
        $response->assertViewHas(['pageLoads', 'slowQueries', 'cacheStats', 'memory']);
    }

    public function test_editor_can_access_performance_dashboard(): void
    {
        $editor = User::factory()->create(['role' => 'editor']);

        $response = $this->actingAs($editor)->get(route('admin.performance'));

        $response->assertStatus(200);
    }

    public function test_author_cannot_access_performance_dashboard(): void
    {
        $author = User::factory()->create(['role' => 'author']);

        $response = $this->actingAs($author)->get(route('admin.performance'));

        $response->assertStatus(403);
    }

    public function test_guest_cannot_access_performance_dashboard(): void
    {
        $response = $this->get(route('admin.performance'));

        $response->assertRedirect(route('login'));
    }

    public function test_performance_dashboard_displays_metrics(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Add some test data
        $service = app(PerformanceMetricsService::class);
        $service->trackPageLoad('home', 150.5);
        $service->logSlowQuery('SELECT * FROM posts', 150.0, []);
        $service->trackCacheHit(true);

        $response = $this->actingAs($admin)->get(route('admin.performance'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.performance.index');

        // Verify the view has the correct data
        $viewData = $response->viewData('pageLoads');
        $this->assertIsArray($viewData);
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }
}

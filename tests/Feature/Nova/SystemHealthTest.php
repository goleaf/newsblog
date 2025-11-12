<?php

namespace Tests\Feature\Nova;

use App\Models\User;
use App\SystemHealth\SystemHealth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class SystemHealthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->editor = User::factory()->create(['role' => 'editor']);
    }

    public function test_admin_can_see_system_health_tool(): void
    {
        $tool = new SystemHealth;
        $request = Request::create('/');
        $request->setUserResolver(fn () => $this->admin);

        $this->assertTrue($tool->authorize($request));
    }

    public function test_non_admin_cannot_see_system_health_tool(): void
    {
        $tool = new SystemHealth;
        $request = Request::create('/');
        $request->setUserResolver(fn () => $this->editor);

        $this->assertFalse($tool->authorize($request));
    }

    public function test_unauthenticated_user_cannot_see_system_health_tool(): void
    {
        $tool = new SystemHealth;
        $request = Request::create('/');
        $request->setUserResolver(fn () => null);

        $this->assertFalse($tool->authorize($request));
    }

    public function test_admin_can_get_system_health_status(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/nova-vendor/system-health/status');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'database' => [
                    'connected',
                    'message',
                    'driver',
                ],
                'queue' => [
                    'driver',
                    'failed_jobs',
                    'pending_jobs',
                    'status',
                ],
                'storage' => [
                    'total',
                    'used',
                    'free',
                    'used_percentage',
                    'status',
                ],
                'errors' => [
                    'count',
                    'errors',
                ],
            ],
        ]);
    }

    public function test_non_admin_cannot_get_system_health_status(): void
    {
        $response = $this->actingAs($this->editor)
            ->getJson('/nova-vendor/system-health/status');

        $response->assertStatus(403);
    }

    public function test_database_status_shows_connected(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/nova-vendor/system-health/status');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'database' => [
                    'connected' => true,
                    'message' => 'Connected',
                ],
            ],
        ]);
    }

    public function test_queue_status_includes_failed_jobs_count(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/nova-vendor/system-health/status');

        $response->assertStatus(200);
        $data = $response->json('data.queue');

        $this->assertArrayHasKey('failed_jobs', $data);
        $this->assertIsInt($data['failed_jobs']);
        $this->assertGreaterThanOrEqual(0, $data['failed_jobs']);
    }

    public function test_storage_status_includes_usage_percentage(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/nova-vendor/system-health/status');

        $response->assertStatus(200);
        $data = $response->json('data.storage');

        $this->assertArrayHasKey('used_percentage', $data);
        $this->assertIsNumeric($data['used_percentage']);
        $this->assertGreaterThanOrEqual(0, $data['used_percentage']);
        $this->assertLessThanOrEqual(100, $data['used_percentage']);
    }

    public function test_errors_status_includes_error_count(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/nova-vendor/system-health/status');

        $response->assertStatus(200);
        $data = $response->json('data.errors');

        $this->assertArrayHasKey('count', $data);
        $this->assertArrayHasKey('errors', $data);
        $this->assertIsInt($data['count']);
        $this->assertIsArray($data['errors']);
    }
}

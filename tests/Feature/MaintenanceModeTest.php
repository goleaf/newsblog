<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MaintenanceModeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure maintenance mode is disabled before each test
        $this->disableMaintenanceMode();
    }

    protected function tearDown(): void
    {
        // Clean up maintenance mode after each test
        $this->disableMaintenanceMode();

        parent::tearDown();
    }

    private function enableMaintenanceMode(array $options = []): array
    {
        $downData = array_merge([
            'time' => now()->timestamp,
            'retry' => 60,
            'secret' => 'test-secret-token',
            'message' => 'We are currently performing maintenance.',
            'allowed' => [],
        ], $options);

        File::put(storage_path('framework/down'), json_encode($downData, JSON_PRETTY_PRINT));

        return $downData;
    }

    private function disableMaintenanceMode(): void
    {
        $file = storage_path('framework/down');
        if (File::exists($file)) {
            File::delete($file);
        }
    }

    public function test_maintenance_mode_displays_503_page_to_visitors(): void
    {
        $this->enableMaintenanceMode();

        $response = $this->get('/');

        $response->assertStatus(503);
        $response->assertSee('We\'ll be back soon!');
    }

    public function test_maintenance_mode_includes_retry_after_header(): void
    {
        $this->enableMaintenanceMode(['retry' => 120]);

        $response = $this->get('/');

        $response->assertStatus(503);
        $response->assertHeader('Retry-After', '120');
    }

    public function test_admin_users_bypass_maintenance_mode(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->enableMaintenanceMode();

        $response = $this->actingAs($admin)->get('/');

        $response->assertStatus(200);
    }

    public function test_whitelisted_ip_bypasses_maintenance_mode(): void
    {
        $this->enableMaintenanceMode(['allowed' => ['127.0.0.1']]);

        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_secret_token_in_url_bypasses_maintenance_mode(): void
    {
        $downData = $this->enableMaintenanceMode(['secret' => 'my-secret-token']);

        $response = $this->get('/my-secret-token');

        $response->assertRedirect('/');
        $response->assertCookie('laravel_maintenance', 'my-secret-token');
    }

    public function test_secret_token_in_cookie_bypasses_maintenance_mode(): void
    {
        $this->enableMaintenanceMode(['secret' => 'my-secret-token']);

        $response = $this->withCookie('laravel_maintenance', 'my-secret-token')->get('/');

        $response->assertStatus(200);
    }

    public function test_can_enable_maintenance_mode_via_controller(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->postJson('/admin/maintenance/enable', [
            'message' => 'Custom maintenance message',
            'retry_after' => 300,
            'allowed_ips' => ['192.168.1.1'],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertTrue(File::exists(storage_path('framework/down')));

        $downData = json_decode(File::get(storage_path('framework/down')), true);
        $this->assertEquals('Custom maintenance message', $downData['message']);
        $this->assertEquals(300, $downData['retry']);
        $this->assertEquals(['192.168.1.1'], $downData['allowed']);
    }

    public function test_can_disable_maintenance_mode_via_controller(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->enableMaintenanceMode();

        $response = $this->actingAs($admin)->postJson('/admin/maintenance/disable');

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertFalse(File::exists(storage_path('framework/down')));
    }

    public function test_can_update_maintenance_mode_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->enableMaintenanceMode();

        $response = $this->actingAs($admin)->postJson('/admin/maintenance/update', [
            'message' => 'Updated message',
            'retry_after' => 180,
            'allowed_ips' => ['10.0.0.1', '10.0.0.2'],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $downData = json_decode(File::get(storage_path('framework/down')), true);
        $this->assertEquals('Updated message', $downData['message']);
        $this->assertEquals(180, $downData['retry']);
        $this->assertEquals(['10.0.0.1', '10.0.0.2'], $downData['allowed']);
    }

    public function test_can_regenerate_secret_token(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $downData = $this->enableMaintenanceMode(['secret' => 'old-secret']);

        $response = $this->actingAs($admin)->postJson('/admin/maintenance/regenerate-secret');

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $newDownData = json_decode(File::get(storage_path('framework/down')), true);
        $this->assertNotEquals('old-secret', $newDownData['secret']);
        $this->assertNotEmpty($newDownData['secret']);
    }

    public function test_maintenance_mode_returns_json_for_api_requests(): void
    {
        $this->enableMaintenanceMode(['message' => 'API maintenance']);

        $response = $this->getJson('/api/posts');

        $response->assertStatus(503);
        $response->assertJson([
            'message' => 'API maintenance',
        ]);
        $response->assertHeader('Retry-After');
    }

    public function test_non_admin_users_cannot_access_maintenance_management(): void
    {
        $user = User::factory()->create(['role' => 'author']);

        $response = $this->actingAs($user)->get('/admin/maintenance');

        $response->assertStatus(403);
    }

    public function test_maintenance_page_displays_custom_message(): void
    {
        $this->enableMaintenanceMode(['message' => 'Custom maintenance message for testing']);

        $response = $this->get('/');

        $response->assertStatus(503);
        $response->assertSee('Custom maintenance message for testing');
    }
}

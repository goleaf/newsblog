<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MaintenanceModeToolTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->file = storage_path('framework/down');

        // Disable maintenance mode middleware for tests
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance::class);
    }

    protected function tearDown(): void
    {
        // Clean up maintenance mode file after each test
        if (File::exists($this->file)) {
            File::delete($this->file);
        }
        parent::tearDown();
    }

    public function test_admin_can_access_maintenance_mode_status(): void
    {
        $response = $this->actingAs($this->admin, 'web')
            ->getJson('/nova-vendor/maintenance-mode/status');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'enabled' => false,
            ]);
    }

    public function test_non_admin_cannot_access_maintenance_mode_status(): void
    {
        $editor = User::factory()->create(['role' => 'editor']);

        $response = $this->actingAs($editor, 'web')
            ->getJson('/nova-vendor/maintenance-mode/status');

        $response->assertStatus(403);
    }

    public function test_admin_can_enable_maintenance_mode(): void
    {
        $response = $this->actingAs($this->admin, 'web')
            ->postJson('/nova-vendor/maintenance-mode/toggle', [
                'enabled' => true,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'enabled' => true,
            ]);

        $this->assertTrue(File::exists($this->file));
        $downFile = json_decode(File::get($this->file), true);
        $this->assertArrayHasKey('time', $downFile);
        $this->assertArrayHasKey('secret', $downFile);
        $this->assertArrayHasKey('allowed', $downFile);
    }

    public function test_admin_can_disable_maintenance_mode(): void
    {
        // Enable maintenance mode first
        File::put($this->file, json_encode([
            'time' => now()->timestamp,
            'retry' => 60,
            'secret' => bin2hex(random_bytes(16)),
            'message' => 'Test message',
            'allowed' => [],
        ], JSON_PRETTY_PRINT));

        $response = $this->actingAs($this->admin, 'web')
            ->postJson('/nova-vendor/maintenance-mode/toggle', [
                'enabled' => false,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'enabled' => false,
            ]);

        $this->assertFalse(File::exists($this->file));
    }

    public function test_admin_can_update_maintenance_message(): void
    {
        // Enable maintenance mode first
        File::put($this->file, json_encode([
            'time' => now()->timestamp,
            'retry' => 60,
            'secret' => bin2hex(random_bytes(16)),
            'message' => 'Old message',
            'allowed' => [],
        ], JSON_PRETTY_PRINT));

        $response = $this->actingAs($this->admin, 'web')
            ->postJson('/nova-vendor/maintenance-mode/message', [
                'message' => 'New maintenance message',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonPath('data.message', 'New maintenance message');

        $downFile = json_decode(File::get($this->file), true);
        $this->assertEquals('New maintenance message', $downFile['message']);
    }

    public function test_cannot_update_message_when_maintenance_mode_disabled(): void
    {
        $response = $this->actingAs($this->admin, 'web')
            ->postJson('/nova-vendor/maintenance-mode/message', [
                'message' => 'Test message',
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_admin_can_update_ip_whitelist(): void
    {
        // Enable maintenance mode first
        File::put($this->file, json_encode([
            'time' => now()->timestamp,
            'retry' => 60,
            'secret' => bin2hex(random_bytes(16)),
            'message' => 'Test message',
            'allowed' => [],
        ], JSON_PRETTY_PRINT));

        $response = $this->actingAs($this->admin, 'web')
            ->postJson('/nova-vendor/maintenance-mode/ip-whitelist', [
                'ips' => ['127.0.0.1', '192.168.1.1'],
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonPath('data.allowed', ['127.0.0.1', '192.168.1.1']);

        $downFile = json_decode(File::get($this->file), true);
        $this->assertEquals(['127.0.0.1', '192.168.1.1'], $downFile['allowed']);
    }

    public function test_cannot_update_ip_whitelist_when_maintenance_mode_disabled(): void
    {
        $response = $this->actingAs($this->admin, 'web')
            ->postJson('/nova-vendor/maintenance-mode/ip-whitelist', [
                'ips' => ['127.0.0.1'],
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_ip_whitelist_validation_requires_valid_ips(): void
    {
        // Enable maintenance mode first
        File::put($this->file, json_encode([
            'time' => now()->timestamp,
            'retry' => 60,
            'secret' => bin2hex(random_bytes(16)),
            'message' => 'Test message',
            'allowed' => [],
        ], JSON_PRETTY_PRINT));

        $response = $this->actingAs($this->admin, 'web')
            ->postJson('/nova-vendor/maintenance-mode/ip-whitelist', [
                'ips' => ['invalid-ip', '127.0.0.1'],
            ]);

        $response->assertStatus(422);
    }

    public function test_message_validation_requires_string(): void
    {
        // Enable maintenance mode first
        File::put($this->file, json_encode([
            'time' => now()->timestamp,
            'retry' => 60,
            'secret' => bin2hex(random_bytes(16)),
            'message' => 'Test message',
            'allowed' => [],
        ], JSON_PRETTY_PRINT));

        $response = $this->actingAs($this->admin, 'web')
            ->postJson('/nova-vendor/maintenance-mode/message', [
                'message' => 12345,
            ]);

        $response->assertStatus(422);
    }

    public function test_toggle_validation_requires_boolean(): void
    {
        $response = $this->actingAs($this->admin, 'web')
            ->postJson('/nova-vendor/maintenance-mode/toggle', [
                'enabled' => 'not-a-boolean',
            ]);

        $response->assertStatus(422);
    }

    public function test_status_returns_current_maintenance_mode_state(): void
    {
        // Enable maintenance mode with custom data
        File::put($this->file, json_encode([
            'time' => 1234567890,
            'retry' => 60,
            'secret' => 'test-secret',
            'message' => 'Custom maintenance message',
            'allowed' => ['127.0.0.1', '192.168.1.1'],
        ], JSON_PRETTY_PRINT));

        $response = $this->actingAs($this->admin, 'web')
            ->getJson('/nova-vendor/maintenance-mode/status');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'enabled' => true,
                'message' => 'Custom maintenance message',
                'allowed' => ['127.0.0.1', '192.168.1.1'],
                'time' => 1234567890,
            ]);
    }

    public function test_maintenance_mode_preserves_existing_data_when_toggling(): void
    {
        // Enable with custom message and IPs
        File::put($this->file, json_encode([
            'time' => now()->timestamp,
            'retry' => 60,
            'secret' => 'existing-secret',
            'message' => 'Existing message',
            'allowed' => ['127.0.0.1'],
        ], JSON_PRETTY_PRINT));

        // Disable
        $this->actingAs($this->admin, 'web')
            ->postJson('/nova-vendor/maintenance-mode/toggle', [
                'enabled' => false,
            ]);

        // Re-enable
        $response = $this->actingAs($this->admin, 'web')
            ->postJson('/nova-vendor/maintenance-mode/toggle', [
                'enabled' => true,
            ]);

        $response->assertStatus(200);

        // Check that new secret was generated (since file was deleted)
        $downFile = json_decode(File::get($this->file), true);
        $this->assertNotEquals('existing-secret', $downFile['secret']);
    }
}

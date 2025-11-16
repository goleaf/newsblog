<?php

namespace Tests\Feature\Nova;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SystemHealthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.connections', [
            'sqlite' => config('database.connections.sqlite'),
        ]);

        Config::set('queue.connections', [
            'sync' => ['driver' => 'sync'],
        ]);

        Config::set('filesystems.disks', [
            'local' => config('filesystems.disks.local'),
        ]);

        $logPath = storage_path('logs/laravel.log');
        if (File::exists($logPath)) {
            File::delete($logPath);
        }
    }

    public function test_returns_system_health_payload(): void
    {
        $response = $this->getJson('/api/nova-api/system-health');

        $response->assertOk()
            ->assertJsonStructure([
                'databases' => ['sqlite'],
                'queues' => ['sync'],
                'storage' => ['local'],
                'errors',
                'timestamp',
            ]);

        $payload = $response->json();

        $this->assertEquals('connected', $payload['databases']['sqlite']['status']);
        $this->assertEquals('active', $payload['queues']['sync']['status']);
        $this->assertEquals('accessible', $payload['storage']['local']['status']);
        $this->assertIsArray($payload['errors']);
    }

    public function test_logs_are_included_when_errors_present(): void
    {
        $logPath = storage_path('logs/laravel.log');
        File::ensureDirectoryExists(dirname($logPath));
        File::put($logPath, '[2025-11-10 12:00:00] local.ERROR: Sample error message'.PHP_EOL);

        $response = $this->getJson('/api/nova-api/system-health');

        $response->assertOk();

        $payload = $response->json();
        $this->assertNotEmpty($payload['errors']);
        $this->assertSame('ERROR', $payload['errors'][0]['level']);
        $this->assertStringContainsString('Sample error message', $payload['errors'][0]['message']);
    }
}


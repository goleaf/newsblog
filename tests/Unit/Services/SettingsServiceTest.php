<?php

namespace Tests\Unit\Services;

use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SettingsServiceTest extends TestCase
{
    use RefreshDatabase;

    private SettingsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SettingsService::class);
    }

    public function test_get_and_set_with_caching(): void
    {
        $this->service->set('site_name', 'My Site', 'general');
        $value = $this->service->get('site_name');
        $this->assertSame('My Site', $value);

        $this->assertTrue(Cache::has('setting_site_name'));
    }

    public function test_cache_invalidated_on_update(): void
    {
        $this->service->set('site_name', 'Old', 'general');
        $this->service->get('site_name'); // prime cache
        $this->assertTrue(Cache::has('setting_site_name'));

        $this->service->set('site_name', 'New', 'general');
        $this->assertFalse(Cache::has('setting_site_name'));
        $this->assertSame('New', $this->service->get('site_name'));
    }

    public function test_get_group_returns_key_value_array(): void
    {
        $this->service->set('k1', 'v1', 'general');
        $this->service->set('k2', 'v2', 'general');

        $group = $this->service->getGroup('general');
        $this->assertSame('v1', $group['k1']);
        $this->assertSame('v2', $group['k2']);
    }

    public function test_type_validation_enforced(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->set('admin_email', 'not-an-email', 'email');
    }
}

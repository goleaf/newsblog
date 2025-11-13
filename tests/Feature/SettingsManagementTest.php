<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SettingsManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_setting_can_be_created_and_retrieved(): void
    {
        $setting = Setting::set('test_key', 'test_value', 'general');

        $this->assertDatabaseHas('settings', [
            'key' => 'test_key',
            'value' => 'test_value',
            'group' => 'general',
        ]);

        $this->assertEquals('test_value', Setting::get('test_key'));
    }

    public function test_setting_returns_default_when_not_found(): void
    {
        $value = Setting::get('non_existent_key', 'default_value');

        $this->assertEquals('default_value', $value);
    }

    public function test_setting_is_cached(): void
    {
        Setting::set('cached_key', 'cached_value');

        // First call should cache
        Setting::get('cached_key');

        // Check cache exists
        $this->assertTrue(Cache::has('setting_cached_key'));
    }

    public function test_cache_is_cleared_when_setting_is_updated(): void
    {
        Setting::set('update_key', 'original_value');
        Setting::get('update_key'); // Cache it

        Setting::set('update_key', 'updated_value');

        // Cache should be cleared
        $this->assertFalse(Cache::has('setting_update_key'));
    }

    public function test_settings_can_be_retrieved_by_group(): void
    {
        Setting::set('general_1', 'value1', 'general');
        Setting::set('general_2', 'value2', 'general');
        Setting::set('email_1', 'value3', 'email');

        $generalSettings = Setting::getByGroup('general');

        $this->assertCount(2, $generalSettings);
        $this->assertEquals('value1', $generalSettings['general_1']);
        $this->assertEquals('value2', $generalSettings['general_2']);
    }

    public function test_multiple_settings_can_be_set_at_once(): void
    {
        Setting::setMany([
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        ], 'general');

        $this->assertEquals('value1', Setting::get('key1'));
        $this->assertEquals('value2', Setting::get('key2'));
        $this->assertEquals('value3', Setting::get('key3'));
    }

    public function test_setting_existence_can_be_checked(): void
    {
        Setting::set('existing_key', 'value');

        $this->assertTrue(Setting::has('existing_key'));
        $this->assertFalse(Setting::has('non_existing_key'));
    }

    public function test_all_cache_can_be_cleared(): void
    {
        Setting::set('key1', 'value1', 'general');
        Setting::set('key2', 'value2', 'email');

        Setting::get('key1');
        Setting::get('key2');

        Setting::clearAllCache();

        $this->assertFalse(Cache::has('setting_key1'));
        $this->assertFalse(Cache::has('setting_key2'));
        $this->assertFalse(Cache::has('settings_all'));
    }

    public function test_admin_can_access_settings_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.settings.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.settings.index');
    }

    public function test_non_admin_cannot_access_settings_page(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)->get(route('admin.settings.index'));

        $response->assertStatus(403);
    }

    public function test_admin_can_update_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->put(route('admin.settings.update'), [
            'group' => 'general',
            'settings' => [
                'site_name' => 'Updated Site Name',
                'posts_per_page' => '20',
            ],
        ]);

        $response->assertRedirect(route('admin.settings.index'));
        $response->assertSessionHas('success');

        $this->assertEquals('Updated Site Name', Setting::get('site_name'));
        $this->assertEquals('20', Setting::get('posts_per_page'));
    }

    public function test_helper_function_retrieves_setting(): void
    {
        Setting::set('helper_test', 'helper_value');

        $this->assertEquals('helper_value', setting('helper_test'));
        $this->assertEquals('default', setting('non_existent', 'default'));
    }
}

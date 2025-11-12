<?php

namespace Tests\Feature\Nova;

use App\Models\Setting;
use App\Models\User;
use App\Nova\Setting as SettingResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Nova\Http\Requests\NovaRequest;
use Tests\TestCase;

class SettingResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_setting_resource_has_correct_model(): void
    {
        $this->assertEquals(\App\Models\Setting::class, SettingResource::$model);
    }

    public function test_setting_resource_has_correct_title(): void
    {
        $this->assertEquals('key', SettingResource::$title);
    }

    public function test_setting_resource_has_correct_search_fields(): void
    {
        $expected = ['id', 'key', 'value', 'group'];
        $this->assertEquals($expected, SettingResource::$search);
    }

    public function test_admin_can_view_any_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $request = NovaRequest::create('/nova-api/settings', 'GET');
        $request->setUserResolver(fn () => $admin);

        $this->assertTrue(SettingResource::authorizedToViewAny($request));
    }

    public function test_editor_cannot_view_any_settings(): void
    {
        $editor = User::factory()->create(['role' => 'editor']);
        $request = NovaRequest::create('/nova-api/settings', 'GET');
        $request->setUserResolver(fn () => $editor);

        $this->assertFalse(SettingResource::authorizedToViewAny($request));
    }

    public function test_author_cannot_view_any_settings(): void
    {
        $author = User::factory()->create(['role' => 'author']);
        $request = NovaRequest::create('/nova-api/settings', 'GET');
        $request->setUserResolver(fn () => $author);

        $this->assertFalse(SettingResource::authorizedToViewAny($request));
    }

    public function test_admin_can_create_setting(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $request = NovaRequest::create('/nova-api/settings', 'POST');
        $request->setUserResolver(fn () => $admin);

        $this->assertTrue(SettingResource::authorizedToCreate($request));
    }

    public function test_editor_cannot_create_setting(): void
    {
        $editor = User::factory()->create(['role' => 'editor']);
        $request = NovaRequest::create('/nova-api/settings', 'POST');
        $request->setUserResolver(fn () => $editor);

        $this->assertFalse(SettingResource::authorizedToCreate($request));
    }

    public function test_setting_resource_has_fields(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $setting = Setting::factory()->create();
        $resource = new SettingResource($setting);

        $request = NovaRequest::create('/nova-api/settings', 'GET');
        $request->setUserResolver(fn () => $admin);

        $fields = $resource->fields($request);

        $this->assertNotEmpty($fields);
        $this->assertGreaterThanOrEqual(6, count($fields));
    }

    public function test_setting_index_query_orders_by_group_and_key(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Setting::factory()->create(['key' => 'site_name', 'group' => 'general']);
        Setting::factory()->create(['key' => 'smtp_host', 'group' => 'email']);
        Setting::factory()->create(['key' => 'facebook_url', 'group' => 'social']);

        $request = NovaRequest::create('/nova-api/settings', 'GET');
        $request->setUserResolver(fn () => $admin);

        $query = SettingResource::indexQuery($request, Setting::query());
        $settings = $query->get();

        $this->assertEquals('email', $settings->first()->group);
        $this->assertEquals('social', $settings->last()->group);
    }

    public function test_admin_can_update_setting(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $setting = Setting::factory()->create();
        $resource = new SettingResource($setting);

        $request = NovaRequest::create('/nova-api/settings/1', 'PUT');
        $request->setUserResolver(fn () => $admin);

        $this->assertTrue($resource->authorizedToUpdate($request));
    }

    public function test_editor_cannot_update_setting(): void
    {
        $editor = User::factory()->create(['role' => 'editor']);
        $setting = Setting::factory()->create();
        $resource = new SettingResource($setting);

        $request = NovaRequest::create('/nova-api/settings/1', 'PUT');
        $request->setUserResolver(fn () => $editor);

        $this->assertFalse($resource->authorizedToUpdate($request));
    }

    public function test_admin_can_delete_setting(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $setting = Setting::factory()->create();
        $resource = new SettingResource($setting);

        $request = NovaRequest::create('/nova-api/settings/1', 'DELETE');
        $request->setUserResolver(fn () => $admin);

        $this->assertTrue($resource->authorizedToDelete($request));
    }

    public function test_editor_cannot_delete_setting(): void
    {
        $editor = User::factory()->create(['role' => 'editor']);
        $setting = Setting::factory()->create();
        $resource = new SettingResource($setting);

        $request = NovaRequest::create('/nova-api/settings/1', 'DELETE');
        $request->setUserResolver(fn () => $editor);

        $this->assertFalse($resource->authorizedToDelete($request));
    }

    public function test_admin_can_view_setting(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $setting = Setting::factory()->create();
        $resource = new SettingResource($setting);

        $request = NovaRequest::create('/nova-api/settings/1', 'GET');
        $request->setUserResolver(fn () => $admin);

        $this->assertTrue($resource->authorizedToView($request));
    }

    public function test_key_field_is_readonly_after_creation(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $setting = Setting::factory()->create(['key' => 'test_key']);
        $resource = new SettingResource($setting);

        $request = NovaRequest::create('/nova-api/settings/1', 'GET');
        $request->setUserResolver(fn () => $admin);

        $fields = $resource->fields($request);
        $keyField = collect($fields)->first(fn ($field) => $field->attribute === 'key');

        $this->assertNotNull($keyField);
        $this->assertTrue($keyField->isReadonly($request));
    }

    public function test_group_field_exists_and_is_select(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $setting = Setting::factory()->create();
        $resource = new SettingResource($setting);

        $request = NovaRequest::create('/nova-api/settings', 'GET');
        $request->setUserResolver(fn () => $admin);

        $fields = $resource->fields($request);
        $groupField = collect($fields)->first(fn ($field) => $field->attribute === 'group');

        $this->assertNotNull($groupField);
        $this->assertInstanceOf(\Laravel\Nova\Fields\Select::class, $groupField);
    }

    public function test_cache_is_cleared_when_setting_is_updated(): void
    {
        $setting = Setting::factory()->create(['key' => 'test_setting', 'value' => 'old_value']);

        Cache::put("setting_{$setting->key}", 'cached_value', 3600);
        $this->assertEquals('cached_value', Cache::get("setting_{$setting->key}"));

        $setting->update(['value' => 'new_value']);

        $this->assertNull(Cache::get("setting_{$setting->key}"));
    }

    public function test_cache_is_cleared_when_setting_is_deleted(): void
    {
        $setting = Setting::factory()->create(['key' => 'test_setting']);

        Cache::put("setting_{$setting->key}", 'cached_value', 3600);
        $this->assertEquals('cached_value', Cache::get("setting_{$setting->key}"));

        $setting->delete();

        $this->assertNull(Cache::get("setting_{$setting->key}"));
    }
}

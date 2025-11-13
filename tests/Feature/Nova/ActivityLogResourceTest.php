<?php

namespace Tests\Feature\Nova;

use App\Models\ActivityLog;
use App\Models\Post;
use App\Models\User;
use App\Nova\ActivityLog as ActivityLogResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Nova\Http\Requests\NovaRequest;
use Tests\TestCase;

class ActivityLogResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_log_resource_has_correct_model(): void
    {
        $this->assertEquals(\App\Models\ActivityLog::class, ActivityLogResource::$model);
    }

    public function test_activity_log_resource_has_correct_title(): void
    {
        $this->assertEquals('description', ActivityLogResource::$title);
    }

    public function test_activity_log_resource_has_correct_search_fields(): void
    {
        $expected = ['id', 'description', 'log_name'];
        $this->assertEquals($expected, ActivityLogResource::$search);
    }

    public function test_admin_can_view_any_activity_logs(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $request = NovaRequest::create('/nova-api/activity-logs', 'GET');
        $request->setUserResolver(fn () => $admin);

        $this->assertTrue(ActivityLogResource::authorizedToViewAny($request));
    }

    public function test_editor_can_view_any_activity_logs(): void
    {
        $editor = User::factory()->create(['role' => 'editor']);
        $request = NovaRequest::create('/nova-api/activity-logs', 'GET');
        $request->setUserResolver(fn () => $editor);

        $this->assertTrue(ActivityLogResource::authorizedToViewAny($request));
    }

    public function test_author_cannot_view_any_activity_logs(): void
    {
        $author = User::factory()->create(['role' => 'author']);
        $request = NovaRequest::create('/nova-api/activity-logs', 'GET');
        $request->setUserResolver(fn () => $author);

        $this->assertFalse(ActivityLogResource::authorizedToViewAny($request));
    }

    public function test_user_cannot_view_any_activity_logs(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $request = NovaRequest::create('/nova-api/activity-logs', 'GET');
        $request->setUserResolver(fn () => $user);

        $this->assertFalse(ActivityLogResource::authorizedToViewAny($request));
    }

    public function test_guest_cannot_view_any_activity_logs(): void
    {
        $request = NovaRequest::create('/nova-api/activity-logs', 'GET');
        $request->setUserResolver(fn () => null);

        $this->assertFalse(ActivityLogResource::authorizedToViewAny($request));
    }

    public function test_admin_can_view_activity_log(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $activityLog = ActivityLog::factory()->create();
        $resource = new ActivityLogResource($activityLog);

        $request = NovaRequest::create('/nova-api/activity-logs/1', 'GET');
        $request->setUserResolver(fn () => $admin);

        $this->assertTrue($resource->authorizedToView($request));
    }

    public function test_editor_can_view_activity_log(): void
    {
        $editor = User::factory()->create(['role' => 'editor']);
        $activityLog = ActivityLog::factory()->create();
        $resource = new ActivityLogResource($activityLog);

        $request = NovaRequest::create('/nova-api/activity-logs/1', 'GET');
        $request->setUserResolver(fn () => $editor);

        $this->assertTrue($resource->authorizedToView($request));
    }

    public function test_author_cannot_view_activity_log(): void
    {
        $author = User::factory()->create(['role' => 'author']);
        $activityLog = ActivityLog::factory()->create();
        $resource = new ActivityLogResource($activityLog);

        $request = NovaRequest::create('/nova-api/activity-logs/1', 'GET');
        $request->setUserResolver(fn () => $author);

        $this->assertFalse($resource->authorizedToView($request));
    }

    public function test_guest_cannot_view_activity_log(): void
    {
        $activityLog = ActivityLog::factory()->create();
        $resource = new ActivityLogResource($activityLog);

        $request = NovaRequest::create('/nova-api/activity-logs/1', 'GET');
        $request->setUserResolver(fn () => null);

        $this->assertFalse($resource->authorizedToView($request));
    }

    public function test_no_user_can_create_activity_log(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $request = NovaRequest::create('/nova-api/activity-logs', 'POST');
        $request->setUserResolver(fn () => $admin);

        $this->assertFalse(ActivityLogResource::authorizedToCreate($request));
    }

    public function test_no_user_can_update_activity_log(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $activityLog = ActivityLog::factory()->create();
        $resource = new ActivityLogResource($activityLog);

        $request = NovaRequest::create('/nova-api/activity-logs/1', 'PUT');
        $request->setUserResolver(fn () => $admin);

        $this->assertFalse($resource->authorizedToUpdate($request));
    }

    public function test_no_user_can_delete_activity_log(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $activityLog = ActivityLog::factory()->create();
        $resource = new ActivityLogResource($activityLog);

        $request = NovaRequest::create('/nova-api/activity-logs/1', 'DELETE');
        $request->setUserResolver(fn () => $admin);

        $this->assertFalse($resource->authorizedToDelete($request));
    }

    public function test_activity_log_resource_has_fields(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $activityLog = ActivityLog::factory()->create();
        $resource = new ActivityLogResource($activityLog);

        $request = NovaRequest::create('/nova-api/activity-logs', 'GET');
        $request->setUserResolver(fn () => $admin);

        $fields = $resource->fields($request);

        $this->assertNotEmpty($fields);
        $this->assertGreaterThanOrEqual(10, count($fields));
    }

    public function test_activity_log_index_query_eager_loads_relationships(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $post = Post::factory()->create();

        ActivityLog::factory()->create([
            'subject_type' => Post::class,
            'subject_id' => $post->id,
            'causer_type' => User::class,
            'causer_id' => $admin->id,
        ]);

        $request = NovaRequest::create('/nova-api/activity-logs', 'GET');
        $request->setUserResolver(fn () => $admin);

        $query = ActivityLogResource::indexQuery($request, ActivityLog::query());
        $logs = $query->get();

        $this->assertTrue($logs->first()->relationLoaded('subject'));
        $this->assertTrue($logs->first()->relationLoaded('causer'));
    }

    public function test_editable_fields_are_readonly(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $activityLog = ActivityLog::factory()->create();
        $resource = new ActivityLogResource($activityLog);

        $request = NovaRequest::create('/nova-api/activity-logs/1', 'GET');
        $request->setUserResolver(fn () => $admin);

        $fields = $resource->fields($request);

        $readonlyFields = ['log_name', 'description', 'event', 'properties', 'ip_address', 'user_agent', 'created_at'];

        foreach ($fields as $field) {
            if (in_array($field->attribute, $readonlyFields) && method_exists($field, 'isReadonly')) {
                $this->assertTrue(
                    $field->isReadonly($request),
                    "Field {$field->attribute} should be readonly"
                );
            }
        }
    }

    public function test_properties_field_is_json(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $activityLog = ActivityLog::factory()->create([
            'properties' => [
                'old' => ['title' => 'Old Title'],
                'new' => ['title' => 'New Title'],
            ],
        ]);
        $resource = new ActivityLogResource($activityLog);

        $request = NovaRequest::create('/nova-api/activity-logs/1', 'GET');
        $request->setUserResolver(fn () => $admin);

        $fields = $resource->fields($request);
        $propertiesField = collect($fields)->first(fn ($field) => $field->attribute === 'properties');

        $this->assertNotNull($propertiesField);
        $this->assertInstanceOf(\Laravel\Nova\Fields\Code::class, $propertiesField);
    }

    public function test_morphto_fields_exist(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $activityLog = ActivityLog::factory()->create();
        $resource = new ActivityLogResource($activityLog);

        $request = NovaRequest::create('/nova-api/activity-logs', 'GET');
        $request->setUserResolver(fn () => $admin);

        $fields = $resource->fields($request);

        $subjectField = collect($fields)->first(fn ($field) => $field->attribute === 'subject');
        $causerField = collect($fields)->first(fn ($field) => $field->attribute === 'causer');

        $this->assertNotNull($subjectField);
        $this->assertNotNull($causerField);
        $this->assertInstanceOf(\Laravel\Nova\Fields\MorphTo::class, $subjectField);
        $this->assertInstanceOf(\Laravel\Nova\Fields\MorphTo::class, $causerField);
    }
}

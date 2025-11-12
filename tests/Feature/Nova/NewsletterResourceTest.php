<?php

namespace Tests\Feature\Nova;

use App\Models\Newsletter;
use App\Models\User;
use App\Nova\Newsletter as NewsletterResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Nova\Http\Requests\NovaRequest;
use Tests\TestCase;

class NewsletterResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_newsletter_resource_has_correct_model(): void
    {
        $this->assertEquals(\App\Models\Newsletter::class, NewsletterResource::$model);
    }

    public function test_newsletter_resource_has_correct_title(): void
    {
        $this->assertEquals('email', NewsletterResource::$title);
    }

    public function test_newsletter_resource_has_correct_search_fields(): void
    {
        $expected = ['id', 'email'];
        $this->assertEquals($expected, NewsletterResource::$search);
    }

    public function test_admin_can_view_any_newsletters(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $request = NovaRequest::create('/nova-api/newsletters', 'GET');
        $request->setUserResolver(fn () => $admin);

        $this->assertTrue(NewsletterResource::authorizedToViewAny($request));
    }

    public function test_editor_cannot_view_any_newsletters(): void
    {
        $editor = User::factory()->create(['role' => 'editor']);
        $request = NovaRequest::create('/nova-api/newsletters', 'GET');
        $request->setUserResolver(fn () => $editor);

        $this->assertFalse(NewsletterResource::authorizedToViewAny($request));
    }

    public function test_author_cannot_view_any_newsletters(): void
    {
        $author = User::factory()->create(['role' => 'author']);
        $request = NovaRequest::create('/nova-api/newsletters', 'GET');
        $request->setUserResolver(fn () => $author);

        $this->assertFalse(NewsletterResource::authorizedToViewAny($request));
    }

    public function test_admin_can_create_newsletter(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $request = NovaRequest::create('/nova-api/newsletters', 'POST');
        $request->setUserResolver(fn () => $admin);

        $this->assertTrue(NewsletterResource::authorizedToCreate($request));
    }

    public function test_editor_cannot_create_newsletter(): void
    {
        $editor = User::factory()->create(['role' => 'editor']);
        $request = NovaRequest::create('/nova-api/newsletters', 'POST');
        $request->setUserResolver(fn () => $editor);

        $this->assertFalse(NewsletterResource::authorizedToCreate($request));
    }

    public function test_newsletter_resource_has_fields(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $newsletter = Newsletter::factory()->create();
        $resource = new NewsletterResource($newsletter);

        $request = NovaRequest::create('/nova-api/newsletters', 'GET');
        $request->setUserResolver(fn () => $admin);

        $fields = $resource->fields($request);

        $this->assertNotEmpty($fields);
        $this->assertGreaterThanOrEqual(6, count($fields));
    }

    public function test_newsletter_index_query_orders_by_latest(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $first = Newsletter::factory()->create(['email' => 'first@example.com']);
        sleep(1);
        $second = Newsletter::factory()->create(['email' => 'second@example.com']);
        sleep(1);
        $third = Newsletter::factory()->create(['email' => 'third@example.com']);

        $request = NovaRequest::create('/nova-api/newsletters', 'GET');
        $request->setUserResolver(fn () => $admin);

        $query = NewsletterResource::indexQuery($request, Newsletter::query());
        $newsletters = $query->get();

        $this->assertEquals('third@example.com', $newsletters->first()->email);
        $this->assertEquals('first@example.com', $newsletters->last()->email);
    }

    public function test_admin_can_update_newsletter(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $newsletter = Newsletter::factory()->create();
        $resource = new NewsletterResource($newsletter);

        $request = NovaRequest::create('/nova-api/newsletters/1', 'PUT');
        $request->setUserResolver(fn () => $admin);

        $this->assertTrue($resource->authorizedToUpdate($request));
    }

    public function test_editor_cannot_update_newsletter(): void
    {
        $editor = User::factory()->create(['role' => 'editor']);
        $newsletter = Newsletter::factory()->create();
        $resource = new NewsletterResource($newsletter);

        $request = NovaRequest::create('/nova-api/newsletters/1', 'PUT');
        $request->setUserResolver(fn () => $editor);

        $this->assertFalse($resource->authorizedToUpdate($request));
    }

    public function test_admin_can_delete_newsletter(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $newsletter = Newsletter::factory()->create();
        $resource = new NewsletterResource($newsletter);

        $request = NovaRequest::create('/nova-api/newsletters/1', 'DELETE');
        $request->setUserResolver(fn () => $admin);

        $this->assertTrue($resource->authorizedToDelete($request));
    }

    public function test_editor_cannot_delete_newsletter(): void
    {
        $editor = User::factory()->create(['role' => 'editor']);
        $newsletter = Newsletter::factory()->create();
        $resource = new NewsletterResource($newsletter);

        $request = NovaRequest::create('/nova-api/newsletters/1', 'DELETE');
        $request->setUserResolver(fn () => $editor);

        $this->assertFalse($resource->authorizedToDelete($request));
    }

    public function test_admin_can_view_newsletter(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $newsletter = Newsletter::factory()->create();
        $resource = new NewsletterResource($newsletter);

        $request = NovaRequest::create('/nova-api/newsletters/1', 'GET');
        $request->setUserResolver(fn () => $admin);

        $this->assertTrue($resource->authorizedToView($request));
    }

    public function test_token_field_is_hidden_from_index_and_detail(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $newsletter = Newsletter::factory()->create();
        $resource = new NewsletterResource($newsletter);

        $request = NovaRequest::create('/nova-api/newsletters', 'GET');
        $request->setUserResolver(fn () => $admin);

        $fields = $resource->fields($request);
        $tokenField = collect($fields)->first(fn ($field) => $field->attribute === 'token');

        $this->assertNotNull($tokenField);
        $this->assertTrue($tokenField->showOnIndex === false);
        $this->assertTrue($tokenField->showOnDetail === false);
    }
}

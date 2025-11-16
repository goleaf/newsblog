<?php

namespace Tests\Feature\Frontend;

use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class StaticPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_page_displays(): void
    {
        $page = Page::factory()->create([
            'status' => 'published',
            'template' => 'default',
        ]);

        $response = $this->get("/page/{$page->slug}");

        $response->assertStatus(200);
        $response->assertSee($page->title);
        $response->assertSee($page->content);
    }

    public function test_full_width_page_displays(): void
    {
        $page = Page::factory()->create([
            'status' => 'published',
            'template' => 'full-width',
        ]);

        $response = $this->get("/page/{$page->slug}");

        $response->assertStatus(200);
        $response->assertSee($page->title);
    }

    public function test_contact_form_submission_is_stored_and_notified(): void
    {
        Mail::fake();

        $response = $this->post('/page/contact', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'subject' => 'Hello',
            'message' => 'This is a test message.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('contact_messages', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'subject' => 'Hello',
            'status' => 'new',
        ]);

        Mail::assertQueued(ContactMessageReceived::class, function ($mail) {
            return $mail->hasTo(config('mail.from.address'));
        });
    }

    public function test_page_hierarchy_relationships(): void
    {
        $parent = Page::factory()->create([
            'title' => 'Parent Page',
            'status' => 'published',
            'template' => 'default',
            'display_order' => 1,
        ]);

        $child = Page::factory()->create([
            'title' => 'Child Page',
            'status' => 'published',
            'template' => 'default',
            'display_order' => 2,
            'parent_id' => $parent->id,
        ]);

        $this->assertTrue($child->parent->is($parent));
        $this->assertTrue($parent->children->contains(fn ($p) => $p->id === $child->id));

        $response = $this->get("/page/{$parent->slug}");
        $response->assertStatus(200);
        $response->assertSee('Parent Page');
    }
}



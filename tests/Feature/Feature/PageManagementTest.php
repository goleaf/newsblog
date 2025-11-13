<?php

namespace Tests\Feature\Feature;

use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_page_can_be_viewed(): void
    {
        $page = Page::factory()->create([
            'status' => 'published',
            'template' => 'default',
            'title' => 'Test Page Title',
            'content' => '<p>Test page content</p>',
        ]);

        $response = $this->get(route('page.show', $page->slug));

        $response->assertStatus(200);
        $response->assertViewIs('pages.default');
        $response->assertViewHas('page', $page);
    }

    public function test_draft_page_cannot_be_viewed(): void
    {
        $page = Page::factory()->create([
            'status' => 'draft',
        ]);

        $response = $this->get(route('page.show', $page->slug));

        $response->assertStatus(404);
    }

    public function test_page_with_full_width_template_uses_correct_view(): void
    {
        $page = Page::factory()->create([
            'status' => 'published',
            'template' => 'full-width',
        ]);

        $response = $this->get(route('page.show', $page->slug));

        $response->assertStatus(200);
        $response->assertViewIs('pages.full-width');
    }

    public function test_page_with_contact_template_displays_contact_form(): void
    {
        $page = Page::factory()->create([
            'status' => 'published',
            'template' => 'contact',
        ]);

        $response = $this->get(route('page.show', $page->slug));

        $response->assertStatus(200);
        $response->assertViewIs('pages.contact');
        $response->assertViewHas('page', $page);
    }

    public function test_contact_form_can_be_submitted(): void
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'subject' => 'Test Subject',
            'message' => 'This is a test message.',
        ];

        $response = $this->post(route('page.contact.submit'), $data);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('contact_messages', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'subject' => 'Test Subject',
            'status' => 'new',
        ]);
    }

    public function test_contact_form_validates_required_fields(): void
    {
        $response = $this->post(route('page.contact.submit'), []);

        $response->assertSessionHasErrors(['name', 'email', 'subject', 'message']);
    }

    public function test_page_displays_parent_breadcrumb(): void
    {
        $parent = Page::factory()->create([
            'status' => 'published',
            'title' => 'Parent Page',
        ]);

        $child = Page::factory()->create([
            'status' => 'published',
            'title' => 'Child Page',
            'parent_id' => $parent->id,
        ]);

        $response = $this->get(route('page.show', $child->slug));

        $response->assertStatus(200);
        $response->assertSee($parent->title);
        $response->assertSee($child->title);
    }

    public function test_page_displays_child_pages(): void
    {
        $parent = Page::factory()->create([
            'status' => 'published',
            'title' => 'Parent Page',
        ]);

        $child1 = Page::factory()->create([
            'status' => 'published',
            'parent_id' => $parent->id,
            'title' => 'Child Page 1',
        ]);

        $child2 = Page::factory()->create([
            'status' => 'published',
            'parent_id' => $parent->id,
            'title' => 'Child Page 2',
        ]);

        $response = $this->get(route('page.show', $parent->slug));

        $response->assertStatus(200);
        $response->assertViewHas('page', function ($page) use ($parent) {
            return $page->id === $parent->id && $page->children->count() === 2;
        });
    }

    public function test_admin_can_create_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.pages.store'), [
            'title' => 'New Page',
            'content' => 'Page content',
            'status' => 'published',
            'template' => 'default',
            'display_order' => 0,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('pages', [
            'title' => 'New Page',
            'status' => 'published',
        ]);
    }

    public function test_admin_can_update_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $page = Page::factory()->create();

        $response = $this->actingAs($admin)->put(route('admin.pages.update', $page), [
            'title' => 'Updated Title',
            'content' => 'Updated content',
            'status' => 'published',
            'template' => 'full-width',
            'display_order' => 0,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('pages', [
            'id' => $page->id,
            'title' => 'Updated Title',
            'template' => 'full-width',
        ]);
    }

    public function test_admin_can_delete_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $page = Page::factory()->create();

        $response = $this->actingAs($admin)->delete(route('admin.pages.destroy', $page));

        $response->assertRedirect();
        $this->assertDatabaseMissing('pages', [
            'id' => $page->id,
        ]);
    }

    public function test_admin_can_update_page_order(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $page1 = Page::factory()->create(['display_order' => 0]);
        $page2 = Page::factory()->create(['display_order' => 1]);

        $response = $this->actingAs($admin)->postJson(route('admin.pages.update-order'), [
            'pages' => [
                ['id' => $page1->id, 'display_order' => 1],
                ['id' => $page2->id, 'display_order' => 0],
            ],
        ]);

        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('pages', [
            'id' => $page1->id,
            'display_order' => 1,
        ]);
        $this->assertDatabaseHas('pages', [
            'id' => $page2->id,
            'display_order' => 0,
        ]);
    }

    public function test_page_can_have_hierarchical_relationships(): void
    {
        $parent = Page::factory()->create();
        $child = Page::factory()->create(['parent_id' => $parent->id]);

        $this->assertEquals($parent->id, $child->parent->id);
        $this->assertTrue($parent->children->contains($child));
    }

    public function test_page_templates_are_available(): void
    {
        $page = new Page;
        $templates = $page->getAvailableTemplates();

        $this->assertIsArray($templates);
        $this->assertArrayHasKey('default', $templates);
        $this->assertArrayHasKey('full-width', $templates);
        $this->assertArrayHasKey('contact', $templates);
        $this->assertArrayHasKey('about', $templates);
    }

    public function test_page_can_check_if_contact_template(): void
    {
        $contactPage = Page::factory()->create(['template' => 'contact']);
        $defaultPage = Page::factory()->create(['template' => 'default']);

        $this->assertTrue($contactPage->isContactTemplate());
        $this->assertFalse($defaultPage->isContactTemplate());
    }
}

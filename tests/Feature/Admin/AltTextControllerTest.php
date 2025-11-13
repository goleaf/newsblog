<?php

namespace Tests\Feature\Admin;

use App\Models\Media;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AltTextControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_can_view_accessibility_report(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.alt-text.report'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.alt-text.report');
        $response->assertViewHas(['summary', 'postsWithIssues', 'mediaWithoutAlt']);
    }

    public function test_non_admin_cannot_view_accessibility_report(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)
            ->get(route('admin.alt-text.report'));

        $response->assertStatus(403);
    }

    public function test_admin_can_view_bulk_edit_interface(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.alt-text.bulk-edit'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.alt-text.bulk-edit');
        $response->assertViewHas('mediaItems');
    }

    public function test_admin_can_bulk_update_alt_text(): void
    {
        $media1 = Media::factory()->create([
            'user_id' => $this->admin->id,
            'file_type' => 'image',
            'alt_text' => null,
        ]);

        $media2 = Media::factory()->create([
            'user_id' => $this->admin->id,
            'file_type' => 'image',
            'alt_text' => null,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.alt-text.bulk-update'), [
                'alt_texts' => [
                    $media1->id => 'New alt text 1',
                    $media2->id => 'New alt text 2',
                ],
            ]);

        $response->assertRedirect(route('admin.alt-text.bulk-edit'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('media_library', [
            'id' => $media1->id,
            'alt_text' => 'New alt text 1',
        ]);

        $this->assertDatabaseHas('media_library', [
            'id' => $media2->id,
            'alt_text' => 'New alt text 2',
        ]);
    }

    public function test_bulk_update_skips_empty_alt_text(): void
    {
        $media = Media::factory()->create([
            'user_id' => $this->admin->id,
            'file_type' => 'image',
            'alt_text' => null,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.alt-text.bulk-update'), [
                'alt_texts' => [
                    $media->id => '',
                ],
            ]);

        $response->assertRedirect(route('admin.alt-text.bulk-edit'));

        $this->assertDatabaseHas('media_library', [
            'id' => $media->id,
            'alt_text' => null,
        ]);
    }

    public function test_can_validate_post_for_alt_text_issues(): void
    {
        $post = Post::factory()->create([
            'user_id' => $this->admin->id,
            'featured_image' => 'images/test.jpg',
            'image_alt_text' => null,
            'content' => '<p>Test</p>',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.posts.validate-alt-text', $post));

        $response->assertStatus(200);
        $response->assertJson([
            'has_issues' => true,
        ]);
    }

    public function test_validates_post_without_issues(): void
    {
        $post = Post::factory()->create([
            'user_id' => $this->admin->id,
            'featured_image' => 'images/test.jpg',
            'image_alt_text' => 'Valid alt text',
            'content' => '<p>Test</p><img src="/test.jpg" alt="Valid">',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.posts.validate-alt-text', $post));

        $response->assertStatus(200);
        $response->assertJson([
            'has_issues' => false,
        ]);
    }

    public function test_accessibility_report_shows_correct_statistics(): void
    {
        // Create posts with issues
        Post::factory()->create([
            'user_id' => $this->admin->id,
            'status' => 'published',
            'featured_image' => 'images/test1.jpg',
            'image_alt_text' => null,
        ]);

        // Create compliant post
        Post::factory()->create([
            'user_id' => $this->admin->id,
            'status' => 'published',
            'featured_image' => 'images/test2.jpg',
            'image_alt_text' => 'Valid alt',
        ]);

        // Create media without alt
        Media::factory()->create([
            'user_id' => $this->admin->id,
            'file_type' => 'image',
            'alt_text' => null,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.alt-text.report'));

        $response->assertStatus(200);
        $response->assertViewHas('summary', function ($summary) {
            return $summary['total_published_posts'] === 2
                && $summary['posts_with_issues'] === 1
                && $summary['compliance_rate'] === 50.0
                && $summary['media_without_alt'] === 1;
        });
    }
}

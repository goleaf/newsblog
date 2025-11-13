<?php

namespace Tests\Unit;

use App\Models\Media;
use App\Models\Post;
use App\Models\User;
use App\Services\AltTextValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AltTextValidatorTest extends TestCase
{
    use RefreshDatabase;

    private AltTextValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new AltTextValidator;
    }

    public function test_scans_post_content_for_images_without_alt_text(): void
    {
        $content = '<p>Some text</p><img src="/image1.jpg" alt=""><img src="/image2.jpg" alt="Valid alt text"><img src="/image3.jpg">';

        $result = $this->validator->scanPostContent($content);

        $this->assertCount(2, $result);
        $this->assertStringContainsString('image1.jpg', $result[0]['src']);
        $this->assertStringContainsString('image3.jpg', $result[1]['src']);
    }

    public function test_validates_post_with_missing_featured_image_alt_text(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'featured_image' => 'images/test.jpg',
            'image_alt_text' => null,
            'content' => '<p>Test content</p>',
        ]);

        $issues = $this->validator->validatePost($post);

        $this->assertNotEmpty($issues);
        $this->assertEquals('featured_image', $issues[0]['type']);
    }

    public function test_validates_post_with_content_images_missing_alt_text(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'content' => '<p>Text</p><img src="/test.jpg" alt="">',
        ]);

        $issues = $this->validator->validatePost($post);

        $this->assertNotEmpty($issues);
        $this->assertEquals('content_images', $issues[0]['type']);
        $this->assertEquals(1, $issues[0]['count']);
    }

    public function test_validates_post_without_issues(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'featured_image' => 'images/test.jpg',
            'image_alt_text' => 'Test image',
            'content' => '<p>Text</p><img src="/test.jpg" alt="Valid alt text">',
        ]);

        $issues = $this->validator->validatePost($post);

        $this->assertEmpty($issues);
    }

    public function test_gets_media_without_alt_text(): void
    {
        $user = User::factory()->create();

        Media::factory()->create([
            'user_id' => $user->id,
            'file_type' => 'image',
            'alt_text' => null,
        ]);

        Media::factory()->create([
            'user_id' => $user->id,
            'file_type' => 'image',
            'alt_text' => 'Valid alt text',
        ]);

        Media::factory()->create([
            'user_id' => $user->id,
            'file_type' => 'document',
            'alt_text' => null,
        ]);

        $result = $this->validator->getMediaWithoutAltText();

        $this->assertCount(1, $result);
    }

    public function test_requires_alt_text_for_images(): void
    {
        $user = User::factory()->create();
        $imageMedia = Media::factory()->create([
            'user_id' => $user->id,
            'file_type' => 'image',
        ]);

        $documentMedia = Media::factory()->create([
            'user_id' => $user->id,
            'file_type' => 'document',
        ]);

        $this->assertTrue($this->validator->requiresAltText($imageMedia));
        $this->assertFalse($this->validator->requiresAltText($documentMedia));
    }

    public function test_validates_media_item(): void
    {
        $user = User::factory()->create();

        $mediaWithoutAlt = Media::factory()->create([
            'user_id' => $user->id,
            'file_type' => 'image',
            'alt_text' => null,
        ]);

        $mediaWithAlt = Media::factory()->create([
            'user_id' => $user->id,
            'file_type' => 'image',
            'alt_text' => 'Valid alt text',
        ]);

        $this->assertNotNull($this->validator->validateMedia($mediaWithoutAlt));
        $this->assertNull($this->validator->validateMedia($mediaWithAlt));
    }

    public function test_generates_accessibility_report(): void
    {
        $user = User::factory()->create();

        Post::factory()->create([
            'user_id' => $user->id,
            'status' => 'published',
            'featured_image' => 'images/test.jpg',
            'image_alt_text' => null,
        ]);

        Post::factory()->create([
            'user_id' => $user->id,
            'status' => 'published',
            'featured_image' => 'images/test2.jpg',
            'image_alt_text' => 'Valid alt',
        ]);

        Media::factory()->create([
            'user_id' => $user->id,
            'file_type' => 'image',
            'alt_text' => null,
        ]);

        $report = $this->validator->generateAccessibilityReport();

        $this->assertEquals(2, $report['summary']['total_published_posts']);
        $this->assertEquals(1, $report['summary']['posts_with_issues']);
        $this->assertEquals(1, $report['summary']['posts_compliant']);
        $this->assertEquals(50.0, $report['summary']['compliance_rate']);
        $this->assertEquals(1, $report['summary']['media_without_alt']);
    }
}

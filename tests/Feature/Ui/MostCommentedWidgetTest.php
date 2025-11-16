<?php

namespace Tests\Feature\Ui;

use App\Enums\CommentStatus;
use App\Enums\PostStatus;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Widget;
use App\Services\WidgetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MostCommentedWidgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_renders_top_5_most_commented_posts_excluding_older_than_30_days(): void
    {
        // Posts within 30 days with varying approved comments
        $posts = Post::factory()->count(6)->create([
            'status' => PostStatus::Published->value,
            'published_at' => now()->subDays(5),
        ]);

        foreach ($posts as $i => $post) {
            Comment::factory()->count($i)->create([
                'post_id' => $post->id,
                'status' => CommentStatus::Approved->value,
                'created_at' => now()->subDays(2),
            ]);
        }

        // Post with many comments but published 40 days ago (should be excluded)
        $oldPost = Post::factory()->create([
            'status' => PostStatus::Published->value,
            'published_at' => now()->subDays(40),
        ]);
        Comment::factory()->count(20)->create([
            'post_id' => $oldPost->id,
            'status' => CommentStatus::Approved->value,
            'created_at' => now()->subDays(3),
        ]);

        $widget = Widget::factory()->create([
            'type' => 'most-commented',
            'active' => true,
            'settings' => ['count' => 5],
        ]);

        $html = app(WidgetService::class)->render($widget);

        // Top of $posts has the highest index, thus most comments
        $this->assertStringContainsString(e($posts[5]->title), $html);
        $this->assertStringNotContainsString(e($oldPost->title), $html);
        $this->assertStringContainsString('#comments', $html);
    }
}




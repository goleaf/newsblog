<?php

namespace Tests\Feature\Frontend;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Models\Widget;
use App\Models\WidgetArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FooterAndWidgetsTest extends TestCase
{
    use RefreshDatabase;

    public function test_footer_component_renders_correctly(): void
    {
        $view = $this->blade('<x-layout.footer />');

        $view->assertSee(config('app.name'));
        $view->assertSee('Quick Links');
        $view->assertSee('Resources');
        $view->assertSee('Legal');
        $view->assertSee(date('Y'));
    }

    public function test_footer_renders_social_media_icons(): void
    {
        $view = $this->blade('<x-layout.footer />');

        $view->assertSee('Twitter', false);
        $view->assertSee('GitHub', false);
        $view->assertSee('LinkedIn', false);
        $view->assertSee('RSS Feed', false);
    }

    public function test_footer_renders_without_widgets_when_disabled(): void
    {
        $view = $this->blade('<x-layout.footer :show-widgets="false" />');

        $view->assertSee('Quick Links');
        $view->assertDontSee('widget-area', false);
    }

    public function test_widget_area_component_renders_active_widgets(): void
    {
        $widgetArea = WidgetArea::factory()->create(['slug' => 'sidebar']);

        Widget::factory()->create([
            'widget_area_id' => $widgetArea->id,
            'type' => 'recent-posts',
            'title' => 'Recent Posts',
            'active' => true,
            'order' => 1,
        ]);

        $view = $this->blade('<x-layout.widget-area slug="sidebar" />');

        $view->assertSee('Recent Posts');
    }

    public function test_widget_area_does_not_render_inactive_widgets(): void
    {
        $widgetArea = WidgetArea::factory()->create(['slug' => 'sidebar']);

        Widget::factory()->create([
            'widget_area_id' => $widgetArea->id,
            'type' => 'recent-posts',
            'title' => 'Inactive Widget',
            'active' => false,
            'order' => 1,
        ]);

        $view = $this->blade('<x-layout.widget-area slug="sidebar" />');

        $view->assertDontSee('Inactive Widget');
    }

    public function test_recent_posts_widget_renders_posts(): void
    {
        $author = User::factory()->create();
        $posts = Post::factory()->count(3)->create([
            'status' => 'published',
            'user_id' => $author->id,
        ]);

        $widget = Widget::factory()->make([
            'type' => 'recent-posts',
            'title' => 'Recent Articles',
        ]);

        $view = $this->blade('<x-widgets.recent-posts :widget="$widget" :posts="$posts" />', [
            'widget' => $widget,
            'posts' => $posts,
        ]);

        $view->assertSee('Recent Articles');
        foreach ($posts as $post) {
            $view->assertSee($post->title);
        }
    }

    public function test_recent_posts_widget_shows_empty_state_when_no_posts(): void
    {
        $widget = Widget::factory()->make([
            'type' => 'recent-posts',
            'title' => 'Recent Articles',
        ]);

        $view = $this->blade('<x-widgets.recent-posts :widget="$widget" :posts="collect([])" />', [
            'widget' => $widget,
            'posts' => collect([]),
        ]);

        $view->assertSee('No recent posts available');
    }

    public function test_popular_posts_widget_renders_posts_with_view_counts(): void
    {
        $author = User::factory()->create();
        $posts = Post::factory()->count(3)->create([
            'status' => 'published',
            'user_id' => $author->id,
            'view_count' => 1000,
        ]);

        $widget = Widget::factory()->make([
            'type' => 'popular-posts',
            'title' => 'Popular Articles',
        ]);

        $view = $this->blade('<x-widgets.popular-posts :widget="$widget" :posts="$posts" />', [
            'widget' => $widget,
            'posts' => $posts,
        ]);

        $view->assertSee('Popular Articles');
        $view->assertSee('1,000 views');
    }

    public function test_categories_list_widget_renders_categories(): void
    {
        $categories = Category::factory()->count(3)->create();

        // Add posts_count attribute
        $categories->each(function ($category) {
            $category->posts_count = rand(5, 20);
        });

        $widget = Widget::factory()->make([
            'type' => 'categories',
            'title' => 'Categories',
        ]);

        $view = $this->blade('<x-widgets.categories-list :widget="$widget" :categories="$categories" :show-count="true" />', [
            'widget' => $widget,
            'categories' => $categories,
            'showCount' => true,
        ]);

        $view->assertSee('Categories');
        foreach ($categories as $category) {
            $view->assertSee($category->name);
        }
    }

    public function test_categories_list_widget_hides_count_when_disabled(): void
    {
        $categories = Category::factory()->count(2)->create();

        $categories->each(function ($category) {
            $category->posts_count = 10;
        });

        $widget = Widget::factory()->make([
            'type' => 'categories',
            'title' => 'Categories',
        ]);

        $view = $this->blade('<x-widgets.categories-list :widget="$widget" :categories="$categories" :show-count="false" />', [
            'widget' => $widget,
            'categories' => $categories,
            'showCount' => false,
        ]);

        $view->assertDontSee('10', false);
    }

    public function test_tags_cloud_widget_renders_tags(): void
    {
        $tags = Tag::factory()->count(5)->create();

        // Add posts_count attribute
        $tags->each(function ($tag, $index) {
            $tag->posts_count = ($index + 1) * 5;
        });

        $widget = Widget::factory()->make([
            'type' => 'tags-cloud',
            'title' => 'Popular Tags',
        ]);

        $view = $this->blade('<x-widgets.tags-cloud :widget="$widget" :tags="$tags" />', [
            'widget' => $widget,
            'tags' => $tags,
        ]);

        $view->assertSee('Popular Tags');
        foreach ($tags as $tag) {
            $view->assertSee($tag->name);
        }
    }

    public function test_newsletter_widget_renders_form(): void
    {
        $widget = Widget::factory()->make([
            'type' => 'newsletter',
            'title' => 'Subscribe to Newsletter',
            'settings' => [
                'description' => 'Get the latest updates',
            ],
        ]);

        $view = $this->blade('<x-widgets.newsletter-form :widget="$widget" />', [
            'widget' => $widget,
        ]);

        $view->assertSee('Subscribe to Newsletter');
        $view->assertSee('Get the latest updates');
        $view->assertSee('Enter your email', false);
        $view->assertSee('privacy policy');
    }

    public function test_newsletter_widget_includes_gdpr_consent(): void
    {
        $widget = Widget::factory()->make([
            'type' => 'newsletter',
            'title' => 'Newsletter',
        ]);

        $view = $this->blade('<x-widgets.newsletter-form :widget="$widget" />', [
            'widget' => $widget,
        ]);

        $view->assertSee('privacy policy');
        $view->assertSee('newsletter-gdpr', false);
    }

    public function test_custom_html_widget_renders_content(): void
    {
        $widget = Widget::factory()->make([
            'type' => 'custom-html',
            'title' => 'Custom Widget',
        ]);

        $content = '<p>This is custom HTML content</p>';

        $view = $this->blade('<x-widgets.custom-html :widget="$widget" :content="$content" />', [
            'widget' => $widget,
            'content' => $content,
        ]);

        $view->assertSee('Custom Widget');
        $view->assertSee('This is custom HTML content');
    }

    public function test_custom_html_widget_sanitizes_content(): void
    {
        $widget = Widget::factory()->make([
            'type' => 'custom-html',
            'title' => 'Custom Widget',
        ]);

        $content = '<script>alert("xss")</script><p>Safe content</p>';

        $view = $this->blade('<x-widgets.custom-html :widget="$widget" :content="$content" />', [
            'widget' => $widget,
            'content' => $content,
        ]);

        $view->assertSee('Safe content');
        $view->assertDontSee('<script>', false);
    }
}

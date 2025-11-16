<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'author']);
        $this->category = Category::factory()->create();
    }

    public function test_can_view_articles_index(): void
    {
        Article::factory()->count(3)->create([
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->get(route('articles.index'));

        $response->assertOk();
        $response->assertViewIs('articles.index');
        $response->assertViewHas('articles');
    }

    public function test_can_view_single_article(): void
    {
        $article = Article::factory()->create([
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->get(route('articles.show', $article->slug));

        $response->assertOk();
        $response->assertViewIs('articles.show');
        $response->assertViewHas('article');
        $response->assertSee($article->title);
    }

    public function test_can_view_create_form_when_authenticated(): void
    {
        $response = $this->actingAs($this->user)->get(route('articles.create'));

        $response->assertOk();
        $response->assertViewIs('articles.create');
    }

    public function test_cannot_view_create_form_when_not_authenticated(): void
    {
        $response = $this->get(route('articles.create'));

        $response->assertRedirect(route('login'));
    }

    public function test_can_create_article(): void
    {
        $data = [
            'title' => 'Test Article',
            'content' => 'This is test content for the article.',
            'excerpt' => 'Test excerpt',
            'category_id' => $this->category->id,
            'status' => 'draft',
        ];

        $response = $this->actingAs($this->user)->post(route('articles.store'), $data);

        $response->assertRedirect();
        $this->assertDatabaseHas('posts', [
            'title' => 'Test Article',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_can_view_edit_form(): void
    {
        $article = Article::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('articles.edit', $article));

        $response->assertOk();
        $response->assertViewIs('articles.edit');
        $response->assertViewHas('article');
    }

    public function test_can_update_article(): void
    {
        $article = Article::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $data = [
            'title' => 'Updated Title',
            'content' => 'Updated content',
            'category_id' => $this->category->id,
            'status' => 'published',
        ];

        $response = $this->actingAs($this->user)->put(route('articles.update', $article), $data);

        $response->assertRedirect();
        $this->assertDatabaseHas('posts', [
            'id' => $article->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_can_delete_article(): void
    {
        $article = Article::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->delete(route('articles.destroy', $article));

        $response->assertRedirect();
        $this->assertSoftDeleted('posts', [
            'id' => $article->id,
        ]);
    }

    public function test_can_publish_article(): void
    {
        $article = Article::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->user)->post(route('articles.publish', $article));

        $response->assertRedirect();
        $article->refresh();
        $this->assertEquals('published', $article->status->value);
        $this->assertNotNull($article->published_at);
    }

    public function test_can_unpublish_article(): void
    {
        $article = Article::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->actingAs($this->user)->post(route('articles.unpublish', $article));

        $response->assertRedirect();
        $article->refresh();
        $this->assertEquals('draft', $article->status->value);
        $this->assertNull($article->published_at);
    }
}

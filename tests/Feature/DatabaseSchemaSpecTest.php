<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseSchemaSpecTest extends TestCase
{
    use RefreshDatabase;

    public function test_core_content_tables_exist(): void
    {
        // articles -> posts mapping
        $this->assertTrue(Schema::hasTable('posts'));
        $this->assertTrue(Schema::hasTable('categories'));
        $this->assertTrue(Schema::hasColumn('categories', 'parent_id'));
        $this->assertTrue(Schema::hasTable('tags'));
        // article_tag -> post_tag mapping
        $this->assertTrue(Schema::hasTable('post_tag'));
    }

    public function test_user_and_authentication_tables_exist(): void
    {
        $this->assertTrue(Schema::hasTable('users'));
        $this->assertTrue(Schema::hasTable('password_reset_tokens'));
        // role may be enum or string depending on driver
        $this->assertTrue(Schema::hasColumn('users', 'role'));

        $this->assertTrue(Schema::hasTable('user_profiles'));
        $this->assertTrue(Schema::hasTable('user_preferences'));
        $this->assertTrue(Schema::hasTable('social_accounts'));
    }

    public function test_engagement_and_interaction_tables_exist(): void
    {
        $this->assertTrue(Schema::hasTable('comments'));
        $this->assertTrue(Schema::hasColumn('comments', 'parent_id'));
        $this->assertTrue(Schema::hasTable('comment_reactions'));
        $this->assertTrue(Schema::hasTable('comment_flags'));

        $this->assertTrue(Schema::hasTable('bookmarks'));
        $this->assertTrue(Schema::hasTable('reading_lists'));
        $this->assertTrue(Schema::hasTable('reading_list_items'));
    }

    public function test_analytics_and_tracking_tables_exist(): void
    {
        // article_views -> post_views mapping
        $this->assertTrue(Schema::hasTable('post_views'));
        $this->assertTrue(Schema::hasTable('traffic_sources'));
        $this->assertTrue(Schema::hasTable('search_logs'));
        $this->assertTrue(Schema::hasTable('user_reading_history'));
    }

    public function test_social_and_notification_tables_exist(): void
    {
        $this->assertTrue(Schema::hasTable('follows'));
        $this->assertTrue(Schema::hasTable('activities'));
        $this->assertTrue(Schema::hasTable('social_shares'));
        $this->assertTrue(Schema::hasTable('notification_preferences'));
    }

    public function test_newsletter_and_moderation_tables_exist(): void
    {
        // newsletter_subscribers -> newsletters mapping
        $this->assertTrue(Schema::hasTable('newsletters'));
        $this->assertTrue(Schema::hasTable('newsletter_sends'));
        $this->assertTrue(Schema::hasTable('moderation_queue'));
        $this->assertTrue(Schema::hasTable('user_reputation'));
        $this->assertTrue(Schema::hasTable('moderation_actions'));
    }

    public function test_recommendation_tables_exist(): void
    {
        // article_similarities -> post_similarities mapping
        $this->assertTrue(Schema::hasTable('post_similarities'));
        $this->assertTrue(Schema::hasTable('recommendations'));
    }
}

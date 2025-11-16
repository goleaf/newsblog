<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseSchemaNewTablesTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_tables_exist_with_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('user_profiles'));
        $this->assertTrue(Schema::hasColumns('user_profiles', [
            'id', 'user_id', 'display_name', 'website_url', 'location', 'birthdate', 'bio', 'social_links', 'created_at', 'updated_at',
        ]));

        $this->assertTrue(Schema::hasTable('user_preferences'));
        $this->assertTrue(Schema::hasColumns('user_preferences', [
            'id', 'user_id', 'email_notifications', 'push_notifications', 'theme', 'language', 'data', 'created_at', 'updated_at',
        ]));

        $this->assertTrue(Schema::hasTable('social_accounts'));
        $this->assertTrue(Schema::hasColumns('social_accounts', [
            'id', 'user_id', 'provider', 'provider_user_id', 'username', 'avatar_url', 'profile_url', 'token', 'created_at', 'updated_at',
        ]));

        $this->assertTrue(Schema::hasTable('comment_reactions'));
        $this->assertTrue(Schema::hasColumns('comment_reactions', [
            'id', 'comment_id', 'user_id', 'type', 'ip_address', 'user_agent', 'created_at', 'updated_at',
        ]));

        $this->assertTrue(Schema::hasTable('comment_flags'));
        $this->assertTrue(Schema::hasColumns('comment_flags', [
            'id', 'comment_id', 'user_id', 'reason', 'notes', 'status', 'created_at', 'updated_at',
        ]));

        $this->assertTrue(Schema::hasTable('reading_lists'));
        $this->assertTrue(Schema::hasColumns('reading_lists', [
            'id', 'user_id', 'name', 'description', 'is_public', 'order', 'created_at', 'updated_at',
        ]));

        $this->assertTrue(Schema::hasTable('reading_list_items'));
        $this->assertTrue(Schema::hasColumns('reading_list_items', [
            'id', 'reading_list_id', 'post_id', 'order', 'note', 'created_at', 'updated_at',
        ]));

        $this->assertTrue(Schema::hasTable('traffic_sources'));
        $this->assertTrue(Schema::hasColumns('traffic_sources', [
            'id', 'post_id', 'user_id', 'session_id', 'referrer_url', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'ip_address', 'user_agent', 'created_at', 'updated_at',
        ]));

        $this->assertTrue(Schema::hasTable('user_reading_history'));
        $this->assertTrue(Schema::hasColumns('user_reading_history', [
            'id', 'user_id', 'post_id', 'last_read_at', 'progress_percent', 'total_time_seconds', 'created_at', 'updated_at',
        ]));

        $this->assertTrue(Schema::hasTable('follows'));
        $this->assertTrue(Schema::hasColumns('follows', [
            'id', 'follower_id', 'followed_id', 'created_at', 'updated_at',
        ]));

        $this->assertTrue(Schema::hasTable('activities'));
        $this->assertTrue(Schema::hasColumns('activities', [
            'id', 'actor_id', 'subject_type', 'subject_id', 'verb', 'meta', 'created_at', 'updated_at',
        ]));

        $this->assertTrue(Schema::hasTable('social_shares'));
        $this->assertTrue(Schema::hasColumns('social_shares', [
            'id', 'post_id', 'user_id', 'provider', 'share_url', 'shared_at', 'created_at', 'updated_at',
        ]));

        $this->assertTrue(Schema::hasTable('notification_preferences'));
        $this->assertTrue(Schema::hasColumns('notification_preferences', [
            'id', 'user_id', 'email_enabled', 'push_enabled', 'digest_frequency', 'channels', 'created_at', 'updated_at',
        ]));

        $this->assertTrue(Schema::hasTable('moderation_queue'));
        $this->assertTrue(Schema::hasColumns('moderation_queue', [
            'id', 'subject_type', 'subject_id', 'reported_by', 'reason', 'notes', 'status', 'priority', 'created_at', 'updated_at',
        ]));

        $this->assertTrue(Schema::hasTable('user_reputation'));
        $this->assertTrue(Schema::hasColumns('user_reputation', [
            'id', 'user_id', 'score', 'upvotes', 'downvotes', 'last_calculated_at', 'created_at', 'updated_at',
        ]));

        $this->assertTrue(Schema::hasTable('moderation_actions'));
        $this->assertTrue(Schema::hasColumns('moderation_actions', [
            'id', 'moderation_queue_id', 'performed_by', 'action', 'notes', 'created_at', 'updated_at',
        ]));

        $this->assertTrue(Schema::hasTable('post_similarities'));
        $this->assertTrue(Schema::hasColumns('post_similarities', [
            'id', 'post_id', 'similar_post_id', 'score', 'created_at', 'updated_at',
        ]));

        $this->assertTrue(Schema::hasTable('recommendations'));
        $this->assertTrue(Schema::hasColumns('recommendations', [
            'id', 'user_id', 'post_id', 'reason', 'score', 'created_at', 'updated_at',
        ]));
    }
}

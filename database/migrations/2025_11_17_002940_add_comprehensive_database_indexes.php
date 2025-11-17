<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Posts table indexes
        Schema::table('posts', function (Blueprint $table) {
            // Composite index for popular posts query (status + view_count)
            $table->index(['status', 'view_count'], 'posts_status_view_count_idx');

            // Composite index for recent published posts
            $table->index(['status', 'published_at', 'created_at'], 'posts_status_published_created_idx');

            // Index for featured posts queries
            $table->index(['is_featured', 'published_at'], 'posts_featured_published_idx');

            // Index for trending posts queries
            $table->index(['is_trending', 'published_at'], 'posts_trending_published_idx');

            // Index for editors pick queries
            $table->index(['is_editors_pick', 'published_at'], 'posts_editors_pick_published_idx');

            // Index for series queries
            $table->index('series_id', 'posts_series_id_idx');
        });

        // Comments table indexes
        Schema::table('comments', function (Blueprint $table) {
            // Composite index for approved comments on a post
            $table->index(['post_id', 'status', 'created_at'], 'comments_post_status_created_idx');

            // Composite index for threaded comments (parent + status)
            $table->index(['parent_id', 'status'], 'comments_parent_status_idx');

            // Index for user's comments
            $table->index(['user_id', 'created_at'], 'comments_user_created_idx');
        });

        // Bookmarks table indexes
        Schema::table('bookmarks', function (Blueprint $table) {
            // Composite index for user's bookmarks with collection
            $table->index(['user_id', 'collection_id', 'created_at'], 'bookmarks_user_collection_created_idx');

            // Index for post bookmarks count
            $table->index(['post_id', 'created_at'], 'bookmarks_post_created_idx');

            // Index for read status filtering
            $table->index(['user_id', 'is_read'], 'bookmarks_user_read_idx');
        });

        // Post views table indexes
        Schema::table('post_views', function (Blueprint $table) {
            // Composite index for analytics queries
            $table->index(['post_id', 'viewed_at'], 'post_views_post_viewed_idx');

            // Index for user reading history
            $table->index(['user_id', 'viewed_at'], 'post_views_user_viewed_idx');

            // Index for session-based tracking
            $table->index(['session_id', 'post_id'], 'post_views_session_post_idx');
        });

        // Reactions table indexes
        Schema::table('reactions', function (Blueprint $table) {
            // Composite index for post reactions
            $table->index(['post_id', 'reaction_type'], 'reactions_post_type_idx');

            // Index for user reactions
            $table->index(['user_id', 'created_at'], 'reactions_user_created_idx');
        });

        // Comment reactions table indexes
        Schema::table('comment_reactions', function (Blueprint $table) {
            // Composite index for comment reactions count
            $table->index(['comment_id', 'reaction_type'], 'comment_reactions_comment_type_idx');
        });

        // Follows table indexes
        Schema::table('follows', function (Blueprint $table) {
            // Index for followers list
            $table->index(['followed_id', 'created_at'], 'follows_followed_created_idx');

            // Index for following list
            $table->index(['follower_id', 'created_at'], 'follows_follower_created_idx');
        });

        // Activities table indexes
        Schema::table('activities', function (Blueprint $table) {
            // Composite index for user activity feed
            $table->index(['user_id', 'created_at'], 'activities_user_created_idx');

            // Composite index for activity type filtering
            $table->index(['user_id', 'activity_type', 'created_at'], 'activities_user_type_created_idx');

            // Polymorphic relationship index
            $table->index(['subject_type', 'subject_id'], 'activities_subject_idx');
        });

        // Notifications table indexes
        Schema::table('notifications', function (Blueprint $table) {
            // Composite index for unread notifications
            $table->index(['notifiable_id', 'notifiable_type', 'read_at'], 'notifications_notifiable_read_idx');

            // Index for notification type filtering
            $table->index(['notifiable_id', 'type', 'created_at'], 'notifications_notifiable_type_created_idx');
        });

        // Search logs table indexes
        Schema::table('search_logs', function (Blueprint $table) {
            // Index for popular searches
            $table->index(['query', 'created_at'], 'search_logs_query_created_idx');

            // Index for user search history
            $table->index(['user_id', 'created_at'], 'search_logs_user_created_idx');
        });

        // Newsletter subscribers table indexes
        Schema::table('newsletters', function (Blueprint $table) {
            // Composite index for active subscribers by frequency
            $table->index(['status', 'frequency'], 'newsletters_status_frequency_idx');

            // Index for verification
            $table->index('verification_token', 'newsletters_verification_token_idx');
        });

        // Newsletter sends table indexes
        Schema::table('newsletter_sends', function (Blueprint $table) {
            // Composite index for tracking metrics
            $table->index(['newsletter_id', 'sent_at'], 'newsletter_sends_newsletter_sent_idx');

            // Index for open tracking
            $table->index(['tracking_token'], 'newsletter_sends_tracking_token_idx');

            // Index for subscriber engagement
            $table->index(['subscriber_id', 'opened_at'], 'newsletter_sends_subscriber_opened_idx');
        });

        // Recommendations table indexes
        Schema::table('recommendations', function (Blueprint $table) {
            // Composite index for user recommendations
            $table->index(['user_id', 'score', 'generated_at'], 'recommendations_user_score_generated_idx');

            // Index for recommendation tracking
            $table->index(['user_id', 'post_id', 'clicked'], 'recommendations_user_post_clicked_idx');

            // Index for recommendation reason filtering
            $table->index(['user_id', 'reason'], 'recommendations_user_reason_idx');
        });

        // Article similarities table indexes
        Schema::table('post_similarities', function (Blueprint $table) {
            // Composite index for similar posts lookup
            $table->index(['post_id', 'similarity_score'], 'post_similarities_post_score_idx');
        });

        // Tags table indexes
        Schema::table('tags', function (Blueprint $table) {
            // Index for popular tags
            $table->index('usage_count', 'tags_usage_count_idx');
        });

        // Post tag pivot table indexes
        Schema::table('post_tag', function (Blueprint $table) {
            // Index for tag-based queries
            $table->index('tag_id', 'post_tag_tag_id_idx');
        });

        // Categories table indexes
        Schema::table('categories', function (Blueprint $table) {
            // Index for hierarchical queries
            $table->index(['parent_id', 'display_order'], 'categories_parent_order_idx');

            // Index for active categories
            $table->index(['status', 'display_order'], 'categories_status_order_idx');
        });

        // Social shares table indexes
        Schema::table('social_shares', function (Blueprint $table) {
            // Composite index for share analytics
            $table->index(['post_id', 'platform', 'shared_at'], 'social_shares_post_platform_shared_idx');
        });

        // Moderation queue table indexes
        Schema::table('moderation_queue', function (Blueprint $table) {
            // Composite index for pending moderation items
            $table->index(['status', 'priority', 'created_at'], 'moderation_queue_status_priority_created_idx');

            // Polymorphic relationship index
            $table->index(['moderatable_type', 'moderatable_id'], 'moderation_queue_moderatable_idx');
        });

        // User reputation table indexes
        Schema::table('user_reputation', function (Blueprint $table) {
            // Index for trust level queries
            $table->index('trust_level', 'user_reputation_trust_level_idx');

            // Index for reputation score ranking
            $table->index('reputation_score', 'user_reputation_score_idx');
        });

        // Broken links table indexes
        Schema::table('broken_links', function (Blueprint $table) {
            // Composite index for post broken links
            $table->index(['post_id', 'status'], 'broken_links_post_status_idx');

            // Index for checking specific URLs
            $table->index('url', 'broken_links_url_idx');
        });

        // Feedback table indexes
        Schema::table('feedback', function (Blueprint $table) {
            // Index for user feedback
            $table->index(['user_id', 'created_at'], 'feedback_user_created_idx');

            // Index for feedback type filtering
            $table->index(['type', 'created_at'], 'feedback_type_created_idx');
        });

        // Users table indexes
        Schema::table('users', function (Blueprint $table) {
            // Index for role-based queries
            $table->index(['role', 'status'], 'users_role_status_idx');

            // Index for active users
            $table->index(['status', 'created_at'], 'users_status_created_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Posts table
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex('posts_status_view_count_idx');
            $table->dropIndex('posts_status_published_created_idx');
            $table->dropIndex('posts_featured_published_idx');
            $table->dropIndex('posts_trending_published_idx');
            $table->dropIndex('posts_editors_pick_published_idx');
            $table->dropIndex('posts_series_id_idx');
        });

        // Comments table
        Schema::table('comments', function (Blueprint $table) {
            $table->dropIndex('comments_post_status_created_idx');
            $table->dropIndex('comments_parent_status_idx');
            $table->dropIndex('comments_user_created_idx');
        });

        // Bookmarks table
        Schema::table('bookmarks', function (Blueprint $table) {
            $table->dropIndex('bookmarks_user_collection_created_idx');
            $table->dropIndex('bookmarks_post_created_idx');
            $table->dropIndex('bookmarks_user_read_idx');
        });

        // Post views table
        Schema::table('post_views', function (Blueprint $table) {
            $table->dropIndex('post_views_post_viewed_idx');
            $table->dropIndex('post_views_user_viewed_idx');
            $table->dropIndex('post_views_session_post_idx');
        });

        // Reactions table
        Schema::table('reactions', function (Blueprint $table) {
            $table->dropIndex('reactions_post_type_idx');
            $table->dropIndex('reactions_user_created_idx');
        });

        // Comment reactions table
        Schema::table('comment_reactions', function (Blueprint $table) {
            $table->dropIndex('comment_reactions_comment_type_idx');
        });

        // Follows table
        Schema::table('follows', function (Blueprint $table) {
            $table->dropIndex('follows_followed_created_idx');
            $table->dropIndex('follows_follower_created_idx');
        });

        // Activities table
        Schema::table('activities', function (Blueprint $table) {
            $table->dropIndex('activities_user_created_idx');
            $table->dropIndex('activities_user_type_created_idx');
            $table->dropIndex('activities_subject_idx');
        });

        // Notifications table
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_notifiable_read_idx');
            $table->dropIndex('notifications_notifiable_type_created_idx');
        });

        // Search logs table
        Schema::table('search_logs', function (Blueprint $table) {
            $table->dropIndex('search_logs_query_created_idx');
            $table->dropIndex('search_logs_user_created_idx');
        });

        // Newsletter subscribers table
        Schema::table('newsletters', function (Blueprint $table) {
            $table->dropIndex('newsletters_status_frequency_idx');
            $table->dropIndex('newsletters_verification_token_idx');
        });

        // Newsletter sends table
        Schema::table('newsletter_sends', function (Blueprint $table) {
            $table->dropIndex('newsletter_sends_newsletter_sent_idx');
            $table->dropIndex('newsletter_sends_tracking_token_idx');
            $table->dropIndex('newsletter_sends_subscriber_opened_idx');
        });

        // Recommendations table
        Schema::table('recommendations', function (Blueprint $table) {
            $table->dropIndex('recommendations_user_score_generated_idx');
            $table->dropIndex('recommendations_user_post_clicked_idx');
            $table->dropIndex('recommendations_user_reason_idx');
        });

        // Article similarities table
        Schema::table('post_similarities', function (Blueprint $table) {
            $table->dropIndex('post_similarities_post_score_idx');
        });

        // Tags table
        Schema::table('tags', function (Blueprint $table) {
            $table->dropIndex('tags_usage_count_idx');
        });

        // Post tag pivot table
        Schema::table('post_tag', function (Blueprint $table) {
            $table->dropIndex('post_tag_tag_id_idx');
        });

        // Categories table
        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex('categories_parent_order_idx');
            $table->dropIndex('categories_status_order_idx');
        });

        // Social shares table
        Schema::table('social_shares', function (Blueprint $table) {
            $table->dropIndex('social_shares_post_platform_shared_idx');
        });

        // Moderation queue table
        Schema::table('moderation_queue', function (Blueprint $table) {
            $table->dropIndex('moderation_queue_status_priority_created_idx');
            $table->dropIndex('moderation_queue_moderatable_idx');
        });

        // User reputation table
        Schema::table('user_reputation', function (Blueprint $table) {
            $table->dropIndex('user_reputation_trust_level_idx');
            $table->dropIndex('user_reputation_score_idx');
        });

        // Broken links table
        Schema::table('broken_links', function (Blueprint $table) {
            $table->dropIndex('broken_links_post_status_idx');
            $table->dropIndex('broken_links_url_idx');
        });

        // Feedback table
        Schema::table('feedback', function (Blueprint $table) {
            $table->dropIndex('feedback_user_created_idx');
            $table->dropIndex('feedback_type_created_idx');
        });

        // Users table
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_role_status_idx');
            $table->dropIndex('users_status_created_idx');
        });
    }
};

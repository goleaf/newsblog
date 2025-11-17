CREATE TABLE IF NOT EXISTS "migrations"(
  "id" integer primary key autoincrement not null,
  "migration" varchar not null,
  "batch" integer not null
);
CREATE TABLE IF NOT EXISTS "password_reset_tokens"(
  "email" varchar not null,
  "token" varchar not null,
  "created_at" datetime,
  primary key("email")
);
CREATE TABLE IF NOT EXISTS "sessions"(
  "id" varchar not null,
  "user_id" integer,
  "ip_address" varchar,
  "user_agent" text,
  "payload" text not null,
  "last_activity" integer not null,
  primary key("id")
);
CREATE INDEX "sessions_user_id_index" on "sessions"("user_id");
CREATE INDEX "sessions_last_activity_index" on "sessions"("last_activity");
CREATE TABLE IF NOT EXISTS "cache"(
  "key" varchar not null,
  "value" text not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE TABLE IF NOT EXISTS "cache_locks"(
  "key" varchar not null,
  "owner" varchar not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE TABLE IF NOT EXISTS "jobs"(
  "id" integer primary key autoincrement not null,
  "queue" varchar not null,
  "payload" text not null,
  "attempts" integer not null,
  "reserved_at" integer,
  "available_at" integer not null,
  "created_at" integer not null
);
CREATE INDEX "jobs_queue_index" on "jobs"("queue");
CREATE TABLE IF NOT EXISTS "job_batches"(
  "id" varchar not null,
  "name" varchar not null,
  "total_jobs" integer not null,
  "pending_jobs" integer not null,
  "failed_jobs" integer not null,
  "failed_job_ids" text not null,
  "options" text,
  "cancelled_at" integer,
  "created_at" integer not null,
  "finished_at" integer,
  primary key("id")
);
CREATE TABLE IF NOT EXISTS "failed_jobs"(
  "id" integer primary key autoincrement not null,
  "uuid" varchar not null,
  "connection" text not null,
  "queue" text not null,
  "payload" text not null,
  "exception" text not null,
  "failed_at" datetime not null default CURRENT_TIMESTAMP
);
CREATE UNIQUE INDEX "failed_jobs_uuid_unique" on "failed_jobs"("uuid");
CREATE TABLE IF NOT EXISTS "action_events"(
  "id" integer primary key autoincrement not null,
  "batch_id" varchar not null,
  "user_id" integer not null,
  "name" varchar not null,
  "actionable_type" varchar not null,
  "actionable_id" integer not null,
  "target_type" varchar not null,
  "target_id" integer not null,
  "model_type" varchar not null,
  "model_id" integer,
  "fields" text not null,
  "status" varchar not null default 'running',
  "exception" text not null,
  "created_at" datetime,
  "updated_at" datetime,
  "original" text,
  "changes" text
);
CREATE INDEX "action_events_actionable_type_actionable_id_index" on "action_events"(
  "actionable_type",
  "actionable_id"
);
CREATE INDEX "action_events_target_type_target_id_index" on "action_events"(
  "target_type",
  "target_id"
);
CREATE INDEX "action_events_batch_id_model_type_model_id_index" on "action_events"(
  "batch_id",
  "model_type",
  "model_id"
);
CREATE INDEX "action_events_user_id_index" on "action_events"("user_id");
CREATE TABLE IF NOT EXISTS "nova_notifications"(
  "id" varchar not null,
  "type" varchar not null,
  "notifiable_type" varchar not null,
  "notifiable_id" integer not null,
  "data" text not null,
  "read_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  primary key("id")
);
CREATE INDEX "nova_notifications_notifiable_type_notifiable_id_index" on "nova_notifications"(
  "notifiable_type",
  "notifiable_id"
);
CREATE TABLE IF NOT EXISTS "nova_pending_field_attachments"(
  "id" integer primary key autoincrement not null,
  "draft_id" varchar not null,
  "attachment" varchar not null,
  "disk" varchar not null,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE INDEX "nova_pending_field_attachments_draft_id_index" on "nova_pending_field_attachments"(
  "draft_id"
);
CREATE TABLE IF NOT EXISTS "nova_field_attachments"(
  "id" integer primary key autoincrement not null,
  "attachable_type" varchar not null,
  "attachable_id" integer not null,
  "attachment" varchar not null,
  "disk" varchar not null,
  "url" varchar not null,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE INDEX "nova_field_attachments_attachable_type_attachable_id_index" on "nova_field_attachments"(
  "attachable_type",
  "attachable_id"
);
CREATE INDEX "nova_field_attachments_url_index" on "nova_field_attachments"(
  "url"
);
CREATE TABLE IF NOT EXISTS "categories"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "slug" varchar not null,
  "description" text,
  "parent_id" integer,
  "icon" varchar,
  "color_code" varchar,
  "meta_title" varchar,
  "meta_description" text,
  "status" varchar check("status" in('active', 'inactive')) not null default 'active',
  "display_order" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  foreign key("parent_id") references "categories"("id") on delete cascade
);
CREATE INDEX "categories_slug_index" on "categories"("slug");
CREATE INDEX "categories_status_index" on "categories"("status");
CREATE INDEX "categories_display_order_index" on "categories"("display_order");
CREATE UNIQUE INDEX "categories_slug_unique" on "categories"("slug");
CREATE TABLE IF NOT EXISTS "tags"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "slug" varchar not null,
  "created_at" datetime,
  "updated_at" datetime,
  "description" text
);
CREATE INDEX "tags_slug_index" on "tags"("slug");
CREATE UNIQUE INDEX "tags_slug_unique" on "tags"("slug");
CREATE TABLE IF NOT EXISTS "comments"(
  "id" integer primary key autoincrement not null,
  "post_id" integer not null,
  "user_id" integer,
  "parent_id" integer,
  "author_name" varchar not null,
  "author_email" varchar not null,
  "content" text not null,
  "status" varchar check("status" in('pending', 'approved', 'spam', 'rejected')) not null default 'pending',
  "ip_address" varchar,
  "user_agent" text,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  foreign key("post_id") references "posts"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete cascade,
  foreign key("parent_id") references "comments"("id") on delete cascade
);
CREATE INDEX "comments_post_id_index" on "comments"("post_id");
CREATE INDEX "comments_status_index" on "comments"("status");
CREATE INDEX "comments_created_at_index" on "comments"("created_at");
CREATE TABLE IF NOT EXISTS "media_library"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "file_name" varchar not null,
  "file_path" varchar not null,
  "file_type" varchar not null,
  "file_size" integer not null,
  "mime_type" varchar not null,
  "alt_text" varchar,
  "title" varchar,
  "caption" text,
  "created_at" datetime,
  "updated_at" datetime,
  "metadata" text,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE INDEX "media_library_user_id_index" on "media_library"("user_id");
CREATE INDEX "media_library_file_type_index" on "media_library"("file_type");
CREATE TABLE IF NOT EXISTS "post_tag"(
  "post_id" integer not null,
  "tag_id" integer not null,
  foreign key("post_id") references "posts"("id") on delete cascade,
  foreign key("tag_id") references "tags"("id") on delete cascade,
  primary key("post_id", "tag_id")
);
CREATE TABLE IF NOT EXISTS "contact_messages"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "email" varchar not null,
  "subject" varchar not null,
  "message" text not null,
  "status" varchar check("status" in('new', 'read', 'replied')) not null default 'new',
  "created_at" datetime,
  "updated_at" datetime
);
CREATE INDEX "contact_messages_status_index" on "contact_messages"("status");
CREATE INDEX "contact_messages_created_at_index" on "contact_messages"(
  "created_at"
);
CREATE TABLE IF NOT EXISTS "reactions"(
  "id" integer primary key autoincrement not null,
  "post_id" integer not null,
  "user_id" integer,
  "type" varchar not null,
  "ip_address" varchar,
  "user_agent" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("post_id") references "posts"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE UNIQUE INDEX "reactions_post_id_user_id_type_unique" on "reactions"(
  "post_id",
  "user_id",
  "type"
);
CREATE INDEX "reactions_post_id_type_index" on "reactions"("post_id", "type");
CREATE TABLE IF NOT EXISTS "post_revisions"(
  "id" integer primary key autoincrement not null,
  "post_id" integer not null,
  "user_id" integer not null,
  "title" varchar not null,
  "content" text not null,
  "excerpt" text,
  "meta_data" text,
  "revision_note" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("post_id") references "posts"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE INDEX "post_revisions_post_id_index" on "post_revisions"("post_id");
CREATE TABLE IF NOT EXISTS "activity_logs"(
  "id" integer primary key autoincrement not null,
  "log_name" varchar,
  "description" text not null,
  "subject_type" varchar,
  "subject_id" integer,
  "event" varchar,
  "causer_type" varchar,
  "causer_id" integer,
  "properties" text,
  "ip_address" varchar,
  "user_agent" varchar,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE INDEX "activity_logs_subject_type_subject_id_index" on "activity_logs"(
  "subject_type",
  "subject_id"
);
CREATE INDEX "activity_logs_causer_type_causer_id_index" on "activity_logs"(
  "causer_type",
  "causer_id"
);
CREATE INDEX "activity_logs_log_name_index" on "activity_logs"("log_name");
CREATE TABLE IF NOT EXISTS "search_logs"(
  "id" integer primary key autoincrement not null,
  "query" varchar not null,
  "result_count" integer not null default '0',
  "execution_time" float,
  "search_type" varchar not null default 'posts',
  "fuzzy_enabled" tinyint(1) not null default '1',
  "threshold" integer,
  "filters" text,
  "ip_address" varchar,
  "user_agent" varchar,
  "user_id" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete set null
);
CREATE INDEX "search_logs_query_created_at_index" on "search_logs"(
  "query",
  "created_at"
);
CREATE INDEX "search_logs_result_count_index" on "search_logs"("result_count");
CREATE INDEX "search_logs_created_at_index" on "search_logs"("created_at");
CREATE TABLE IF NOT EXISTS "search_clicks"(
  "id" integer primary key autoincrement not null,
  "search_log_id" integer not null,
  "post_id" integer not null,
  "position" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("search_log_id") references "search_logs"("id") on delete cascade,
  foreign key("post_id") references "posts"("id") on delete cascade
);
CREATE INDEX "search_clicks_search_log_id_post_id_index" on "search_clicks"(
  "search_log_id",
  "post_id"
);
CREATE TABLE IF NOT EXISTS "feedback"(
  "id" integer primary key autoincrement not null,
  "user_id" integer,
  "type" varchar not null default 'general',
  "subject" varchar not null,
  "message" text not null,
  "status" varchar not null default 'new',
  "admin_notes" text,
  "reviewed_by" integer,
  "reviewed_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete set null,
  foreign key("reviewed_by") references "users"("id") on delete set null
);
CREATE INDEX "tags_name_index" on "tags"("name");
CREATE INDEX "categories_name_index" on "categories"("name");
CREATE INDEX "media_library_file_name_index" on "media_library"("file_name");
CREATE INDEX "media_library_created_at_index" on "media_library"("created_at");
CREATE INDEX "categories_parent_id_index" on "categories"("parent_id");
CREATE INDEX "activity_logs_event_index" on "activity_logs"("event");
CREATE INDEX "activity_logs_created_at_index" on "activity_logs"("created_at");
CREATE TABLE IF NOT EXISTS "series"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "slug" varchar not null,
  "description" text,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "series_slug_unique" on "series"("slug");
CREATE TABLE IF NOT EXISTS "broken_links"(
  "id" integer primary key autoincrement not null,
  "post_id" integer not null,
  "url" varchar not null,
  "checked_at" datetime,
  "response_code" integer,
  "error_message" varchar,
  "status" varchar check("status" in('ok', 'broken', 'ignored')) not null default 'broken',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("post_id") references "posts"("id") on delete cascade
);
CREATE INDEX "broken_links_post_id_status_index" on "broken_links"(
  "post_id",
  "status"
);
CREATE INDEX "broken_links_status_index" on "broken_links"("status");
CREATE TABLE IF NOT EXISTS "newsletters"(
  "id" integer primary key autoincrement not null,
  "email" varchar not null,
  "status" varchar not null default 'pending',
  "verified_at" datetime,
  "token" varchar,
  "verification_token" varchar,
  "verification_token_expires_at" datetime,
  "unsubscribe_token" varchar,
  "unsubscribed_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  "frequency" varchar check("frequency" in('daily', 'weekly', 'monthly')) not null default 'weekly'
);
CREATE INDEX "newsletters_email_index" on "newsletters"("email");
CREATE INDEX "newsletters_status_index" on "newsletters"("status");
CREATE INDEX "newsletters_verification_token_index" on "newsletters"(
  "verification_token"
);
CREATE INDEX "newsletters_unsubscribe_token_index" on "newsletters"(
  "unsubscribe_token"
);
CREATE UNIQUE INDEX "newsletters_email_unique" on "newsletters"("email");
CREATE TABLE IF NOT EXISTS "notifications"(
  "id" integer primary key autoincrement not null,
  "user_id" integer,
  "type" varchar,
  "title" varchar,
  "message" text,
  "action_url" varchar,
  "icon" varchar,
  "data" text,
  "read_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE INDEX "notifications_user_id_read_at_index" on "notifications"(
  "user_id",
  "read_at"
);
CREATE INDEX "notifications_user_id_created_at_index" on "notifications"(
  "user_id",
  "created_at"
);
CREATE TABLE IF NOT EXISTS "widget_areas"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "slug" varchar not null,
  "description" text,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "widget_areas_slug_unique" on "widget_areas"("slug");
CREATE TABLE IF NOT EXISTS "widgets"(
  "id" integer primary key autoincrement not null,
  "widget_area_id" integer not null,
  "type" varchar not null,
  "title" varchar not null,
  "settings" text,
  "order" integer not null default '0',
  "active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("widget_area_id") references "widget_areas"("id") on delete cascade
);
CREATE INDEX "widgets_widget_area_id_order_index" on "widgets"(
  "widget_area_id",
  "order"
);
CREATE TABLE IF NOT EXISTS "pages"(
  "id" integer primary key autoincrement not null,
  "title" varchar not null,
  "slug" varchar not null,
  "content" text not null,
  "meta_title" varchar,
  "meta_description" text,
  "status" varchar not null default('draft'),
  "template" varchar not null default('default'),
  "display_order" integer not null default('0'),
  "created_at" datetime,
  "updated_at" datetime,
  "parent_id" integer,
  foreign key("parent_id") references "pages"("id") on delete cascade
);
CREATE INDEX "pages_display_order_index" on "pages"("display_order");
CREATE INDEX "pages_slug_index" on "pages"("slug");
CREATE UNIQUE INDEX "pages_slug_unique" on "pages"("slug");
CREATE INDEX "pages_status_index" on "pages"("status");
CREATE INDEX "pages_parent_id_index" on "pages"("parent_id");
CREATE TABLE IF NOT EXISTS "category_post"(
  "id" integer primary key autoincrement not null,
  "post_id" integer not null,
  "category_id" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("post_id") references "posts"("id") on delete cascade,
  foreign key("category_id") references "categories"("id") on delete cascade
);
CREATE UNIQUE INDEX "category_post_post_id_category_id_unique" on "category_post"(
  "post_id",
  "category_id"
);
CREATE INDEX "post_tag_tag_id_index" on "post_tag"("tag_id");
CREATE INDEX "post_tag_post_id_index" on "post_tag"("post_id");
CREATE TABLE IF NOT EXISTS "engagement_metrics"(
  "id" integer primary key autoincrement not null,
  "post_id" integer not null,
  "session_id" varchar not null,
  "user_id" integer,
  "time_on_page" integer not null default '0',
  "scroll_depth" integer not null default '0',
  "clicked_bookmark" tinyint(1) not null default '0',
  "clicked_share" tinyint(1) not null default '0',
  "clicked_reaction" tinyint(1) not null default '0',
  "clicked_comment" tinyint(1) not null default '0',
  "clicked_related_post" tinyint(1) not null default '0',
  "ip_address" varchar,
  "user_agent" text,
  "referer" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("post_id") references "posts"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete set null
);
CREATE INDEX "engagement_metrics_post_id_created_at_index" on "engagement_metrics"(
  "post_id",
  "created_at"
);
CREATE INDEX "engagement_metrics_session_id_post_id_index" on "engagement_metrics"(
  "session_id",
  "post_id"
);
CREATE INDEX "engagement_metrics_session_id_index" on "engagement_metrics"(
  "session_id"
);
CREATE TABLE IF NOT EXISTS "bookmark_collections"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "name" varchar not null,
  "description" text,
  "is_public" tinyint(1) not null default '0',
  "order" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  "share_token" varchar,
  "view_count" integer not null default '0',
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE INDEX "bookmark_collections_user_id_order_index" on "bookmark_collections"(
  "user_id",
  "order"
);
CREATE TABLE IF NOT EXISTS "bookmarks"(
  "id" integer primary key autoincrement not null,
  "user_id" integer,
  "post_id" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  "collection_id" integer,
  "order" integer not null default '0',
  "reader_token" varchar,
  "is_read" tinyint(1) not null default '0',
  "read_at" datetime,
  "notes" text,
  foreign key("post_id") references posts("id") on delete cascade on update no action,
  foreign key("user_id") references users("id") on delete cascade on update no action,
  foreign key("collection_id") references "bookmark_collections"("id") on delete set null
);
CREATE INDEX "bookmarks_user_id_post_id_index" on "bookmarks"(
  "user_id",
  "post_id"
);
CREATE UNIQUE INDEX "bookmarks_user_id_post_id_unique" on "bookmarks"(
  "user_id",
  "post_id"
);
CREATE INDEX "bookmarks_collection_id_order_index" on "bookmarks"(
  "collection_id",
  "order"
);
CREATE TABLE IF NOT EXISTS "menus"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "location" varchar not null default 'header',
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "menus_location_unique" on "menus"("location");
CREATE INDEX "menus_location_index" on "menus"("location");
CREATE TABLE IF NOT EXISTS "settings"(
  "id" integer primary key autoincrement not null,
  "key" varchar not null,
  "value" text,
  "group" varchar not null default('general'),
  "created_at" datetime,
  "updated_at" datetime
);
CREATE INDEX "settings_group_index" on "settings"("group");
CREATE INDEX "settings_key_index" on "settings"("key");
CREATE UNIQUE INDEX "settings_key_unique" on "settings"("key");
CREATE TABLE IF NOT EXISTS "media"(
  "id" integer primary key autoincrement not null,
  "filename" varchar not null,
  "path" varchar not null,
  "mime_type" varchar not null,
  "size" integer not null,
  "alt_text" varchar,
  "caption" text,
  "metadata" text,
  "user_id" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete set null
);
CREATE INDEX "media_mime_type_index" on "media"("mime_type");
CREATE TABLE IF NOT EXISTS "menu_items"(
  "id" integer primary key autoincrement not null,
  "menu_id" integer not null,
  "parent_id" integer,
  "type" varchar not null default 'link',
  "title" varchar not null,
  "url" varchar,
  "reference_id" integer,
  "order" integer not null default '0',
  "css_class" varchar,
  "target" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("menu_id") references "menus"("id") on delete cascade,
  foreign key("parent_id") references "menu_items"("id") on delete cascade
);
CREATE INDEX "menu_items_menu_id_parent_id_order_index" on "menu_items"(
  "menu_id",
  "parent_id",
  "order"
);
CREATE INDEX "menu_items_type_index" on "menu_items"("type");
CREATE INDEX "menu_items_order_index" on "menu_items"("order");
CREATE TABLE IF NOT EXISTS "users"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "email" varchar not null,
  "email_verified_at" datetime,
  "password" varchar not null,
  "remember_token" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  "role" varchar not null default 'user',
  "avatar" varchar,
  "bio" text,
  "status" varchar not null default('active'),
  "two_factor_secret" text,
  "two_factor_recovery_codes" text,
  "two_factor_confirmed_at" datetime,
  "email_preferences" text
);
CREATE UNIQUE INDEX "users_email_unique" on "users"("email");
CREATE VIRTUAL TABLE posts_fts5 USING fts5(
  title,
  content,
  excerpt
)
/* posts_fts5(
  title,
  content,
  excerpt
) */;
CREATE TABLE IF NOT EXISTS 'posts_fts5_data'(id INTEGER PRIMARY KEY, block BLOB);
CREATE TABLE IF NOT EXISTS 'posts_fts5_idx'(
  segid,
  term,
  pgno,
  PRIMARY KEY(segid, term)
) WITHOUT ROWID;
CREATE TABLE IF NOT EXISTS 'posts_fts5_content'(id INTEGER PRIMARY KEY, c0, c1, c2);
CREATE TABLE IF NOT EXISTS 'posts_fts5_docsize'(id INTEGER PRIMARY KEY, sz BLOB);
CREATE TABLE IF NOT EXISTS 'posts_fts5_config'(k PRIMARY KEY, v) WITHOUT ROWID;
CREATE TABLE IF NOT EXISTS "post_views"(
  "id" integer primary key autoincrement not null,
  "post_id" integer not null,
  "ip_address" varchar not null,
  "user_agent" text,
  "viewed_at" datetime not null,
  "session_id" varchar not null,
  "referer" varchar,
  "user_id" integer,
  foreign key("post_id") references posts("id") on delete cascade on update no action,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE INDEX "post_views_post_id_index" on "post_views"("post_id");
CREATE INDEX "post_views_session_id_index" on "post_views"("session_id");
CREATE INDEX "post_views_viewed_at_index" on "post_views"("viewed_at");
CREATE INDEX "post_views_user_id_viewed_at_index" on "post_views"(
  "user_id",
  "viewed_at"
);
CREATE TABLE IF NOT EXISTS "posts"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "category_id" integer not null,
  "title" varchar not null,
  "slug" varchar not null,
  "excerpt" text,
  "content" text not null,
  "featured_image" varchar,
  "image_alt_text" varchar,
  "status" varchar not null default('draft'),
  "is_featured" tinyint(1) not null default('0'),
  "is_trending" tinyint(1) not null default('0'),
  "view_count" integer not null default('0'),
  "published_at" datetime,
  "scheduled_at" datetime,
  "reading_time" integer,
  "meta_title" varchar,
  "meta_description" text,
  "meta_keywords" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  "is_breaking" tinyint(1) not null default('0'),
  "is_sponsored" tinyint(1) not null default('0'),
  "is_editors_pick" tinyint(1) not null default('0'),
  "series_id" integer,
  "order_in_series" integer not null default '0',
  "editors_pick_order" integer,
  foreign key("category_id") references categories("id") on delete cascade on update no action,
  foreign key("user_id") references users("id") on delete cascade on update no action,
  foreign key("series_id") references "series"("id") on delete set null
);
CREATE INDEX "posts_category_id_status_index" on "posts"(
  "category_id",
  "status"
);
CREATE INDEX "posts_created_at_index" on "posts"("created_at");
CREATE INDEX "posts_is_featured_index" on "posts"("is_featured");
CREATE INDEX "posts_is_trending_index" on "posts"("is_trending");
CREATE INDEX "posts_published_at_index" on "posts"("published_at");
CREATE INDEX "posts_scheduled_lookup" on "posts"("status", "scheduled_at");
CREATE INDEX "posts_slug_index" on "posts"("slug");
CREATE UNIQUE INDEX "posts_slug_unique" on "posts"("slug");
CREATE INDEX "posts_status_index" on "posts"("status");
CREATE INDEX "posts_status_published_at_index" on "posts"(
  "status",
  "published_at"
);
CREATE INDEX "posts_title_index" on "posts"("title");
CREATE INDEX "posts_user_id_status_index" on "posts"("user_id", "status");
CREATE INDEX "posts_is_editors_pick_editors_pick_order_index" on "posts"(
  "is_editors_pick",
  "editors_pick_order"
);
CREATE INDEX "bookmarks_reader_token_index" on "bookmarks"("reader_token");
CREATE UNIQUE INDEX "bookmarks_reader_post_unique" on "bookmarks"(
  "reader_token",
  "post_id"
);
CREATE TABLE IF NOT EXISTS "personal_access_tokens"(
  "id" integer primary key autoincrement not null,
  "tokenable_type" varchar not null,
  "tokenable_id" integer not null,
  "name" text not null,
  "token" varchar not null,
  "abilities" text,
  "last_used_at" datetime,
  "expires_at" datetime,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE INDEX "personal_access_tokens_tokenable_type_tokenable_id_index" on "personal_access_tokens"(
  "tokenable_type",
  "tokenable_id"
);
CREATE UNIQUE INDEX "personal_access_tokens_token_unique" on "personal_access_tokens"(
  "token"
);
CREATE INDEX "personal_access_tokens_expires_at_index" on "personal_access_tokens"(
  "expires_at"
);
CREATE TABLE IF NOT EXISTS "user_profiles"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "display_name" varchar,
  "website_url" varchar,
  "location" varchar,
  "birthdate" date,
  "bio" text,
  "social_links" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE UNIQUE INDEX "user_profiles_user_id_unique" on "user_profiles"(
  "user_id"
);
CREATE INDEX "user_profiles_display_name_index" on "user_profiles"(
  "display_name"
);
CREATE TABLE IF NOT EXISTS "user_preferences"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "email_notifications" tinyint(1) not null default '1',
  "push_notifications" tinyint(1) not null default '0',
  "theme" varchar not null default 'system',
  "language" varchar,
  "data" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE UNIQUE INDEX "user_preferences_user_id_unique" on "user_preferences"(
  "user_id"
);
CREATE TABLE IF NOT EXISTS "social_accounts"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "provider" varchar not null,
  "provider_user_id" varchar not null,
  "username" varchar,
  "avatar_url" varchar,
  "profile_url" varchar,
  "token" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE UNIQUE INDEX "social_accounts_provider_provider_user_id_unique" on "social_accounts"(
  "provider",
  "provider_user_id"
);
CREATE INDEX "social_accounts_user_id_provider_index" on "social_accounts"(
  "user_id",
  "provider"
);
CREATE TABLE IF NOT EXISTS "comment_reactions"(
  "id" integer primary key autoincrement not null,
  "comment_id" integer not null,
  "user_id" integer,
  "type" varchar not null,
  "ip_address" varchar,
  "user_agent" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("comment_id") references "comments"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE UNIQUE INDEX "comment_reactions_comment_id_user_id_type_unique" on "comment_reactions"(
  "comment_id",
  "user_id",
  "type"
);
CREATE INDEX "comment_reactions_comment_id_type_index" on "comment_reactions"(
  "comment_id",
  "type"
);
CREATE TABLE IF NOT EXISTS "comment_flags"(
  "id" integer primary key autoincrement not null,
  "comment_id" integer not null,
  "user_id" integer,
  "reason" varchar not null,
  "notes" text,
  "status" varchar not null default 'open',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("comment_id") references "comments"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete set null
);
CREATE INDEX "comment_flags_comment_id_status_index" on "comment_flags"(
  "comment_id",
  "status"
);
CREATE INDEX "comment_flags_created_at_index" on "comment_flags"("created_at");
CREATE TABLE IF NOT EXISTS "reading_lists"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "name" varchar not null,
  "description" text,
  "is_public" tinyint(1) not null default '0',
  "order" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE INDEX "reading_lists_user_id_order_index" on "reading_lists"(
  "user_id",
  "order"
);
CREATE TABLE IF NOT EXISTS "reading_list_items"(
  "id" integer primary key autoincrement not null,
  "reading_list_id" integer not null,
  "post_id" integer not null,
  "order" integer not null default '0',
  "note" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("reading_list_id") references "reading_lists"("id") on delete cascade,
  foreign key("post_id") references "posts"("id") on delete cascade
);
CREATE UNIQUE INDEX "reading_list_items_reading_list_id_post_id_unique" on "reading_list_items"(
  "reading_list_id",
  "post_id"
);
CREATE INDEX "reading_list_items_reading_list_id_order_index" on "reading_list_items"(
  "reading_list_id",
  "order"
);
CREATE TABLE IF NOT EXISTS "traffic_sources"(
  "id" integer primary key autoincrement not null,
  "post_id" integer,
  "user_id" integer,
  "session_id" varchar,
  "referrer_url" varchar,
  "utm_source" varchar,
  "utm_medium" varchar,
  "utm_campaign" varchar,
  "utm_term" varchar,
  "utm_content" varchar,
  "ip_address" varchar,
  "user_agent" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("post_id") references "posts"("id") on delete set null,
  foreign key("user_id") references "users"("id") on delete set null
);
CREATE INDEX "traffic_sources_post_id_created_at_index" on "traffic_sources"(
  "post_id",
  "created_at"
);
CREATE INDEX "traffic_sources_utm_source_utm_medium_utm_campaign_index" on "traffic_sources"(
  "utm_source",
  "utm_medium",
  "utm_campaign"
);
CREATE INDEX "traffic_sources_session_id_index" on "traffic_sources"(
  "session_id"
);
CREATE INDEX "traffic_sources_created_at_index" on "traffic_sources"(
  "created_at"
);
CREATE TABLE IF NOT EXISTS "user_reading_history"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "post_id" integer not null,
  "last_read_at" datetime,
  "progress_percent" integer not null default '0',
  "total_time_seconds" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade,
  foreign key("post_id") references "posts"("id") on delete cascade
);
CREATE UNIQUE INDEX "user_reading_history_user_id_post_id_unique" on "user_reading_history"(
  "user_id",
  "post_id"
);
CREATE INDEX "user_reading_history_last_read_at_index" on "user_reading_history"(
  "last_read_at"
);
CREATE TABLE IF NOT EXISTS "follows"(
  "id" integer primary key autoincrement not null,
  "follower_id" integer not null,
  "followed_id" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("follower_id") references "users"("id") on delete cascade,
  foreign key("followed_id") references "users"("id") on delete cascade
);
CREATE UNIQUE INDEX "follows_follower_id_followed_id_unique" on "follows"(
  "follower_id",
  "followed_id"
);
CREATE INDEX "follows_created_at_index" on "follows"("created_at");
CREATE TABLE IF NOT EXISTS "activities"(
  "id" integer primary key autoincrement not null,
  "actor_id" integer,
  "verb" varchar not null,
  "subject_type" varchar not null,
  "subject_id" integer not null,
  "meta" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("actor_id") references "users"("id") on delete set null
);
CREATE INDEX "activities_subject_type_subject_id_index" on "activities"(
  "subject_type",
  "subject_id"
);
CREATE INDEX "activities_actor_id_created_at_index" on "activities"(
  "actor_id",
  "created_at"
);
CREATE TABLE IF NOT EXISTS "social_shares"(
  "id" integer primary key autoincrement not null,
  "post_id" integer not null,
  "user_id" integer,
  "provider" varchar not null,
  "share_url" varchar,
  "shared_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("post_id") references "posts"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete set null
);
CREATE INDEX "social_shares_post_id_provider_index" on "social_shares"(
  "post_id",
  "provider"
);
CREATE INDEX "social_shares_shared_at_index" on "social_shares"("shared_at");
CREATE TABLE IF NOT EXISTS "notification_preferences"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "email_enabled" tinyint(1) not null default '1',
  "push_enabled" tinyint(1) not null default '0',
  "digest_frequency" varchar not null default 'weekly',
  "channels" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE UNIQUE INDEX "notification_preferences_user_id_unique" on "notification_preferences"(
  "user_id"
);
CREATE TABLE IF NOT EXISTS "moderation_queue"(
  "id" integer primary key autoincrement not null,
  "subject_type" varchar not null,
  "subject_id" integer not null,
  "reported_by" integer,
  "reason" varchar not null,
  "notes" text,
  "status" varchar not null default 'pending',
  "priority" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("reported_by") references "users"("id") on delete set null
);
CREATE INDEX "moderation_queue_subject_type_subject_id_index" on "moderation_queue"(
  "subject_type",
  "subject_id"
);
CREATE INDEX "moderation_queue_status_priority_index" on "moderation_queue"(
  "status",
  "priority"
);
CREATE INDEX "moderation_queue_created_at_index" on "moderation_queue"(
  "created_at"
);
CREATE TABLE IF NOT EXISTS "user_reputation"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "score" integer not null default '0',
  "level" varchar not null default 'new',
  "meta" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE UNIQUE INDEX "user_reputation_user_id_unique" on "user_reputation"(
  "user_id"
);
CREATE TABLE IF NOT EXISTS "moderation_actions"(
  "id" integer primary key autoincrement not null,
  "moderation_queue_id" integer not null,
  "performed_by" integer,
  "action" varchar not null,
  "notes" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("moderation_queue_id") references "moderation_queue"("id") on delete cascade,
  foreign key("performed_by") references "users"("id") on delete set null
);
CREATE INDEX "moderation_actions_moderation_queue_id_created_at_index" on "moderation_actions"(
  "moderation_queue_id",
  "created_at"
);
CREATE TABLE IF NOT EXISTS "post_similarities"(
  "id" integer primary key autoincrement not null,
  "post_id" integer not null,
  "similar_post_id" integer not null,
  "score" float not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("post_id") references "posts"("id") on delete cascade,
  foreign key("similar_post_id") references "posts"("id") on delete cascade
);
CREATE UNIQUE INDEX "post_similarities_post_id_similar_post_id_unique" on "post_similarities"(
  "post_id",
  "similar_post_id"
);
CREATE INDEX "post_similarities_post_id_score_index" on "post_similarities"(
  "post_id",
  "score"
);
CREATE TABLE IF NOT EXISTS "recommendations"(
  "id" integer primary key autoincrement not null,
  "user_id" integer,
  "post_id" integer not null,
  "reason" varchar,
  "score" float not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  "clicked" tinyint(1) not null default '0',
  "generated_at" datetime,
  "clicked_at" datetime,
  "impressions" integer not null default '0',
  foreign key("user_id") references "users"("id") on delete set null,
  foreign key("post_id") references "posts"("id") on delete cascade
);
CREATE UNIQUE INDEX "recommendations_user_id_post_id_unique" on "recommendations"(
  "user_id",
  "post_id"
);
CREATE INDEX "recommendations_score_created_at_index" on "recommendations"(
  "score",
  "created_at"
);
CREATE TABLE IF NOT EXISTS "newsletter_sends"(
  "id" integer primary key autoincrement not null,
  "subscriber_id" integer not null,
  "batch_id" varchar,
  "subject" varchar not null,
  "content" text not null,
  "status" varchar not null default 'queued',
  "sent_at" datetime,
  "provider_message_id" varchar,
  "error" text,
  "created_at" datetime,
  "updated_at" datetime,
  "opened_at" datetime,
  "clicked_at" datetime,
  "click_count" integer not null default '0',
  "clicked_links" text,
  foreign key("subscriber_id") references "newsletters"("id") on delete cascade
);
CREATE INDEX "newsletter_sends_subscriber_id_status_index" on "newsletter_sends"(
  "subscriber_id",
  "status"
);
CREATE INDEX "newsletter_sends_batch_id_index" on "newsletter_sends"(
  "batch_id"
);
CREATE INDEX "newsletter_sends_sent_at_index" on "newsletter_sends"("sent_at");
CREATE UNIQUE INDEX "bookmark_collections_share_token_unique" on "bookmark_collections"(
  "share_token"
);
CREATE INDEX "recommendations_clicked_index" on "recommendations"("clicked");
CREATE INDEX "recommendations_generated_at_index" on "recommendations"(
  "generated_at"
);
CREATE INDEX "posts_status_view_count_idx" on "posts"("status", "view_count");
CREATE INDEX "posts_status_published_created_idx" on "posts"(
  "status",
  "published_at",
  "created_at"
);
CREATE INDEX "posts_featured_published_idx" on "posts"(
  "is_featured",
  "published_at"
);
CREATE INDEX "posts_trending_published_idx" on "posts"(
  "is_trending",
  "published_at"
);
CREATE INDEX "posts_editors_pick_published_idx" on "posts"(
  "is_editors_pick",
  "published_at"
);
CREATE INDEX "posts_series_id_idx" on "posts"("series_id");
CREATE INDEX "comments_post_status_created_idx" on "comments"(
  "post_id",
  "status",
  "created_at"
);
CREATE INDEX "comments_parent_status_idx" on "comments"("parent_id", "status");
CREATE INDEX "comments_user_created_idx" on "comments"(
  "user_id",
  "created_at"
);
CREATE INDEX "bookmarks_user_collection_created_idx" on "bookmarks"(
  "user_id",
  "collection_id",
  "created_at"
);
CREATE INDEX "bookmarks_post_created_idx" on "bookmarks"(
  "post_id",
  "created_at"
);
CREATE INDEX "bookmarks_user_read_idx" on "bookmarks"("user_id", "is_read");
CREATE INDEX "post_views_post_viewed_idx" on "post_views"(
  "post_id",
  "viewed_at"
);
CREATE INDEX "post_views_user_viewed_idx" on "post_views"(
  "user_id",
  "viewed_at"
);
CREATE INDEX "post_views_session_post_idx" on "post_views"(
  "session_id",
  "post_id"
);
CREATE INDEX "reactions_post_type_idx" on "reactions"(
  "post_id",
  "reaction_type"
);
CREATE INDEX "reactions_user_created_idx" on "reactions"(
  "user_id",
  "created_at"
);
CREATE INDEX "comment_reactions_comment_type_idx" on "comment_reactions"(
  "comment_id",
  "reaction_type"
);
CREATE INDEX "follows_followed_created_idx" on "follows"(
  "followed_id",
  "created_at"
);
CREATE INDEX "follows_follower_created_idx" on "follows"(
  "follower_id",
  "created_at"
);
CREATE INDEX "activities_user_created_idx" on "activities"(
  "user_id",
  "created_at"
);
CREATE INDEX "activities_user_type_created_idx" on "activities"(
  "user_id",
  "activity_type",
  "created_at"
);
CREATE INDEX "activities_subject_idx" on "activities"(
  "subject_type",
  "subject_id"
);
CREATE INDEX "notifications_notifiable_read_idx" on "notifications"(
  "notifiable_id",
  "notifiable_type",
  "read_at"
);
CREATE INDEX "notifications_notifiable_type_created_idx" on "notifications"(
  "notifiable_id",
  "type",
  "created_at"
);
CREATE INDEX "search_logs_query_created_idx" on "search_logs"(
  "query",
  "created_at"
);
CREATE INDEX "search_logs_user_created_idx" on "search_logs"(
  "user_id",
  "created_at"
);
CREATE INDEX "newsletters_status_frequency_idx" on "newsletters"(
  "status",
  "frequency"
);
CREATE INDEX "newsletters_verification_token_idx" on "newsletters"(
  "verification_token"
);
CREATE INDEX "newsletter_sends_newsletter_sent_idx" on "newsletter_sends"(
  "newsletter_id",
  "sent_at"
);
CREATE INDEX "newsletter_sends_tracking_token_idx" on "newsletter_sends"(
  "tracking_token"
);
CREATE INDEX "newsletter_sends_subscriber_opened_idx" on "newsletter_sends"(
  "subscriber_id",
  "opened_at"
);
CREATE INDEX "recommendations_user_score_generated_idx" on "recommendations"(
  "user_id",
  "score",
  "generated_at"
);
CREATE INDEX "recommendations_user_post_clicked_idx" on "recommendations"(
  "user_id",
  "post_id",
  "clicked"
);
CREATE INDEX "recommendations_user_reason_idx" on "recommendations"(
  "user_id",
  "reason"
);
CREATE INDEX "post_similarities_post_score_idx" on "post_similarities"(
  "post_id",
  "similarity_score"
);
CREATE INDEX "tags_usage_count_idx" on "tags"("usage_count");
CREATE INDEX "post_tag_tag_id_idx" on "post_tag"("tag_id");
CREATE INDEX "categories_parent_order_idx" on "categories"(
  "parent_id",
  "display_order"
);
CREATE INDEX "categories_status_order_idx" on "categories"(
  "status",
  "display_order"
);
CREATE INDEX "social_shares_post_platform_shared_idx" on "social_shares"(
  "post_id",
  "platform",
  "shared_at"
);
CREATE INDEX "moderation_queue_status_priority_created_idx" on "moderation_queue"(
  "status",
  "priority",
  "created_at"
);
CREATE INDEX "moderation_queue_moderatable_idx" on "moderation_queue"(
  "moderatable_type",
  "moderatable_id"
);
CREATE INDEX "user_reputation_trust_level_idx" on "user_reputation"(
  "trust_level"
);
CREATE INDEX "user_reputation_score_idx" on "user_reputation"(
  "reputation_score"
);
CREATE INDEX "broken_links_post_status_idx" on "broken_links"(
  "post_id",
  "status"
);
CREATE INDEX "broken_links_url_idx" on "broken_links"("url");
CREATE INDEX "feedback_user_created_idx" on "feedback"(
  "user_id",
  "created_at"
);
CREATE INDEX "feedback_type_created_idx" on "feedback"("type", "created_at");
CREATE INDEX "users_role_status_idx" on "users"("role", "status");
CREATE INDEX "users_status_created_idx" on "users"("status", "created_at");

INSERT INTO migrations VALUES(1,'0001_01_01_000000_create_users_table',1);
INSERT INTO migrations VALUES(2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO migrations VALUES(3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO migrations VALUES(4,'2018_01_01_000000_create_action_events_table',1);
INSERT INTO migrations VALUES(5,'2019_05_10_000000_add_fields_to_action_events_table',1);
INSERT INTO migrations VALUES(6,'2021_08_25_193039_create_nova_notifications_table',1);
INSERT INTO migrations VALUES(7,'2022_04_26_000000_add_fields_to_nova_notifications_table',1);
INSERT INTO migrations VALUES(8,'2022_12_19_000000_create_field_attachments_table',1);
INSERT INTO migrations VALUES(9,'2025_11_11_190307_add_fields_to_users_table',1);
INSERT INTO migrations VALUES(10,'2025_11_11_190308_create_categories_table',1);
INSERT INTO migrations VALUES(11,'2025_11_11_190309_create_posts_table',1);
INSERT INTO migrations VALUES(12,'2025_11_11_190309_create_tags_table',1);
INSERT INTO migrations VALUES(13,'2025_11_11_190310_create_comments_table',1);
INSERT INTO migrations VALUES(14,'2025_11_11_190310_create_media_library_table',1);
INSERT INTO migrations VALUES(15,'2025_11_11_190310_create_post_tag_table',1);
INSERT INTO migrations VALUES(16,'2025_11_11_190311_create_newsletters_table',1);
INSERT INTO migrations VALUES(17,'2025_11_11_190311_create_pages_table',1);
INSERT INTO migrations VALUES(18,'2025_11_11_190311_create_settings_table',1);
INSERT INTO migrations VALUES(19,'2025_11_11_190312_create_contact_messages_table',1);
INSERT INTO migrations VALUES(20,'2025_11_11_190312_create_post_views_table',1);
INSERT INTO migrations VALUES(21,'2025_11_11_203950_create_bookmarks_table',1);
INSERT INTO migrations VALUES(22,'2025_11_11_203952_create_reactions_table',1);
INSERT INTO migrations VALUES(23,'2025_11_11_203954_create_post_revisions_table',1);
INSERT INTO migrations VALUES(24,'2025_11_11_210000_create_activity_logs_table',1);
INSERT INTO migrations VALUES(25,'2025_11_11_231724_add_scheduled_posts_index_to_posts_table',1);
INSERT INTO migrations VALUES(26,'2025_11_11_232752_add_metadata_to_media_library_table',1);
INSERT INTO migrations VALUES(27,'2025_11_12_000752_create_search_logs_table',1);
INSERT INTO migrations VALUES(28,'2025_11_12_000821_create_search_clicks_table',1);
INSERT INTO migrations VALUES(29,'2025_11_12_013611_add_performance_indexes_to_posts_table',1);
INSERT INTO migrations VALUES(30,'2025_11_12_023012_add_two_factor_columns_to_users_table',1);
INSERT INTO migrations VALUES(31,'2025_11_12_025506_add_suspended_to_users_status',1);
INSERT INTO migrations VALUES(32,'2025_11_12_025512_add_user_to_users_role',1);
INSERT INTO migrations VALUES(33,'2025_11_12_030914_create_feedback_table',1);
INSERT INTO migrations VALUES(34,'2025_11_12_032054_add_performance_indexes_for_nova_resources',1);
INSERT INTO migrations VALUES(35,'2025_11_12_103246_add_additional_nova_performance_indexes',1);
INSERT INTO migrations VALUES(36,'2025_11_12_114159_add_fulltext_index_to_posts_table',1);
INSERT INTO migrations VALUES(37,'2025_11_12_124637_create_series_table',1);
INSERT INTO migrations VALUES(38,'2025_11_12_124700_create_post_series_table',1);
INSERT INTO migrations VALUES(39,'2025_11_12_132035_add_session_id_and_referer_to_post_views_table',1);
INSERT INTO migrations VALUES(40,'2025_11_12_141818_create_broken_links_table',1);
INSERT INTO migrations VALUES(41,'2025_11_12_153239_add_newsletter_verification_fields',1);
INSERT INTO migrations VALUES(42,'2025_11_12_153919_update_newsletter_status_enum',1);
INSERT INTO migrations VALUES(43,'2025_11_12_154516_recreate_newsletters_table_with_pending_status',1);
INSERT INTO migrations VALUES(44,'2025_11_12_154746_create_notifications_table',1);
INSERT INTO migrations VALUES(45,'2025_11_13_132146_create_widget_areas_table',1);
INSERT INTO migrations VALUES(46,'2025_11_13_132154_create_widgets_table',1);
INSERT INTO migrations VALUES(47,'2025_11_13_140316_add_parent_id_to_pages_table',1);
INSERT INTO migrations VALUES(48,'2025_11_13_210600_add_soft_deletes_to_categories_table',1);
INSERT INTO migrations VALUES(49,'2025_11_13_220700_create_category_post_pivot_table',1);
INSERT INTO migrations VALUES(50,'2025_11_13_221500_add_indexes_to_post_tag_table',1);
INSERT INTO migrations VALUES(51,'2025_11_14_040425_add_email_preferences_to_users_table',1);
INSERT INTO migrations VALUES(52,'2025_11_14_062720_create_engagement_metrics_table',1);
INSERT INTO migrations VALUES(53,'2025_11_15_154844_create_bookmark_collections_table',1);
INSERT INTO migrations VALUES(54,'2025_11_15_154913_add_collection_id_to_bookmarks_table',1);
INSERT INTO migrations VALUES(55,'2025_11_16_000000_create_menus_table',1);
INSERT INTO migrations VALUES(56,'2025_11_16_000001_alter_settings_value_to_json',1);
INSERT INTO migrations VALUES(57,'2025_11_16_000001_create_media_table',1);
INSERT INTO migrations VALUES(58,'2025_11_16_000001_create_menu_items_table',1);
INSERT INTO migrations VALUES(59,'2025_11_16_000001_update_default_user_role_to_user',1);
INSERT INTO migrations VALUES(60,'2025_11_16_005600_add_post_flags_fields_to_posts_table',1);
INSERT INTO migrations VALUES(61,'2025_11_16_012041_add_image_to_categories_table',1);
INSERT INTO migrations VALUES(62,'2025_11_16_013635_add_description_to_tags_table',1);
INSERT INTO migrations VALUES(63,'2025_11_16_015346_create_posts_fts5_virtual_table',1);
INSERT INTO migrations VALUES(64,'2025_11_16_020024_add_user_id_to_post_views_table',1);
INSERT INTO migrations VALUES(65,'2025_11_16_022425_refactor_series_to_one_to_many',1);
INSERT INTO migrations VALUES(66,'2025_11_16_110000_add_editors_pick_order_to_posts_table',1);
INSERT INTO migrations VALUES(67,'2025_11_16_141500_add_reader_token_to_bookmarks_table',1);
INSERT INTO migrations VALUES(68,'2025_11_16_150417_create_personal_access_tokens_table',1);
INSERT INTO migrations VALUES(69,'2025_11_16_150726_create_user_profiles_table',1);
INSERT INTO migrations VALUES(70,'2025_11_16_150729_create_user_preferences_table',1);
INSERT INTO migrations VALUES(71,'2025_11_16_150734_create_social_accounts_table',1);
INSERT INTO migrations VALUES(72,'2025_11_16_150736_create_comment_reactions_table',1);
INSERT INTO migrations VALUES(73,'2025_11_16_150739_create_comment_flags_table',1);
INSERT INTO migrations VALUES(74,'2025_11_16_150744_create_reading_lists_table',1);
INSERT INTO migrations VALUES(75,'2025_11_16_150747_create_reading_list_items_table',1);
INSERT INTO migrations VALUES(76,'2025_11_16_150748_create_traffic_sources_table',1);
INSERT INTO migrations VALUES(77,'2025_11_16_150753_create_user_reading_history_table',1);
INSERT INTO migrations VALUES(78,'2025_11_16_150758_create_follows_table',1);
INSERT INTO migrations VALUES(79,'2025_11_16_150804_create_activities_table',1);
INSERT INTO migrations VALUES(80,'2025_11_16_150811_create_social_shares_table',1);
INSERT INTO migrations VALUES(81,'2025_11_16_150815_create_notification_preferences_table',1);
INSERT INTO migrations VALUES(82,'2025_11_16_150821_create_moderation_queue_table',1);
INSERT INTO migrations VALUES(83,'2025_11_16_150827_create_user_reputation_table',1);
INSERT INTO migrations VALUES(84,'2025_11_16_150831_create_moderation_actions_table',1);
INSERT INTO migrations VALUES(85,'2025_11_16_150839_create_post_similarities_table',1);
INSERT INTO migrations VALUES(86,'2025_11_16_150845_create_recommendations_table',1);
INSERT INTO migrations VALUES(87,'2025_11_16_151107_create_newsletter_sends_table',1);
INSERT INTO migrations VALUES(88,'2025_11_16_170500_add_checked_at_and_response_code_to_broken_links_table',1);
INSERT INTO migrations VALUES(89,'2025_11_16_171000_update_broken_links_status_enum',1);
INSERT INTO migrations VALUES(90,'2025_11_16_171500_create_follows_table',1);
INSERT INTO migrations VALUES(91,'2025_11_16_172000_drop_legacy_columns_from_broken_links_table',1);
INSERT INTO migrations VALUES(92,'2025_11_16_210000_add_share_token_to_bookmark_collections_table',1);
INSERT INTO migrations VALUES(93,'2025_11_16_215338_add_read_status_and_notes_to_bookmarks_table',1);
INSERT INTO migrations VALUES(94,'2025_11_16_221052_add_view_count_to_bookmark_collections_table',1);
INSERT INTO migrations VALUES(95,'2025_11_16_225802_add_frequency_to_newsletters_table',1);
INSERT INTO migrations VALUES(96,'2025_11_16_230910_add_tracking_fields_to_newsletter_sends_table',1);
INSERT INTO migrations VALUES(97,'2025_11_16_233636_add_tracking_fields_to_recommendations_table',1);
INSERT INTO migrations VALUES(98,'2025_11_17_002940_add_comprehensive_database_indexes',2);

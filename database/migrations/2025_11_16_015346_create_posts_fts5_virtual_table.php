<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates FTS5 virtual table for full-text search on posts.
     * FTS5 provides fast full-text search capabilities in SQLite.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        // Only create FTS5 virtual table for SQLite
        if ($driver === 'sqlite') {
            // Create FTS5 virtual table for posts
            DB::statement('
                CREATE VIRTUAL TABLE IF NOT EXISTS posts_fts5 USING fts5(
                    title,
                    content,
                    excerpt
                );
            ');

            // Populate FTS5 table with existing posts in batches to avoid memory issues
            // Use chunking with raw queries to minimize memory usage
            \App\Models\Post::query()
                ->where('status', 'published')
                ->where(function ($q) {
                    $q->whereNull('published_at')
                        ->orWhere('published_at', '<=', now());
                })
                ->chunkById(100, function ($posts) {
                    foreach ($posts as $post) {
                        try {
                            DB::statement('
                                INSERT INTO posts_fts5(rowid, title, content, excerpt)
                                VALUES(?, ?, ?, ?)
                            ', [
                                $post->id,
                                $post->title ?? '',
                                $post->content ?? '',
                                $post->excerpt ?? '',
                            ]);
                        } catch (\Exception $e) {
                            // Skip if already exists or other error
                            continue;
                        }
                    }
                });

            // Create trigger to keep FTS5 table in sync when posts are inserted
            DB::statement('
                CREATE TRIGGER IF NOT EXISTS posts_fts5_insert AFTER INSERT ON posts BEGIN
                    INSERT INTO posts_fts5(rowid, title, content, excerpt)
                    VALUES(new.id, new.title, new.content, COALESCE(new.excerpt, ""));
                END
            ');

            // Create trigger to keep FTS5 table in sync when posts are updated
            DB::statement('
                CREATE TRIGGER IF NOT EXISTS posts_fts5_update AFTER UPDATE ON posts BEGIN
                    UPDATE posts_fts5
                    SET title = new.title,
                        content = new.content,
                        excerpt = COALESCE(new.excerpt, "")
                    WHERE rowid = new.id;
                END
            ');

            // Create trigger to keep FTS5 table in sync when posts are deleted
            DB::statement('
                CREATE TRIGGER IF NOT EXISTS posts_fts5_delete AFTER DELETE ON posts BEGIN
                    DELETE FROM posts_fts5 WHERE rowid = old.id;
                END
            ');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            // Drop triggers
            DB::statement('DROP TRIGGER IF EXISTS posts_fts5_insert');
            DB::statement('DROP TRIGGER IF EXISTS posts_fts5_update');
            DB::statement('DROP TRIGGER IF EXISTS posts_fts5_delete');

            // Drop FTS5 virtual table
            DB::statement('DROP TABLE IF EXISTS posts_fts5');
        }
    }
};

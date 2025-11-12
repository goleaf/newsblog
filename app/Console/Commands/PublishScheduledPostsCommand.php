<?php

namespace App\Console\Commands;

use App\Jobs\SendPostPublishedNotification;
use App\Models\Post;
use Illuminate\Console\Command;

class PublishScheduledPostsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'posts:publish-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish scheduled posts that are ready to be published';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $posts = Post::where('status', 'scheduled')
            ->where('scheduled_at', '<=', now())
            ->with('user:id,email,name')
            ->select('id', 'user_id', 'title', 'slug', 'status', 'scheduled_at', 'published_at')
            ->get();

        if ($posts->isEmpty()) {
            $this->info('No scheduled posts ready to publish.');

            return self::SUCCESS;
        }

        $count = 0;

        foreach ($posts as $post) {
            $post->update([
                'status' => 'published',
                'published_at' => $post->scheduled_at ?? now(),
            ]);

            // Queue notification email to post author
            SendPostPublishedNotification::dispatch($post);

            $count++;
        }

        $this->info("Published {$count} scheduled post(s).");

        return self::SUCCESS;
    }
}

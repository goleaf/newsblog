<?php

namespace App\Jobs;

use App\Mail\PostPublishedMail;
use App\Models\Post;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendPostPublishedNotification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Post $post
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Send email notification to the post author
        Mail::to($this->post->user->email)
            ->send(new PostPublishedMail($this->post));
    }
}

<?php

namespace App\Jobs;

use App\Mail\CommentReplyMail;
use App\Models\Comment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendCommentReplyNotification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public Comment $reply) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Get the parent comment
        $parentComment = $this->reply->parent;

        if (! $parentComment) {
            return;
        }

        // Send notification email to the parent commenter
        Mail::to($parentComment->author_email)
            ->send(new CommentReplyMail($this->reply, $parentComment));
    }
}

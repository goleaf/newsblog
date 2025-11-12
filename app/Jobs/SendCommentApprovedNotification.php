<?php

namespace App\Jobs;

use App\Mail\CommentApprovedMail;
use App\Models\Comment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendCommentApprovedNotification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public Comment $comment) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Send notification email to the post author
        Mail::to($this->comment->post->user->email)
            ->send(new CommentApprovedMail($this->comment));
    }
}

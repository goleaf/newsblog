<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CommentReplyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Comment $comment,
        public Comment $parentComment
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        // Check user preferences for email notifications
        if ($notifiable->wantsEmailNotification('comment_replies')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $postTitle = $this->comment->post->title;
        $authorName = $this->comment->user->name;

        return (new MailMessage)
            ->subject("New reply to your comment on \"{$postTitle}\"")
            ->greeting("Hello {$notifiable->name}!")
            ->line("{$authorName} replied to your comment on \"{$postTitle}\".")
            ->line('**Reply:**')
            ->line($this->comment->content)
            ->action('View Reply', route('post.show', $this->comment->post->slug).'#comment-'.$this->comment->id)
            ->line('Thank you for being part of our community!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'comment_reply',
            'comment_id' => $this->comment->id,
            'parent_comment_id' => $this->parentComment->id,
            'post_id' => $this->comment->post_id,
            'post_title' => $this->comment->post->title,
            'post_slug' => $this->comment->post->slug,
            'author_id' => $this->comment->user_id,
            'author_name' => $this->comment->user->name,
            'author_avatar' => $this->comment->user->avatar_url,
            'content_excerpt' => \Illuminate\Support\Str::limit($this->comment->content, 100),
            'action_url' => route('post.show', $this->comment->post->slug).'#comment-'.$this->comment->id,
        ];
    }

    /**
     * Get the notification's database type.
     */
    public function databaseType(object $notifiable): string
    {
        return 'comment_reply';
    }
}

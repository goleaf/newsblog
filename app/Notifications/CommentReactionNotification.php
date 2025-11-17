<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\CommentReaction;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CommentReactionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public CommentReaction $reaction,
        public Comment $comment,
        public User $reactor
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
        if ($notifiable->wantsEmailNotification('comment_reactions')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $reactionType = ucfirst($this->reaction->type);
        $postTitle = $this->comment->post->title;

        return (new MailMessage)
            ->subject("{$this->reactor->name} reacted to your comment")
            ->greeting("Hello {$notifiable->name}!")
            ->line("{$this->reactor->name} reacted with {$reactionType} to your comment on \"{$postTitle}\".")
            ->line('**Your comment:**')
            ->line(\Illuminate\Support\Str::limit($this->comment->content, 150))
            ->action('View Comment', route('post.show', $this->comment->post->slug).'#comment-'.$this->comment->id)
            ->line('Thank you for contributing to our community!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'comment_reaction',
            'reaction_id' => $this->reaction->id,
            'reaction_type' => $this->reaction->type,
            'comment_id' => $this->comment->id,
            'comment_excerpt' => \Illuminate\Support\Str::limit($this->comment->content, 100),
            'post_id' => $this->comment->post_id,
            'post_title' => $this->comment->post->title,
            'post_slug' => $this->comment->post->slug,
            'reactor_id' => $this->reactor->id,
            'reactor_name' => $this->reactor->name,
            'reactor_avatar' => $this->reactor->avatar_url,
            'action_url' => route('post.show', $this->comment->post->slug).'#comment-'.$this->comment->id,
        ];
    }

    /**
     * Get the notification's database type.
     */
    public function databaseType(object $notifiable): string
    {
        return 'comment_reaction';
    }
}

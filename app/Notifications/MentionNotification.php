<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MentionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public User $mentioner,
        public string $mentionableType,
        public int $mentionableId,
        public string $content
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
        if ($notifiable->wantsEmailNotification('mentions')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mentionableModel = $this->getMentionableModel();
        $context = $this->getContext($mentionableModel);

        return (new MailMessage)
            ->subject("{$this->mentioner->name} mentioned you")
            ->greeting("Hello {$notifiable->name}!")
            ->line("{$this->mentioner->name} mentioned you in {$context}.")
            ->line('**Content:**')
            ->line(\Illuminate\Support\Str::limit($this->content, 150))
            ->action('View Mention', $this->getActionUrl($mentionableModel))
            ->line('Thank you for being part of our community!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $mentionableModel = $this->getMentionableModel();

        return [
            'type' => 'mention',
            'mentioner_id' => $this->mentioner->id,
            'mentioner_name' => $this->mentioner->name,
            'mentioner_avatar' => $this->mentioner->avatar_url,
            'mentionable_type' => $this->mentionableType,
            'mentionable_id' => $this->mentionableId,
            'content_excerpt' => \Illuminate\Support\Str::limit($this->content, 100),
            'context' => $this->getContext($mentionableModel),
            'action_url' => $this->getActionUrl($mentionableModel),
        ];
    }

    /**
     * Get the notification's database type.
     */
    public function databaseType(object $notifiable): string
    {
        return 'mention';
    }

    /**
     * Get the mentionable model instance.
     */
    protected function getMentionableModel(): Comment|Post|null
    {
        return match ($this->mentionableType) {
            'comment' => Comment::find($this->mentionableId),
            'post' => Post::find($this->mentionableId),
            default => null,
        };
    }

    /**
     * Get the context description for the mention.
     */
    protected function getContext(Comment|Post|null $model): string
    {
        if ($model instanceof Comment) {
            return "a comment on \"{$model->post->title}\"";
        }

        if ($model instanceof Post) {
            return "the article \"{$model->title}\"";
        }

        return 'a post';
    }

    /**
     * Get the action URL for the mention.
     */
    protected function getActionUrl(Comment|Post|null $model): string
    {
        if ($model instanceof Comment) {
            return route('post.show', $model->post->slug).'#comment-'.$model->id;
        }

        if ($model instanceof Post) {
            return route('post.show', $model->slug);
        }

        return route('home');
    }
}

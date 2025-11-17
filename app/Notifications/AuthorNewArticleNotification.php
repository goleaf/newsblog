<?php

namespace App\Notifications;

use App\Models\Post;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AuthorNewArticleNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Post $post,
        public User $author
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
        if ($notifiable->wantsEmailNotification('author_new_article')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("{$this->author->name} published a new article")
            ->greeting("Hello {$notifiable->name}!")
            ->line("{$this->author->name} just published a new article: \"{$this->post->title}\".")
            ->line($this->post->excerpt)
            ->action('Read Article', route('post.show', $this->post->slug))
            ->line('Thank you for following '.$this->author->name.'!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'author_new_article',
            'post_id' => $this->post->id,
            'post_title' => $this->post->title,
            'post_slug' => $this->post->slug,
            'post_excerpt' => $this->post->excerpt,
            'post_featured_image' => $this->post->featured_image_url,
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'author_avatar' => $this->author->avatar_url,
            'reading_time' => $this->post->reading_time,
            'action_url' => route('post.show', $this->post->slug),
        ];
    }

    /**
     * Get the notification's database type.
     */
    public function databaseType(object $notifiable): string
    {
        return 'author_new_article';
    }
}

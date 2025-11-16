<?php

namespace Database\Factories;

use App\Models\Newsletter;
use App\Models\NewsletterSend;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NewsletterSend>
 */
class NewsletterSendFactory extends Factory
{
    protected $model = NewsletterSend::class;

    public function definition(): array
    {
        return [
            'subscriber_id' => Newsletter::factory(),
            'batch_id' => null,
            'subject' => fake()->sentence(),
            'content' => '<p>'.fake()->sentence(12).'</p>',
            'status' => 'sent',
            'sent_at' => now(),
            'provider_message_id' => (string) fake()->uuid(),
            'error' => null,
        ];
    }
}

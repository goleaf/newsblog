<?php

namespace App\Listeners;

use App\Jobs\SendWelcomeEmail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendWelcomeEmailListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct() {}

    /**
     * Handle the event.
     */
    public function handle(Registered $event): void
    {
        // Queue welcome email to the new user (Requirement 24.3)
        dispatch(new SendWelcomeEmail($event->user));
    }
}

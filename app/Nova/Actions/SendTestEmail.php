<?php

namespace App\Nova\Actions;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\Text;

class SendTestEmail extends Action
{
    use Queueable;

    public $name = 'Send Test Email';

    public function fields(): array
    {
        return [
            Text::make('Email', 'email')
                ->rules('required', 'email')
                ->help(__('settings.test_email_help')),
        ];
    }

    public function handle(ActionFields $fields, Collection $models)
    {
        try {
            Mail::raw(__('settings.test_email_body', ['app' => config('app.name')]), function ($message) use ($fields) {
                $message->to($fields->get('email'))
                    ->subject(__('settings.test_email_subject', ['app' => config('app.name')]));
            });

            return Action::message(__('settings.test_email_sent', ['email' => $fields->get('email')]));
        } catch (\Throwable $e) {
            return Action::danger(__('settings.test_email_failed', ['message' => $e->getMessage()]));
        }
    }
}

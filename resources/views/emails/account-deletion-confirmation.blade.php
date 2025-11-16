@php /** @var \App\Models\User $user */ @endphp
<div style="font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial;">
    <h1 style="font-size: 20px; font-weight: 600; margin: 0 0 16px;">
        {{ __('Account Deletion Confirmation') }}
    </h1>
    <p style="margin: 0 0 12px;">
        {{ __('Hello') }} {{ $user->name }},
    </p>
    <p style="margin: 0 0 12px;">
        {{ __('This is a confirmation that your account has been deleted and personal data anonymized, per your request.') }}
    </p>
    <p style="margin: 0 0 12px;">
        {{ __('If you did not request this, please contact us immediately at') }}
        <a href="mailto:{{ config('mail.from.address') }}">{{ config('mail.from.address') }}</a>.
    </p>
    <p style="margin: 24px 0 0; color: #6b7280; font-size: 12px;">
        {{ config('app.name') }}
    </p>
</div>



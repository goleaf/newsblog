@php /** @var \App\Models\ContactMessage $contact */ @endphp
<div>
    <h1 style="margin:0 0 16px 0;">{{ __('mail.contact.header') }}</h1>
    <p><strong>{{ __('mail.contact.name') }}:</strong> {{ $contact->name }}</p>
    <p><strong>{{ __('mail.contact.email') }}:</strong> {{ $contact->email }}</p>
    @if($contact->subject)
        <p><strong>{{ __('mail.contact.subject_label') }}:</strong> {{ $contact->subject }}</p>
    @endif
    <p style="white-space: pre-wrap;"><strong>{{ __('mail.contact.message') }}:</strong><br>{{ $contact->message }}</p>
    <p style="color:#666;margin-top:24px;">{{ __('mail.contact.footer') }}</p>
</div>




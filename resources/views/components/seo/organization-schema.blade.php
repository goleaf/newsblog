{{-- Organization Schema.org Structured Data (JSON-LD) --}}
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "Organization",
    "name": "{{ config('app.name', 'TechNewsHub') }}",
    "url": "{{ url('/') }}",
    "logo": {
        "@type": "ImageObject",
        "url": "{{ asset('images/logo.png') }}",
        "width": 600,
        "height": 60
    },
    "description": "{{ config('app.description', 'Your source for technology news and insights') }}",
    "sameAs": [
        "{{ config('services.social.twitter', 'https://twitter.com/technewshub') }}",
        "{{ config('services.social.facebook', 'https://facebook.com/technewshub') }}",
        "{{ config('services.social.linkedin', 'https://linkedin.com/company/technewshub') }}",
        "{{ config('services.social.github', 'https://github.com/technewshub') }}"
    ],
    "contactPoint": {
        "@type": "ContactPoint",
        "contactType": "Customer Service",
        "email": "{{ config('mail.from.address', 'contact@technewshub.com') }}"
    }
}
</script>

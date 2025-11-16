import Alpine from 'alpinejs';

function createSocialEmbedComponent({ provider = 'twitter', url = '' }) {
    return {
        provider,
        url,
        observed: false,
        init() {
            // Lazy observe to keep parity with requirement while honoring no-CDN policy
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting && !this.observed) {
                        this.observed = true;
                        // In a CDN-allowed setting, we would hydrate SDKs here.
                        // Policy requires placeholders; nothing to do.
                    }
                });
            }, { rootMargin: '200px' });
            observer.observe(this.$refs.placeholder);
        },
        label() {
            const map = {
                twitter: this.$t?.('social.twitter') || 'Twitter/X post',
                facebook: this.$t?.('social.facebook') || 'Facebook post',
                instagram: this.$t?.('social.instagram') || 'Instagram post',
            };
            return map[this.provider] || 'Social post';
        },
    };
}

Alpine.data('socialEmbedComponent', createSocialEmbedComponent);

export default createSocialEmbedComponent;




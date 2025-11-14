const csrfToken = () => {
    const token = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content');

    if (!token) {
        console.warn('CSRF token not found in meta tags.');
    }

    return token;
};

export default function postFeedback({
    postId,
    counts = {},
    reactionUrl,
    messages,
} = {}) {
    return {
        counts: {
            like: counts.like ?? 0,
            love: counts.love ?? 0,
        },
        loading: false,
        postId,
        reactionUrl,
        messages,

        async react(type) {
            if (this.loading) {
                return;
            }

            this.loading = true;

            try {
                const response = await fetch(this.reactionEndpoint(), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                        Accept: 'application/json',
                    },
                    body: JSON.stringify({ type }),
                });

                if (!response.ok) {
                    throw new Error(`Reaction request failed with status ${response.status}`);
                }

                const data = await response.json();

                if (typeof data.count === 'number') {
                    this.counts[type] = data.count;
                }
            } catch (error) {
                console.error('Failed to submit reaction:', error);
                alert(this.messages.error);
            } finally {
                this.loading = false;
            }
        },

        reactionEndpoint() {
            if (this.reactionUrl) {
                return this.reactionUrl.replace(':postId', this.postId);
            }

            return `/api/v1/posts/${this.postId}/reactions`;
        },
    };
}


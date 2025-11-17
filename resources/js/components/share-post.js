const openPopup = (url) =>
    window.open(url, '_blank', 'width=600,height=400,noopener,noreferrer');

const copyUsingTextarea = async (text) => {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px';
    textArea.style.top = '-999999px';
    document.body.appendChild(textArea);

    textArea.focus();
    textArea.select();

    try {
        const successful = document.execCommand('copy');

        if (!successful) {
            throw new Error('Copy command unsuccessful');
        }
    } finally {
        document.body.removeChild(textArea);
    }
};

export default function sharePost({
    url,
    title,
    text,
    copyErrorMessage,
    copySuccessDuration = 3000,
    postId = null,
    trackUrl = null,
    shareCount = 0,
} = {}) {
    return {
        copied: false,
        canShare: false,
        url,
        title,
        text,
        copyErrorMessage,
        postId,
        trackUrl,
        shareCount,

        init() {
            this.canShare =
                typeof navigator !== 'undefined' &&
                typeof navigator.share === 'function';
        },

        async trackShare(platform) {
            if (!this.trackUrl || !this.postId) {
                return;
            }

            try {
                const response = await fetch(this.trackUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({ platform }),
                });

                if (response.ok) {
                    const data = await response.json();
                    if (data.share_count !== undefined) {
                        this.shareCount = data.share_count;
                    }
                }
            } catch (error) {
                console.error('Failed to track share:', error);
            }
        },

        shareOnFacebook() {
            const shareUrl = encodeURIComponent(this.url);
            openPopup(
                `https://www.facebook.com/sharer/sharer.php?u=${shareUrl}`,
            );
            this.trackShare('facebook');
        },

        shareOnTwitter() {
            const shareUrl = encodeURIComponent(this.url);
            const shareText = encodeURIComponent(this.title);
            openPopup(
                `https://twitter.com/intent/tweet?url=${shareUrl}&text=${shareText}`,
            );
            this.trackShare('twitter');
        },

        shareOnLinkedIn() {
            const shareUrl = encodeURIComponent(this.url);
            openPopup(
                `https://www.linkedin.com/sharing/share-offsite/?url=${shareUrl}`,
            );
            this.trackShare('linkedin');
        },

        shareOnReddit() {
            const shareUrl = encodeURIComponent(this.url);
            const shareTitle = encodeURIComponent(this.title);
            openPopup(
                `https://www.reddit.com/submit?url=${shareUrl}&title=${shareTitle}`,
            );
            this.trackShare('reddit');
        },

        shareOnHackerNews() {
            const shareUrl = encodeURIComponent(this.url);
            const shareTitle = encodeURIComponent(this.title);
            openPopup(
                `https://news.ycombinator.com/submitlink?u=${shareUrl}&t=${shareTitle}`,
            );
            this.trackShare('hackernews');
        },

        async copyLink() {
            try {
                if (
                    navigator.clipboard &&
                    typeof navigator.clipboard.writeText === 'function'
                ) {
                    await navigator.clipboard.writeText(this.url);
                } else {
                    await copyUsingTextarea(this.url);
                }

                this.copied = true;

                setTimeout(() => {
                    this.copied = false;
                }, copySuccessDuration);
            } catch (error) {
                console.error('Failed to copy link:', error);
                alert(`${this.copyErrorMessage} ${this.url}`);
            }
        },

        async nativeShare() {
            if (!this.canShare) {
                return;
            }

            try {
                await navigator.share({
                    title: this.title,
                    text: this.text,
                    url: this.url,
                });
            } catch (error) {
                if (error?.name !== 'AbortError') {
                    console.error('Share failed:', error);
                }
            }
        },
    };
}


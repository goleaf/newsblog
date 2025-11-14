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
} = {}) {
    return {
        copied: false,
        canShare: false,
        url,
        title,
        text,
        copyErrorMessage,

        init() {
            this.canShare =
                typeof navigator !== 'undefined' &&
                typeof navigator.share === 'function';
        },

        shareOnFacebook() {
            const shareUrl = encodeURIComponent(this.url);
            openPopup(
                `https://www.facebook.com/sharer/sharer.php?u=${shareUrl}`,
            );
        },

        shareOnTwitter() {
            const shareUrl = encodeURIComponent(this.url);
            const shareText = encodeURIComponent(this.title);
            openPopup(
                `https://twitter.com/intent/tweet?url=${shareUrl}&text=${shareText}`,
            );
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


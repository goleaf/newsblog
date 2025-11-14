const csrfToken = () => {
    const token = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content');

    if (!token) {
        console.warn('CSRF token not found in meta tags.');
    }

    return token;
};

const createToast = (message, type = 'success') => {
    const toast = document.createElement('div');
    toast.className = `fixed bottom-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg text-white transition-opacity duration-300 ${
        type === 'error' ? 'bg-red-500' : 'bg-green-500'
    }`;
    toast.textContent = message;

    document.body.appendChild(toast);

    requestAnimationFrame(() => {
        toast.style.opacity = '1';
    });

    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
};

export default function bookmarkButton({
    toggleUrl,
    initialBookmarked = false,
    messages,
    sizeClass = '',
} = {}) {
    return {
        bookmarked: initialBookmarked,
        loading: false,
        toggleUrl,
        messages,
        sizeClass,

        get iconClasses() {
            return this.bookmarked
                ? 'fill-current text-indigo-600 dark:text-indigo-400'
                : 'stroke-current text-gray-600 dark:text-gray-400';
        },

        get iconClassList() {
            return [this.sizeClass, 'transition-colors', this.iconClasses]
                .filter(Boolean)
                .join(' ');
        },

        get iconFill() {
            return this.bookmarked ? 'currentColor' : 'none';
        },

        get tooltip() {
            return this.bookmarked
                ? this.messages.removeFromReadingList
                : this.messages.addToReadingList;
        },

        async toggle() {
            if (this.loading) {
                return;
            }

            this.loading = true;

            try {
                const response = await fetch(this.toggleUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                        Accept: 'application/json',
                    },
                    body: JSON.stringify({}),
                });

                if (!response.ok) {
                    throw new Error(`Request failed with status ${response.status}`);
                }

                const data = await response.json();

                this.bookmarked = Boolean(data.bookmarked);

                if (data?.message) {
                    createToast(data.message);
                }
            } catch (error) {
                console.error('Bookmark toggle failed:', error);
                createToast(this.messages.error, 'error');
            } finally {
                this.loading = false;
            }
        },
    };
}


export default function infiniteScroll() {
    return {
        loading: false,
        finished: false,
        error: null,
        currentPage: 1,
        lastPage: 1,
        observer: null,

        init() {
            const container = this.$root;
            this.currentPage = parseInt(container.getAttribute('data-current-page') || '1', 10);
            this.lastPage = parseInt(container.getAttribute('data-last-page') || '1', 10);
            this.finished = this.currentPage >= this.lastPage;

            this.$nextTick(() => {
                const sentinel = this.$refs.sentinel;
                if (!sentinel) {
                    return;
                }
                this.observer = new IntersectionObserver(
                    (entries) => {
                        entries.forEach((entry) => {
                            if (entry.isIntersecting && !this.loading && !this.finished) {
                                this.loadMore();
                            }
                        });
                    },
                    {
                        root: null,
                        rootMargin: '200px', // 200px from bottom
                        threshold: 0,
                    }
                );
                this.observer.observe(sentinel);
            });
        },

        buildNextPageUrl() {
            const url = new URL(window.location.href);
            const nextPage = this.currentPage + 1;
            url.searchParams.set('page', String(nextPage));
            return url.toString();
        },

        async loadMore() {
            if (this.loading || this.finished) {
                return;
            }
            this.loading = true;
            this.error = null;
            try {
                const nextUrl = this.buildNextPageUrl();
                const response = await fetch(nextUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                });
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                const data = await response.json();

                if (typeof data.html === 'string' && data.html.length > 0) {
                    // Insert HTML and apply fade-in to new items
                    const container = this.$refs.postsContainer;
                    const temp = document.createElement('div');
                    temp.innerHTML = data.html;
                    const newItems = Array.from(temp.children);
                    newItems.forEach((el) => {
                        // add fade-in classes before insertion
                        el.classList.add('opacity-0', 'transition-opacity', 'duration-500');
                        container.appendChild(el);
                        requestAnimationFrame(() => {
                            el.classList.remove('opacity-0');
                            el.classList.add('opacity-100');
                        });
                    });
                }

                this.currentPage = data.currentPage ?? this.currentPage + 1;
                this.lastPage = data.lastPage ?? this.lastPage;
                this.finished = data.hasMorePages === false || this.currentPage >= this.lastPage;

                // Update URL to reflect current page
                const url = new URL(window.location.href);
                url.searchParams.set('page', String(this.currentPage));
                window.history.pushState({}, '', url.toString());
            } catch (e) {
                console.error(e);
                this.error = 'infinite_scroll.error_generic';
                this.finished = true;
            } finally {
                this.loading = false;
            }
        },
    };
}



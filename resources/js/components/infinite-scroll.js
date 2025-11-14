export default function infiniteScroll(initialUrl, initialPage = 1) {
    return {
        posts: [],
        loading: false,
        hasMore: true,
        currentPage: initialPage,
        nextPageUrl: initialUrl,
        error: null,

        init() {
            // Set up intersection observer for infinite scroll
            this.$nextTick(() => {
                const sentinel = this.$refs.sentinel;
                if (sentinel) {
                    const observer = new IntersectionObserver(
                        (entries) => {
                            entries.forEach((entry) => {
                                if (entry.isIntersecting && !this.loading && this.hasMore) {
                                    this.loadMore();
                                }
                            });
                        },
                        {
                            rootMargin: '100px', // Start loading 100px before reaching the sentinel
                        }
                    );
                    observer.observe(sentinel);
                }
            });
        },

        async loadMore() {
            if (this.loading || !this.hasMore || !this.nextPageUrl) {
                return;
            }

            this.loading = true;
            this.error = null;

            try {
                const response = await fetch(this.nextPageUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();

                // Append new posts
                if (data.data && Array.isArray(data.data)) {
                    this.posts = [...this.posts, ...data.data];
                }

                // Update pagination info
                if (data.pagination) {
                    this.currentPage = data.pagination.current_page;
                    this.hasMore = data.pagination.has_more || false;
                    this.nextPageUrl = data.pagination.next_page_url || null;
                } else if (data.next_page_url) {
                    // Fallback for Laravel pagination format
                    this.nextPageUrl = data.next_page_url;
                    this.hasMore = !!data.next_page_url;
                    this.currentPage = data.current_page || this.currentPage + 1;
                } else {
                    this.hasMore = false;
                    this.nextPageUrl = null;
                }
            } catch (error) {
                console.error('Error loading more posts:', error);
                this.error = 'Failed to load more posts. Please try again.';
                this.hasMore = false;
            } finally {
                this.loading = false;
            }
        },

        retry() {
            this.error = null;
            this.hasMore = true;
            this.loadMore();
        },
    };
}

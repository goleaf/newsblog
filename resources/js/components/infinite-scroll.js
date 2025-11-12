/**
 * Infinite Scroll Component
 * 
 * Implements infinite scrolling for post listing pages using Intersection Observer API.
 * Requirements: 27.1, 27.2, 27.3, 27.4, 27.5
 */

export default function infiniteScroll() {
    return {
        loading: false,
        finished: false,
        currentPage: 1,
        lastPage: 1,
        observer: null,
        
        init() {
            // Get initial pagination data from the page
            const paginationData = this.$el.dataset;
            this.currentPage = parseInt(paginationData.currentPage) || 1;
            this.lastPage = parseInt(paginationData.lastPage) || 1;
            
            // Check if we're already on the last page
            if (this.currentPage >= this.lastPage) {
                this.finished = true;
                return;
            }
            
            // Set up Intersection Observer
            this.setupObserver();
        },
        
        setupObserver() {
            const options = {
                root: null,
                rootMargin: '200px', // Trigger 200px before reaching the bottom
                threshold: 0
            };
            
            this.observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting && !this.loading && !this.finished) {
                        this.loadMore();
                    }
                });
            }, options);
            
            // Observe the sentinel element
            const sentinel = this.$refs.sentinel;
            if (sentinel) {
                this.observer.observe(sentinel);
            }
        },
        
        async loadMore() {
            if (this.loading || this.finished) {
                return;
            }
            
            this.loading = true;
            const nextPage = this.currentPage + 1;
            
            try {
                // Build URL with current query parameters
                const url = new URL(window.location.href);
                url.searchParams.set('page', nextPage);
                
                const response = await fetch(url.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });
                
                if (!response.ok) {
                    throw new Error('Failed to load posts');
                }
                
                const data = await response.json();
                
                if (data.html && data.html.trim() !== '') {
                    // Append new posts to the grid
                    this.appendPosts(data.html);
                    
                    // Update current page
                    this.currentPage = nextPage;
                    
                    // Update URL without page reload (Requirement 27.4)
                    this.updateUrl(nextPage);
                    
                    // Check if we've reached the last page
                    if (nextPage >= data.lastPage) {
                        this.finished = true;
                        this.cleanup();
                    }
                } else {
                    // No more content
                    this.finished = true;
                    this.cleanup();
                }
            } catch (error) {
                console.error('Error loading more posts:', error);
                // On error, mark as finished to prevent infinite retry
                this.finished = true;
            } finally {
                this.loading = false;
            }
        },
        
        appendPosts(html) {
            const container = this.$refs.postsContainer;
            if (!container) return;
            
            // Create a temporary container to parse the HTML
            const temp = document.createElement('div');
            temp.innerHTML = html;
            
            // Get all post elements
            const posts = temp.querySelectorAll('[data-post-item]');
            
            // Append each post with fade-in animation (Requirement 27.2)
            posts.forEach((post, index) => {
                post.style.opacity = '0';
                post.style.transform = 'translateY(20px)';
                post.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                
                container.appendChild(post);
                
                // Trigger animation after a small delay
                setTimeout(() => {
                    post.style.opacity = '1';
                    post.style.transform = 'translateY(0)';
                }, index * 50);
            });
        },
        
        updateUrl(page) {
            // Update browser URL using pushState (Requirement 27.4)
            const url = new URL(window.location.href);
            url.searchParams.set('page', page);
            window.history.pushState({ page }, '', url.toString());
        },
        
        cleanup() {
            // Disconnect observer when finished
            if (this.observer) {
                this.observer.disconnect();
                this.observer = null;
            }
        },
        
        destroy() {
            this.cleanup();
        }
    };
}

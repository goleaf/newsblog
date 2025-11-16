import Alpine from 'alpinejs';

const clamp = (num, min, max) => Math.max(min, Math.min(num, max));

function createGalleryComponent({ images = [], autoplay = true, intervalMs = 3000 }) {
    return {
        images,
        currentIndex: 0,
        autoplaying: Boolean(autoplay),
        intervalMs: Number(intervalMs) || 3000,
        timerId: null,
        startX: 0,
        deltaX: 0,
        loaded: false,
        get currentImage() {
            return this.images[this.currentIndex] || null;
        },
        init() {
            if (this.autoplaying) {
                this.startAutoplay();
            }
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    this.stopAutoplay();
                } else if (this.autoplaying) {
                    this.startAutoplay();
                }
            });
        },
        onMainLoaded() {
            this.loaded = true;
        },
        counterText() {
            return `${this.currentIndex + 1}/${this.images.length}`;
        },
        go(index) {
            const next = clamp(index, 0, this.images.length - 1);
            this.loaded = false;
            this.currentIndex = next;
        },
        next() {
            const next = (this.currentIndex + 1) % this.images.length;
            this.go(next);
        },
        prev() {
            const prev = (this.currentIndex - 1 + this.images.length) % this.images.length;
            this.go(prev);
        },
        toggleAutoplay() {
            if (this.autoplaying) {
                this.autoplaying = false;
                this.stopAutoplay();
            } else {
                this.autoplaying = true;
                this.startAutoplay();
            }
        },
        startAutoplay() {
            this.stopAutoplay();
            this.timerId = setInterval(() => this.next(), this.intervalMs);
        },
        stopAutoplay() {
            if (this.timerId) {
                clearInterval(this.timerId);
                this.timerId = null;
            }
        },
        toggleFullscreen() {
            const el = this.$root.querySelector('img');
            if (!el) {
                return;
            }
            if (!document.fullscreenElement) {
                el.requestFullscreen?.();
            } else {
                document.exitFullscreen?.();
            }
        },
        onTouchStart(e) {
            this.startX = e.changedTouches[0].clientX;
            this.deltaX = 0;
        },
        onTouchMove(e) {
            this.deltaX = e.changedTouches[0].clientX - this.startX;
        },
        onTouchEnd() {
            if (Math.abs(this.deltaX) > 40) {
                if (this.deltaX < 0) {
                    this.next();
                } else {
                    this.prev();
                }
            }
            this.startX = 0;
            this.deltaX = 0;
        },
    };
}

Alpine.data('galleryComponent', createGalleryComponent);

export default createGalleryComponent;



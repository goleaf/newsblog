@props(['post'])

<div 
    x-data="floatingActions({ 
        postId: {{ $post->id }},
        isBookmarked: {{ $post->isBookmarkedBy(auth()->id()) ? 'true' : 'false' }},
        bookmarksCount: {{ $post->bookmarks_count }},
        reactions: @js([
            'like' => $post->reactions()->where('type', 'like')->count(),
            'love' => $post->reactions()->where('type', 'love')->count(),
            'laugh' => $post->reactions()->where('type', 'laugh')->count(),
            'wow' => $post->reactions()->where('type', 'wow')->count(),
            'sad' => $post->reactions()->where('type', 'sad')->count(),
            'angry' => $post->reactions()->where('type', 'angry')->count(),
        ])
    })"
    x-init="init()"
    x-show="isVisible"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 transform translate-y-4"
    x-transition:enter-end="opacity-100 transform translate-y-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 transform translate-y-0"
    x-transition:leave-end="opacity-0 transform translate-y-4"
    class="fixed bottom-8 right-8 z-40 print:hidden"
    style="display: none;"
>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-2xl border border-gray-200 dark:border-gray-700 p-3 space-y-2">
        {{-- Bookmark Button --}}
        @auth
            <button
                @click="toggleBookmark"
                :disabled="bookmarkLoading"
                class="flex items-center justify-center w-12 h-12 rounded-lg transition-colors"
                :class="isBookmarked ? 'bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-400' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600'"
                :aria-label="isBookmarked ? 'Remove bookmark' : 'Bookmark this article'"
                title="Bookmark"
            >
                <svg 
                    class="w-6 h-6 transition-transform" 
                    :class="{ 'scale-110': isBookmarked }"
                    :fill="isBookmarked ? 'currentColor' : 'none'" 
                    stroke="currentColor" 
                    viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                </svg>
            </button>
        @else
            <a
                href="{{ route('login') }}"
                class="flex items-center justify-center w-12 h-12 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                aria-label="Login to bookmark"
                title="Bookmark (Login required)"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                </svg>
            </a>
        @endauth

        {{-- Share Button --}}
        <button
            @click="showShareModal = true"
            class="flex items-center justify-center w-12 h-12 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
            aria-label="Share this article"
            title="Share"
        >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
            </svg>
        </button>

        {{-- QR Code Button --}}
        <button
            @click="showQrModal = true"
            class="flex items-center justify-center w-12 h-12 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
            aria-label="{{ __('qr.open') }}"
            title="{{ __('qr.title') }}"
        >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h6v6H3V3zm12 0h6v6h-6V3zM3 15h6v6H3v-6zm12 4h2v2h-2v-2zm4-8h2v6h-6v-2h4v-4zM11 11h2v2h-2v-2z" />
            </svg>
        </button>

        {{-- Reaction Button --}}
        @auth
            <div class="relative">
                <button
                    @click="showReactionPicker = !showReactionPicker"
                    class="flex items-center justify-center w-12 h-12 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                    aria-label="React to this article"
                    title="React"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </button>

                {{-- Reaction Picker --}}
                <div
                    x-show="showReactionPicker"
                    @click.away="showReactionPicker = false"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 transform scale-95"
                    x-transition:enter-end="opacity-100 transform scale-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 transform scale-100"
                    x-transition:leave-end="opacity-0 transform scale-95"
                    class="absolute bottom-full right-0 mb-2 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 p-2 flex gap-1"
                    style="display: none;"
                >
                    <button @click="react('like')" class="reaction-btn" title="Like" aria-label="Like">
                        <span class="text-2xl">👍</span>
                        <span class="text-xs" x-text="reactions.like"></span>
                    </button>
                    <button @click="react('love')" class="reaction-btn" title="Love" aria-label="Love">
                        <span class="text-2xl">❤️</span>
                        <span class="text-xs" x-text="reactions.love"></span>
                    </button>
                    <button @click="react('laugh')" class="reaction-btn" title="Laugh" aria-label="Laugh">
                        <span class="text-2xl">😂</span>
                        <span class="text-xs" x-text="reactions.laugh"></span>
                    </button>
                    <button @click="react('wow')" class="reaction-btn" title="Wow" aria-label="Wow">
                        <span class="text-2xl">😮</span>
                        <span class="text-xs" x-text="reactions.wow"></span>
                    </button>
                    <button @click="react('sad')" class="reaction-btn" title="Sad" aria-label="Sad">
                        <span class="text-2xl">😢</span>
                        <span class="text-xs" x-text="reactions.sad"></span>
                    </button>
                    <button @click="react('angry')" class="reaction-btn" title="Angry" aria-label="Angry">
                        <span class="text-2xl">😠</span>
                        <span class="text-xs" x-text="reactions.angry"></span>
                    </button>
                </div>
            </div>
        @else
            <a
                href="{{ route('login') }}"
                class="flex items-center justify-center w-12 h-12 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                aria-label="Login to react"
                title="React (Login required)"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </a>
        @endauth

        {{-- Scroll to Top Button --}}
        <button
            @click="scrollToTop"
            class="flex items-center justify-center w-12 h-12 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
            aria-label="Scroll to top"
            title="Scroll to top"
        >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
            </svg>
        </button>
    </div>

    {{-- Share Modal --}}
    <div
        x-show="showShareModal"
        @click.self="showShareModal = false"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
        style="display: none;"
    >
        <div
            @click.stop
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform scale-95"
            x-transition:enter-end="opacity-100 transform scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 transform scale-100"
            x-transition:leave-end="opacity-0 transform scale-95"
            class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full mx-4 p-6"
        >
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Share this article</h3>
                <button
                    @click="showShareModal = false"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                    aria-label="Close"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <button @click="shareOn('twitter')" class="share-btn">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                    </svg>
                    <span>Twitter</span>
                </button>

                <button @click="shareOn('facebook')" class="share-btn">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                    <span>Facebook</span>
                </button>

                <button @click="shareOn('linkedin')" class="share-btn">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                    </svg>
                    <span>LinkedIn</span>
                </button>

                <button @click="shareOn('reddit')" class="share-btn">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0zm5.01 4.744c.688 0 1.25.561 1.25 1.249a1.25 1.25 0 0 1-2.498.056l-2.597-.547-.8 3.747c1.824.07 3.48.632 4.674 1.488.308-.309.73-.491 1.207-.491.968 0 1.754.786 1.754 1.754 0 .716-.435 1.333-1.01 1.614a3.111 3.111 0 0 1 .042.52c0 2.694-3.13 4.87-7.004 4.87-3.874 0-7.004-2.176-7.004-4.87 0-.183.015-.366.043-.534A1.748 1.748 0 0 1 4.028 12c0-.968.786-1.754 1.754-1.754.463 0 .898.196 1.207.49 1.207-.883 2.878-1.43 4.744-1.487l.885-4.182a.342.342 0 0 1 .14-.197.35.35 0 0 1 .238-.042l2.906.617a1.214 1.214 0 0 1 1.108-.701zM9.25 12C8.561 12 8 12.562 8 13.25c0 .687.561 1.248 1.25 1.248.687 0 1.248-.561 1.248-1.249 0-.688-.561-1.249-1.249-1.249zm5.5 0c-.687 0-1.248.561-1.248 1.25 0 .687.561 1.248 1.249 1.248.688 0 1.249-.561 1.249-1.249 0-.687-.562-1.249-1.25-1.249zm-5.466 3.99a.327.327 0 0 0-.231.094.33.33 0 0 0 0 .463c.842.842 2.484.913 2.961.913.477 0 2.105-.056 2.961-.913a.361.361 0 0 0 .029-.463.33.33 0 0 0-.464 0c-.547.533-1.684.73-2.512.73-.828 0-1.979-.196-2.512-.73a.326.326 0 0 0-.232-.095z"/>
                    </svg>
                    <span>Reddit</span>
                </button>
            </div>

            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Copy link</label>
                <div class="flex gap-2">
                    <input
                        type="text"
                        :value="shareUrl"
                        readonly
                        class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white text-sm"
                    >
                    <button
                        @click="copyLink"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition-colors"
                    >
                        <span x-text="linkCopied ? 'Copied!' : 'Copy'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- QR Code Modal (scaffold) --}}
<div 
    x-show="showQrModal"
    x-transition
    @keydown.escape.window="showQrModal = false"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 print:hidden"
    style="display:none"
    role="dialog"
    aria-modal="true"
    aria-label="QR code"
>
    <div @click.stop class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('qr.title') }}</h3>
            <button @click="showQrModal = false" aria-label="Close" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div class="flex flex-col items-center gap-4">
            <div id="qr-container" class="w-48 h-48 bg-white"></div>
            <div class="flex items-center gap-2">
                <button
                    @click="
                        if (window.generateQRCode) {
                            window.generateQRCode('#qr-container', window.location.href);
                        } else {
                            document.querySelector('#qr-container').innerHTML = '<div class=\'text-sm text-gray-600\'>QR library not installed</div>';
                        }
                    "
                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition-colors"
                >
                    {{ __('qr.generate') }}
                </button>
                <button
                    @click="
                        const canvas = document.querySelector('#qr-container canvas');
                        if (!canvas) return;
                        const link = document.createElement('a');
                        link.href = canvas.toDataURL('image/png');
                        link.download = 'qr.png';
                        link.click();
                    "
                    class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-lg text-sm font-medium transition-colors"
                >
                    {{ __('qr.download') }}
                </button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.reaction-btn {
    @apply flex flex-col items-center justify-center w-12 h-12 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors;
}

.share-btn {
    @apply flex items-center justify-center gap-2 px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 transition-colors;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('floatingActions', (config) => ({
        isVisible: false,
        isBookmarked: config.isBookmarked,
        bookmarksCount: config.bookmarksCount,
        bookmarkLoading: false,
        reactions: config.reactions,
        showReactionPicker: false,
        showShareModal: false,
        showQrModal: false,
        shareUrl: window.location.href,
        linkCopied: false,
        lastScrollY: 0,
        
        init() {
            // Show/hide on scroll
            let ticking = false;
            window.addEventListener('scroll', () => {
                if (!ticking) {
                    window.requestAnimationFrame(() => {
                        this.handleScroll();
                        ticking = false;
                    });
                    ticking = true;
                }
            });
        },
        
        handleScroll() {
            const currentScrollY = window.pageYOffset;
            
            // Show after scrolling down 300px
            if (currentScrollY > 300) {
                this.isVisible = true;
            } else {
                this.isVisible = false;
            }
            
            this.lastScrollY = currentScrollY;
        },
        
        async toggleBookmark() {
            if (this.bookmarkLoading) return;
            
            this.bookmarkLoading = true;
            
            try {
                const response = await fetch(`/api/v1/posts/${config.postId}/bookmark`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.isBookmarked = data.data.bookmarked;
                    this.bookmarksCount = data.data.bookmarks_count;
                } else {
                    console.error('Bookmark failed:', data.message);
                }
            } catch (error) {
                console.error('Bookmark error:', error);
            } finally {
                this.bookmarkLoading = false;
            }
        },
        
        async react(type) {
            try {
                const response = await fetch(`/api/v1/posts/${config.postId}/reactions`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ type })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.reactions = data.data.reactions;
                    this.showReactionPicker = false;
                }
            } catch (error) {
                console.error('Reaction error:', error);
            }
        },
        
        shareOn(platform) {
            const url = encodeURIComponent(this.shareUrl);
            const title = encodeURIComponent(document.title);
            
            let shareUrl = '';
            
            switch(platform) {
                case 'twitter':
                    shareUrl = `https://twitter.com/intent/tweet?url=${url}&text=${title}`;
                    break;
                case 'facebook':
                    shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${url}`;
                    break;
                case 'linkedin':
                    shareUrl = `https://www.linkedin.com/sharing/share-offsite/?url=${url}`;
                    break;
                case 'reddit':
                    shareUrl = `https://reddit.com/submit?url=${url}&title=${title}`;
                    break;
            }
            
            if (shareUrl) {
                window.open(shareUrl, '_blank', 'width=600,height=400');
            }
        },
        
        async copyLink() {
            try {
                await navigator.clipboard.writeText(this.shareUrl);
                this.linkCopied = true;
                
                setTimeout(() => {
                    this.linkCopied = false;
                }, 2000);
            } catch (error) {
                console.error('Copy failed:', error);
            }
        },
        
        scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }
    }));
});
</script>
@endpush

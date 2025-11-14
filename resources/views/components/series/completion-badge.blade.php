@props(['seriesId', 'seriesName', 'relatedSeries' => []])

<div 
    x-data="completionBadge({{ $seriesId }}, {{ json_encode($relatedSeries) }})"
    x-show="isCompleted"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 transform scale-95"
    x-transition:enter-end="opacity-100 transform scale-100"
    class="overflow-hidden rounded-lg bg-gradient-to-r from-green-50 to-emerald-50 p-6 shadow-lg dark:from-green-900/20 dark:to-emerald-900/20"
>
    <!-- Celebration Animation -->
    <div class="mb-4 flex items-center justify-center">
        <div class="relative">
            <!-- Trophy Icon -->
            <div class="flex h-20 w-20 items-center justify-center rounded-full bg-gradient-to-br from-yellow-400 to-yellow-600 shadow-lg">
                <svg class="h-10 w-10 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>
            </div>
            
            <!-- Sparkles -->
            <div class="absolute -right-2 -top-2 animate-ping">
                <svg class="h-6 w-6 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>
            </div>
        </div>
    </div>

    <!-- Completion Message -->
    <div class="text-center">
        <h3 class="mb-2 text-2xl font-bold text-gray-900 dark:text-gray-100">
            🎉 Series Completed!
        </h3>
        <p class="mb-4 text-gray-700 dark:text-gray-300">
            Congratulations! You've finished reading all articles in <strong>{{ $seriesName }}</strong>.
        </p>
    </div>

    <!-- Related Series Suggestions -->
    @if(count($relatedSeries) > 0)
        <div class="mt-6 border-t border-green-200 pt-6 dark:border-green-800">
            <h4 class="mb-3 text-lg font-semibold text-gray-900 dark:text-gray-100">
                Continue Learning
            </h4>
            <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                Check out these related series:
            </p>
            
            <div class="space-y-3">
                @foreach($relatedSeries as $related)
                    <a 
                        href="{{ route('series.show', $related['slug']) }}"
                        class="block rounded-lg bg-white p-4 shadow-sm transition-shadow hover:shadow-md dark:bg-gray-800"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <h5 class="font-semibold text-gray-900 dark:text-gray-100">
                                    {{ $related['name'] }}
                                </h5>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $related['posts_count'] }} articles
                                </p>
                            </div>
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Share Achievement -->
    <div class="mt-6 flex justify-center gap-3">
        <button 
            @click="shareCompletion"
            class="inline-flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-green-700 dark:bg-green-700 dark:hover:bg-green-600"
        >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
            </svg>
            Share Achievement
        </button>
        
        <button 
            @click="resetProgress"
            class="inline-flex items-center gap-2 rounded-lg bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
        >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            Reset Progress
        </button>
    </div>
</div>

@push('scripts')
<script>
    function completionBadge(seriesId, relatedSeries) {
        return {
            seriesId: seriesId,
            relatedSeries: relatedSeries,
            isCompleted: false,
            
            init() {
                this.checkCompletion();
                
                // Listen for progress updates
                window.addEventListener('series-progress-updated', (event) => {
                    if (event.detail.seriesId === this.seriesId) {
                        this.checkCompletion();
                    }
                });
            },
            
            checkCompletion() {
                const key = `series_progress_${this.seriesId}`;
                const stored = localStorage.getItem(key);
                const readPosts = stored ? JSON.parse(stored) : [];
                
                // Get total posts from the page
                const totalPosts = document.querySelectorAll('[data-post-id]').length;
                
                this.isCompleted = totalPosts > 0 && readPosts.length === totalPosts;
                
                // Show celebration animation on first completion
                if (this.isCompleted && !this.hasSeenCelebration()) {
                    this.showCelebration();
                    this.markCelebrationSeen();
                }
            },
            
            hasSeenCelebration() {
                const key = `series_celebration_${this.seriesId}`;
                return localStorage.getItem(key) === 'true';
            },
            
            markCelebrationSeen() {
                const key = `series_celebration_${this.seriesId}`;
                localStorage.setItem(key, 'true');
            },
            
            showCelebration() {
                // Trigger confetti or celebration animation
                console.log('🎉 Series completed!');
            },
            
            shareCompletion() {
                const text = `I just completed the "${document.title}" series! 🎉`;
                const url = window.location.href;
                
                if (navigator.share) {
                    navigator.share({
                        title: document.title,
                        text: text,
                        url: url
                    }).catch(() => {
                        this.copyToClipboard(url);
                    });
                } else {
                    this.copyToClipboard(url);
                }
            },
            
            copyToClipboard(text) {
                navigator.clipboard.writeText(text).then(() => {
                    alert('Link copied to clipboard!');
                });
            },
            
            resetProgress() {
                if (confirm('Are you sure you want to reset your progress? This will mark all articles as unread.')) {
                    const key = `series_progress_${this.seriesId}`;
                    localStorage.removeItem(key);
                    
                    const celebrationKey = `series_celebration_${this.seriesId}`;
                    localStorage.removeItem(celebrationKey);
                    
                    window.location.reload();
                }
            }
        };
    }
</script>
@endpush

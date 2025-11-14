@props(['post', 'show' => false])

@php
    $url = route('post.show', $post->slug);
    $title = $post->title;
    $excerpt = $post->excerpt ?? Str::limit(strip_tags($post->content), 150);
    
    $shareLinks = [
        'twitter' => [
            'url' => 'https://twitter.com/intent/tweet?text=' . urlencode($title) . '&url=' . urlencode($url),
            'label' => 'Twitter',
            'icon' => 'M23 3a10.9 10.9 0 01-3.14 1.53 4.48 4.48 0 00-7.86 3v1A10.66 10.66 0 013 4s-4 9 5 13a11.64 11.64 0 01-7 2c9 5 20 0 20-11.5a4.5 4.5 0 00-.08-.83A7.72 7.72 0 0023 3z',
            'color' => 'bg-blue-400 hover:bg-blue-500',
        ],
        'facebook' => [
            'url' => 'https://www.facebook.com/sharer/sharer.php?u=' . urlencode($url),
            'label' => 'Facebook',
            'icon' => 'M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z',
            'color' => 'bg-blue-600 hover:bg-blue-700',
        ],
        'linkedin' => [
            'url' => 'https://www.linkedin.com/sharing/share-offsite/?url=' . urlencode($url),
            'label' => 'LinkedIn',
            'icon' => 'M16 8a6 6 0 016 6v7h-4v-7a2 2 0 00-2-2 2 2 0 00-2 2v7h-4v-7a6 6 0 016-6zM2 9h4v12H2z M4 6a2 2 0 100-4 2 2 0 000 4z',
            'color' => 'bg-blue-700 hover:bg-blue-800',
        ],
        'reddit' => [
            'url' => 'https://reddit.com/submit?url=' . urlencode($url) . '&title=' . urlencode($title),
            'label' => 'Reddit',
            'icon' => 'M12 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0zm5.01 4.744c.688 0 1.25.561 1.25 1.249a1.25 1.25 0 0 1-2.498.056l-2.597-.547-.8 3.747c1.824.07 3.48.632 4.674 1.488.308-.309.73-.491 1.207-.491.968 0 1.754.786 1.754 1.754 0 .716-.435 1.333-1.01 1.614a3.111 3.111 0 0 1 .042.52c0 2.694-3.13 4.87-7.004 4.87-3.874 0-7.004-2.176-7.004-4.87 0-.183.015-.366.043-.534A1.748 1.748 0 0 1 4.028 12c0-.968.786-1.754 1.754-1.754.463 0 .898.196 1.207.49 1.207-.883 2.878-1.43 4.744-1.487l.885-4.182a.342.342 0 0 1 .14-.197.35.35 0 0 1 .238-.042l2.906.617a1.214 1.214 0 0 1 1.108-.701zM9.25 12C8.561 12 8 12.562 8 13.25c0 .687.561 1.248 1.25 1.248.687 0 1.248-.561 1.248-1.249 0-.688-.561-1.249-1.249-1.249zm5.5 0c-.687 0-1.248.561-1.248 1.25 0 .687.561 1.248 1.249 1.248.688 0 1.249-.561 1.249-1.249 0-.687-.562-1.249-1.25-1.249zm-5.466 3.99a.327.327 0 0 0-.231.094.33.33 0 0 0 0 .463c.842.842 2.484.913 2.961.913.477 0 2.105-.056 2.961-.913a.361.361 0 0 0 .029-.463.33.33 0 0 0-.464 0c-.547.533-1.684.73-2.512.73-.828 0-1.979-.196-2.512-.73a.326.326 0 0 0-.232-.095z',
            'color' => 'bg-orange-600 hover:bg-orange-700',
        ],
    ];
@endphp

<div 
    x-data="{
        open: @js($show),
        copied: false,
        
        copyLink() {
            const url = '{{ $url }}';
            
            if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(() => {
                    this.copied = true;
                    setTimeout(() => this.copied = false, 2000);
                    
                    this.$dispatch('toast', {
                        message: 'Link copied to clipboard!',
                        type: 'success'
                    });
                }).catch(err => {
                    console.error('Failed to copy:', err);
                    this.fallbackCopy(url);
                });
            } else {
                this.fallbackCopy(url);
            }
        },
        
        fallbackCopy(text) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            document.body.appendChild(textArea);
            textArea.select();
            
            try {
                document.execCommand('copy');
                this.copied = true;
                setTimeout(() => this.copied = false, 2000);
                
                this.$dispatch('toast', {
                    message: 'Link copied to clipboard!',
                    type: 'success'
                });
            } catch (err) {
                console.error('Fallback copy failed:', err);
                alert('Failed to copy link. Please copy manually: ' + text);
            }
            
            document.body.removeChild(textArea);
        },
        
        share(platform, url) {
            window.open(url, platform + '-share', 'width=600,height=400');
        }
    }"
    @keydown.escape.window="open = false"
    x-show="open"
    class="fixed inset-0 z-50 overflow-y-auto"
    style="display: none;"
    role="dialog"
    aria-modal="true"
    aria-labelledby="share-modal-title"
>
    <!-- Backdrop -->
    <div 
        x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"
        @click="open = false"
    ></div>
    
    <!-- Modal -->
    <div class="flex min-h-screen items-center justify-center p-4">
        <div 
            x-show="open"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform scale-95"
            x-transition:enter-end="opacity-100 transform scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 transform scale-100"
            x-transition:leave-end="opacity-0 transform scale-95"
            class="relative w-full max-w-md bg-white dark:bg-gray-800 rounded-lg shadow-xl"
            @click.stop
        >
            <!-- Header -->
            <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 id="share-modal-title" class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    Share Article
                </h3>
                <button
                    @click="open = false"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                    aria-label="Close modal"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <!-- Content -->
            <div class="p-6">
                <!-- Article Preview -->
                <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">
                        {{ $title }}
                    </h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ $excerpt }}
                    </p>
                </div>
                
                <!-- Share Buttons -->
                <div class="grid grid-cols-2 gap-3 mb-6">
                    @foreach($shareLinks as $platform => $config)
                        <button
                            @click="share('{{ $platform }}', '{{ $config['url'] }}')"
                            class="flex items-center justify-center gap-2 px-4 py-3 text-white rounded-lg transition-colors {{ $config['color'] }}"
                        >
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="{{ $config['icon'] }}" />
                            </svg>
                            <span class="font-medium">{{ $config['label'] }}</span>
                        </button>
                    @endforeach
                </div>
                
                <!-- Copy Link -->
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Or copy link
                    </label>
                    <div class="flex gap-2">
                        <input
                            type="text"
                            readonly
                            value="{{ $url }}"
                            class="flex-1 px-3 py-2 text-sm bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            @click="$el.select()"
                        />
                        <button
                            @click="copyLink"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors"
                            :class="{ 'bg-green-600 hover:bg-green-700': copied }"
                        >
                            <span x-show="!copied">Copy</span>
                            <span x-show="copied">Copied!</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

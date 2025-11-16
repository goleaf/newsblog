@props([
    'provider' => 'twitter', // twitter|facebook|instagram
    'url' => '',
    'title' => null,
])

<div
    x-data="socialEmbedComponent({ provider: @js($provider), url: @js($url) })"
    x-init="init()"
    class="w-full"
>
    <div x-ref="placeholder" class="w-full rounded-md border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
        <div class="flex items-center justify-between">
            <div class="text-sm text-gray-700 dark:text-gray-300">
                <span x-text="label()"></span>
            </div>
            <a href="{{ $url }}" :href="url" target="_blank" rel="noopener" class="text-primary-600 hover:text-primary-700 text-sm">
                <span x-text="$t('social.open')">Open</span>
            </a>
        </div>
        <p class="mt-2 text-gray-500 dark:text-gray-400 truncate" x-text="url"></p>
    </div>
    <!-- Policy: no external SDKs/CDNs; render styled fallback and link -->
</div>



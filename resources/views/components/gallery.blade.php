@props([
    'images' => [], // [['src' => '', 'alt' => '', 'thumb' => '']]
    'autoplay' => true,
    'intervalMs' => 3000,
])

<div
    x-data="galleryComponent({ images: @js($images), autoplay: @js($autoplay), intervalMs: @js($intervalMs) })"
    x-init="init()"
    class="w-full"
>
    <div class="relative aspect-video bg-gray-100 dark:bg-gray-900 rounded-md overflow-hidden">
        <template x-if="currentImage">
            <img
                :src="currentImage.src"
                :alt="currentImage.alt || ''"
                class="h-full w-full object-cover"
                x-show="loaded"
                x-transition.opacity
                @load="onMainLoaded"
            />
        </template>

        <div class="absolute inset-0 flex items-center justify-between px-2">
            <button type="button" @click="prev()"
                class="p-2 rounded-full bg-white/70 dark:bg-gray-800/70 hover:bg-white dark:hover:bg-gray-800 shadow focus:outline-none focus:ring focus:ring-primary-400"
                :aria-label="$t('gallery.prev')">
                <span class="sr-only" x-text="$t('gallery.prev')"></span>
                ‹
            </button>
            <button type="button" @click="next()"
                class="p-2 rounded-full bg-white/70 dark:bg-gray-800/70 hover:bg-white dark:hover:bg-gray-800 shadow focus:outline-none focus:ring focus:ring-primary-400"
                :aria-label="$t('gallery.next')">
                <span class="sr-only" x-text="$t('gallery.next')"></span>
                ›
            </button>
        </div>

        <div class="absolute bottom-0 left-0 right-0 flex items-center justify-between p-2 bg-gradient-to-t from-black/40 to-transparent text-white text-xs">
            <div x-text="counterText()"></div>
            <div class="flex items-center gap-2">
                <button type="button" @click="toggleAutoplay()" class="px-2 py-1 rounded bg-white/70 text-gray-900">
                    <span x-text="autoplaying ? $t('gallery.pause') : $t('gallery.play')">Play</span>
                </button>
                <button type="button" @click="toggleFullscreen()" class="px-2 py-1 rounded bg-white/70 text-gray-900">
                    <span x-text="$t('gallery.fullscreen')">Full screen</span>
                </button>
            </div>
        </div>

        <div
            class="absolute inset-0"
            @touchstart="onTouchStart($event)"
            @touchmove="onTouchMove($event)"
            @touchend="onTouchEnd($event)"
        ></div>
    </div>

    <div class="mt-3 flex gap-2 overflow-x-auto">
        <template x-for="(img, idx) in images" :key="idx">
            <button type="button" @click="go(idx)"
                class="relative h-16 w-24 flex-shrink-0 rounded overflow-hidden ring-2"
                :class="currentIndex === idx ? 'ring-primary-500' : 'ring-transparent'">
                <img :src="img.thumb || img.src" :alt="img.alt || ''" class="h-full w-full object-cover" loading="lazy" />
            </button>
        </template>
    </div>
</div>



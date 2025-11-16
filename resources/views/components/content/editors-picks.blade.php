@props([
    'posts' => collect(),
])

@if($posts->count() > 0)
<section>
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ __("Editor's Picks") }}</h2>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($posts as $post)
            <x-content.post-card :post="$post" :showImage="true" />
        @endforeach
    </div>
</section>
@endif


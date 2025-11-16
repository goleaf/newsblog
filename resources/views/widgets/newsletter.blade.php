<div class="widget bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
    <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">{{ $widget->title }}</h3>
    
    <form action="{{ route('newsletter.subscribe') }}" method="POST" class="space-y-3" aria-describedby="newsletter-hint">
        @csrf
        <div>
            <label for="newsletter-email" class="sr-only">{{ __('Email address') }}</label>
            <input type="email" 
                   id="newsletter-email"
                   name="email" 
                   placeholder="Enter your email" 
                   required
                   aria-describedby="newsletter-hint"
                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-100">
        </div>
        <button type="submit" 
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition">
            Subscribe
        </button>
    </form>
    
    <p id="newsletter-hint" class="text-xs text-gray-500 dark:text-gray-400 mt-3">
        Get the latest posts delivered right to your inbox.
    </p>
</div>

@props([
    'showWidgets' => true,
])

@php
    // Fetch legal pages for footer
    $legalPages = \App\Models\Page::active()
        ->whereIn('slug', ['privacy-policy', 'terms-of-service', 'cookie-policy', 'gdpr'])
        ->ordered()
        ->get();
    
    // Get social media links from settings
    $socialLinks = [
        'twitter' => config('app.social.twitter', '#'),
        'github' => config('app.social.github', '#'),
        'linkedin' => config('app.social.linkedin', '#'),
        'rss' => \Illuminate\Support\Facades\Route::has('feed') ? route('feed') : '#',
    ];
@endphp

<footer class="bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-800 mt-auto" role="contentinfo">
    <!-- Widget Areas -->
    @if($showWidgets)
    <div class="bg-gray-50 dark:bg-gray-800/50 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Footer Widget Area -->
                <x-layout.widget-area slug="footer-1" />
                <x-layout.widget-area slug="footer-2" />
                <x-layout.widget-area slug="footer-3" />
                <x-layout.widget-area slug="footer-4" />
            </div>
        </div>
    </div>
    @endif

    <!-- Main Footer -->
    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 lg:gap-12">
                <!-- About Section -->
                <div class="space-y-4">
                    <div>
                        <a href="{{ route('home') }}" class="flex items-center space-x-2 mb-4">
                            <x-application-logo class="h-8 w-auto" />
                            <span class="text-xl font-bold text-gray-900 dark:text-white">
                                {{ config('app.name') }}
                            </span>
                        </a>
                        <p class="text-gray-600 dark:text-gray-400 text-sm leading-relaxed">
                            Your trusted source for the latest technology news, in-depth tutorials, expert insights, and comprehensive guides. Stay informed and ahead in the ever-evolving tech landscape.
                        </p>
                    </div>
                    
                    <!-- Social Media Links -->
                    <div>
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">
                            Follow Us
                        </h4>
                        <div class="flex gap-3">
                            @if($socialLinks['twitter'] !== '#')
                            <a 
                                href="{{ $socialLinks['twitter'] }}" 
                                target="_blank"
                                rel="noopener noreferrer"
                                class="p-2 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-blue-100 dark:hover:bg-blue-900/30 hover:text-blue-600 dark:hover:text-blue-400 transition-colors" 
                                aria-label="Follow us on Twitter"
                            >
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84" />
                                </svg>
                            </a>
                            @endif
                            
                            @if($socialLinks['github'] !== '#')
                            <a 
                                href="{{ $socialLinks['github'] }}" 
                                target="_blank"
                                rel="noopener noreferrer"
                                class="p-2 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-900 dark:hover:bg-gray-700 hover:text-white transition-colors" 
                                aria-label="Follow us on GitHub"
                            >
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd" />
                                </svg>
                            </a>
                            @endif
                            
                            @if($socialLinks['linkedin'] !== '#')
                            <a 
                                href="{{ $socialLinks['linkedin'] }}" 
                                target="_blank"
                                rel="noopener noreferrer"
                                class="p-2 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-blue-700 hover:text-white transition-colors" 
                                aria-label="Follow us on LinkedIn"
                            >
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z" clip-rule="evenodd" />
                                </svg>
                            </a>
                            @endif
                            
                            <a 
                                href="{{ $socialLinks['rss'] }}" 
                                class="p-2 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-orange-100 dark:hover:bg-orange-900/30 hover:text-orange-600 dark:hover:text-orange-400 transition-colors" 
                                aria-label="Subscribe to RSS Feed"
                            >
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M6.503 20.752c0 1.794-1.456 3.248-3.251 3.248-1.796 0-3.252-1.454-3.252-3.248 0-1.794 1.456-3.248 3.252-3.248 1.795.001 3.251 1.454 3.251 3.248zm-6.503-12.572v4.811c6.05.062 10.96 4.966 11.022 11.009h4.817c-.062-8.71-7.118-15.758-15.839-15.82zm0-3.368c10.58.046 19.152 8.594 19.183 19.188h4.817c-.03-13.231-10.755-23.954-24-24v4.812z" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Quick Links -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4 uppercase tracking-wider">
                        Quick Links
                    </h3>
                    <ul class="space-y-3">
                        <li>
                            <a href="{{ route('home') }}" class="text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 text-sm transition-colors inline-flex items-center group">
                                <svg class="w-4 h-4 mr-2 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                                Home
                            </a>
                        </li>
                        @auth
                        <li>
                            <a href="{{ route('dashboard') }}" class="text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 text-sm transition-colors inline-flex items-center group">
                                <svg class="w-4 h-4 mr-2 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                                Dashboard
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('bookmarks.index') }}" class="text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 text-sm transition-colors inline-flex items-center group">
                                <svg class="w-4 h-4 mr-2 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                                Bookmarks
                            </a>
                        </li>
                        @endauth
                        <li>
                            <a href="{{ route('series.index') }}" class="text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 text-sm transition-colors inline-flex items-center group">
                                <svg class="w-4 h-4 mr-2 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                                Series
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('search') }}" class="text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 text-sm transition-colors inline-flex items-center group">
                                <svg class="w-4 h-4 mr-2 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                                Search
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Resources -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4 uppercase tracking-wider">
                        Resources
                    </h3>
                    <ul class="space-y-3">
                        @php
                            $resourcePages = \App\Models\Page::active()
                                ->whereIn('slug', ['about', 'contact', 'advertise', 'write-for-us'])
                                ->get()
                                ->keyBy('slug');
                        @endphp
                        
                        @if($resourcePages->has('about'))
                        <li>
                            <a href="{{ route('page.show', 'about') }}" class="text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 text-sm transition-colors inline-flex items-center group">
                                <svg class="w-4 h-4 mr-2 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                                About Us
                            </a>
                        </li>
                        @endif
                        
                        @if($resourcePages->has('contact'))
                        <li>
                            <a href="{{ route('page.show', 'contact') }}" class="text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 text-sm transition-colors inline-flex items-center group">
                                <svg class="w-4 h-4 mr-2 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                                Contact Us
                            </a>
                        </li>
                        @endif
                        
                        @if($resourcePages->has('advertise'))
                        <li>
                            <a href="{{ route('page.show', 'advertise') }}" class="text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 text-sm transition-colors inline-flex items-center group">
                                <svg class="w-4 h-4 mr-2 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                                Advertise
                            </a>
                        </li>
                        @endif
                        
                        @if($resourcePages->has('write-for-us'))
                        <li>
                            <a href="{{ route('page.show', 'write-for-us') }}" class="text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 text-sm transition-colors inline-flex items-center group">
                                <svg class="w-4 h-4 mr-2 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                                Write for Us
                            </a>
                        </li>
                        @endif
                        
                        <li>
                            <a href="{{ route('home') }}#newsletter" class="text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 text-sm transition-colors inline-flex items-center group">
                                <svg class="w-4 h-4 mr-2 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                                Newsletter
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Legal -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4 uppercase tracking-wider">
                        Legal
                    </h3>
                    <ul class="space-y-3">
                        @if($legalPages->where('slug', 'privacy-policy')->first())
                        <li>
                            <a href="{{ route('page.show', 'privacy-policy') }}" class="text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 text-sm transition-colors inline-flex items-center group">
                                <svg class="w-4 h-4 mr-2 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                                Privacy Policy
                            </a>
                        </li>
                        @endif
                        
                        @if($legalPages->where('slug', 'terms-of-service')->first())
                        <li>
                            <a href="{{ route('page.show', 'terms-of-service') }}" class="text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 text-sm transition-colors inline-flex items-center group">
                                <svg class="w-4 h-4 mr-2 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                                Terms of Service
                            </a>
                        </li>
                        @endif
                        
                        @if($legalPages->where('slug', 'cookie-policy')->first())
                        <li>
                            <a href="{{ route('page.show', 'cookie-policy') }}" class="text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 text-sm transition-colors inline-flex items-center group">
                                <svg class="w-4 h-4 mr-2 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                                Cookie Policy
                            </a>
                        </li>
                        @endif
                        
                        @if($legalPages->where('slug', 'gdpr')->first())
                        <li>
                            <a href="{{ route('page.show', 'gdpr') }}" class="text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 text-sm transition-colors inline-flex items-center group">
                                <svg class="w-4 h-4 mr-2 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                                GDPR Compliance
                            </a>
                        </li>
                        @endif
                        
                        <li>
                            <a href="{{ route('sitemap.index') }}" class="text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 text-sm transition-colors inline-flex items-center group">
                                <svg class="w-4 h-4 mr-2 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                                Sitemap
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Bar -->
    <div class="border-t border-gray-200 dark:border-gray-800 py-6 bg-gray-50 dark:bg-gray-800/30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                <p class="text-sm text-gray-600 dark:text-gray-400 text-center md:text-left">
                    &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved. Built with ❤️ for developers.
                </p>
                <div class="flex flex-wrap justify-center gap-4 md:gap-6">
                    @if($legalPages->where('slug', 'privacy-policy')->first())
                    <a href="{{ route('page.show', 'privacy-policy') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                        Privacy
                    </a>
                    @endif
                    
                    @if($legalPages->where('slug', 'terms-of-service')->first())
                    <a href="{{ route('page.show', 'terms-of-service') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                        Terms
                    </a>
                    @endif
                    
                    <a href="{{ route('page.show', 'contact') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                        Contact
                    </a>
                    
                    <button 
                        @click="window.scrollTo({ top: 0, behavior: 'smooth' })"
                        class="text-sm text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors inline-flex items-center"
                        aria-label="Back to top"
                    >
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                        </svg>
                        Back to Top
                    </button>
                </div>
            </div>
        </div>
    </div>
</footer>

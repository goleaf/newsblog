{{--
    Mobile Profile Page Component
    
    Mobile-optimized user profile layout.
    Requirements: 17.1, 17.2
--}}

@props([
    'user',
])

<div class="lg:hidden">
    {{-- Profile Header --}}
    <div class="bg-gradient-to-br from-blue-600 to-purple-600 px-4 py-8">
        <div class="text-center">
            {{-- Avatar --}}
            <div class="relative inline-block mb-4">
                @if($user->avatar_url)
                    <x-optimized-image 
                        :src="$user->avatar_url"
                        :alt="$user->name"
                        :width="120"
                        :height="120"
                        :blur-up="false"
                        class="w-24 h-24 rounded-full object-cover border-4 border-white shadow-lg"
                    />
                @else
                    <div class="w-24 h-24 rounded-full bg-white flex items-center justify-center border-4 border-white shadow-lg">
                        <span class="text-3xl font-bold text-blue-600">
                            {{ substr($user->name, 0, 1) }}
                        </span>
                    </div>
                @endif
                
                {{-- Edit Button (if own profile) --}}
                @if(auth()->id() === $user->id)
                    <a 
                        href="{{ route('profile.edit') }}"
                        class="absolute bottom-0 right-0 p-2 bg-white rounded-full shadow-lg text-blue-600 hover:bg-gray-50 touch-target"
                        aria-label="Edit profile"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                    </a>
                @endif
            </div>
            
            {{-- Name & Bio --}}
            <h1 class="text-2xl font-bold text-white mb-2">{{ $user->name }}</h1>
            
            @if($user->profile && $user->profile->bio)
                <p class="text-white/90 text-sm max-w-md mx-auto">
                    {{ $user->profile->bio }}
                </p>
            @endif
            
            {{-- Stats --}}
            <div class="flex justify-center gap-6 mt-6">
                <div class="text-center">
                    <div class="text-2xl font-bold text-white">{{ $user->posts_count ?? 0 }}</div>
                    <div class="text-xs text-white/80">Articles</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-white">{{ $user->followers_count ?? 0 }}</div>
                    <div class="text-xs text-white/80">Followers</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-white">{{ $user->following_count ?? 0 }}</div>
                    <div class="text-xs text-white/80">Following</div>
                </div>
            </div>
            
            {{-- Action Buttons --}}
            <div class="flex gap-3 mt-6">
                @if(auth()->id() !== $user->id)
                    <x-follow-button :user="$user" class="flex-1" />
                @endif
                
                @if($user->profile && $user->profile->website)
                    <a 
                        href="{{ $user->profile->website }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="flex-1 px-4 py-3 min-h-[44px] bg-white/20 backdrop-blur-sm text-white rounded-lg font-medium hover:bg-white/30 transition-colors touch-target flex items-center justify-center gap-2"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                        Website
                    </a>
                @endif
            </div>
        </div>
    </div>
    
    {{-- Tabs --}}
    <div 
        x-data="{ activeTab: 'articles' }"
        class="sticky top-0 z-10 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700"
    >
        <div class="flex overflow-x-auto scrollbar-hide">
            <button 
                @click="activeTab = 'articles'"
                type="button"
                class="flex-1 px-4 py-4 min-h-[44px] text-sm font-medium border-b-2 transition-colors touch-target"
                :class="activeTab === 'articles' ? 'border-blue-600 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400'"
            >
                Articles
            </button>
            <button 
                @click="activeTab = 'about'"
                type="button"
                class="flex-1 px-4 py-4 min-h-[44px] text-sm font-medium border-b-2 transition-colors touch-target"
                :class="activeTab === 'about' ? 'border-blue-600 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400'"
            >
                About
            </button>
            <button 
                @click="activeTab = 'activity'"
                type="button"
                class="flex-1 px-4 py-4 min-h-[44px] text-sm font-medium border-b-2 transition-colors touch-target"
                :class="activeTab === 'activity' ? 'border-blue-600 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400'"
            >
                Activity
            </button>
        </div>
        
        {{-- Tab Content --}}
        <div class="p-4">
            {{-- Articles Tab --}}
            <div x-show="activeTab === 'articles'">
                @if($user->posts && $user->posts->count() > 0)
                    <div class="space-y-4">
                        @foreach($user->posts as $post)
                            <x-mobile.article-card :post="$post" />
                        @endforeach
                    </div>
                @else
                    <div class="py-12 text-center">
                        <svg class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="text-gray-500 dark:text-gray-400">No articles yet</p>
                    </div>
                @endif
            </div>
            
            {{-- About Tab --}}
            <div x-show="activeTab === 'about'">
                <div class="space-y-6">
                    @if($user->profile)
                        {{-- Bio --}}
                        @if($user->profile->bio)
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">Bio</h3>
                                <p class="text-gray-700 dark:text-gray-300">{{ $user->profile->bio }}</p>
                            </div>
                        @endif
                        
                        {{-- Location --}}
                        @if($user->profile->location)
                            <div class="flex items-center gap-3 text-gray-700 dark:text-gray-300">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span>{{ $user->profile->location }}</span>
                            </div>
                        @endif
                        
                        {{-- Company --}}
                        @if($user->profile->company)
                            <div class="flex items-center gap-3 text-gray-700 dark:text-gray-300">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                <span>{{ $user->profile->company }}</span>
                            </div>
                        @endif
                        
                        {{-- Social Links --}}
                        @if($user->profile->twitter_handle || $user->profile->github_username || $user->profile->linkedin_url)
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Social Links</h3>
                                <div class="flex gap-3">
                                    @if($user->profile->twitter_handle)
                                        <a 
                                            href="https://twitter.com/{{ $user->profile->twitter_handle }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="p-3 min-w-[44px] min-h-[44px] flex items-center justify-center bg-gray-100 dark:bg-gray-800 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors touch-target"
                                            aria-label="Twitter"
                                        >
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M23 3a10.9 10.9 0 01-3.14 1.53 4.48 4.48 0 00-7.86 3v1A10.66 10.66 0 013 4s-4 9 5 13a11.64 11.64 0 01-7 2c9 5 20 0 20-11.5a4.5 4.5 0 00-.08-.83A7.72 7.72 0 0023 3z" />
                                            </svg>
                                        </a>
                                    @endif
                                    
                                    @if($user->profile->github_username)
                                        <a 
                                            href="https://github.com/{{ $user->profile->github_username }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="p-3 min-w-[44px] min-h-[44px] flex items-center justify-center bg-gray-100 dark:bg-gray-800 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors touch-target"
                                            aria-label="GitHub"
                                        >
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z" />
                                            </svg>
                                        </a>
                                    @endif
                                    
                                    @if($user->profile->linkedin_url)
                                        <a 
                                            href="{{ $user->profile->linkedin_url }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="p-3 min-w-[44px] min-h-[44px] flex items-center justify-center bg-gray-100 dark:bg-gray-800 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors touch-target"
                                            aria-label="LinkedIn"
                                        >
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z" />
                                            </svg>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endif
                    
                    {{-- Member Since --}}
                    <div class="flex items-center gap-3 text-gray-700 dark:text-gray-300">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span>Member since {{ $user->created_at->format('F Y') }}</span>
                    </div>
                </div>
            </div>
            
            {{-- Activity Tab --}}
            <div x-show="activeTab === 'activity'">
                <div class="py-12 text-center">
                    <svg class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <p class="text-gray-500 dark:text-gray-400">Activity feed coming soon</p>
                </div>
            </div>
        </div>
    </div>
</div>

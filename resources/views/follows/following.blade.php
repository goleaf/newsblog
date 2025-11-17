<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ $user->name }}'s Following
            </h2>
            <a href="{{ route('users.show', $user) }}" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                Back to Profile
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
                <div class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 px-6 py-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        Following ({{ $following->total() }})
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        People {{ $user->name }} is following
                    </p>
                </div>

                @if($following->count() > 0)
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($following as $follow)
                            @php
                                $followedUser = $follow->followed;
                            @endphp
                            <div class="p-6 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-4">
                                        <a href="{{ route('users.show', $followedUser) }}">
                                            <img 
                                                src="{{ $followedUser->avatar_url }}" 
                                                alt="{{ $followedUser->name }}" 
                                                class="w-12 h-12 rounded-full"
                                            >
                                        </a>
                                        <div>
                                            <a href="{{ route('users.show', $followedUser) }}" class="text-lg font-semibold text-gray-900 dark:text-gray-100 hover:text-blue-600 dark:hover:text-blue-400">
                                                {{ $followedUser->name }}
                                            </a>
                                            @if($followedUser->bio)
                                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                                    {{ Str::limit($followedUser->bio, 100) }}
                                                </p>
                                            @endif
                                            <div class="flex items-center gap-4 mt-2 text-xs text-gray-500 dark:text-gray-400">
                                                <span>{{ $followedUser->followers()->count() }} followers</span>
                                                <span>{{ $followedUser->following()->count() }} following</span>
                                            </div>
                                        </div>
                                    </div>

                                    @auth
                                        @if(auth()->id() !== $followedUser->id)
                                            <x-follow-button :user="$followedUser" />
                                        @endif
                                    @endauth
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                        {{ $following->links() }}
                    </div>
                @else
                    <div class="p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">Not following anyone yet</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ $user->name }} isn't following anyone yet.
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>

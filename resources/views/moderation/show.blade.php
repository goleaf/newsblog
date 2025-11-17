<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Review Content') }}
            </h2>
            <a href="{{ route('moderation.index') }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300">
                ← Back to Queue
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Main Content Area --}}
                <div class="lg:col-span-2 space-y-6">
                    {{-- Content Preview --}}
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Content Preview</h3>
                            
                            @if ($content)
                                @if ($moderationQueue->type === 'comment')
                                    <div class="space-y-4">
                                        {{-- Comment Context --}}
                                        <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-lg">
                                            <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                                Comment on: 
                                                <a href="{{ route('posts.show', $content->post->slug) }}" class="text-blue-600 dark:text-blue-400 hover:underline" target="_blank">
                                                    {{ $content->post->title }}
                                                </a>
                                            </div>
                                            @if ($content->parent)
                                                <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                                    Reply to: {{ Str::limit($content->parent->content, 100) }}
                                                </div>
                                            @endif
                                        </div>

                                        {{-- Comment Content --}}
                                        <div class="border-l-4 border-blue-500 pl-4">
                                            <div class="flex items-center gap-2 mb-2">
                                                <img src="{{ $content->user->avatar_url }}" alt="{{ $content->user->name }}" class="w-8 h-8 rounded-full">
                                                <div>
                                                    <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $content->user->name }}</div>
                                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $content->created_at->format('M d, Y H:i') }}</div>
                                                </div>
                                            </div>
                                            <div class="prose dark:prose-invert max-w-none">
                                                {{ $content->content }}
                                            </div>
                                        </div>

                                        {{-- Comment Metadata --}}
                                        <div class="grid grid-cols-2 gap-4 text-sm">
                                            <div>
                                                <span class="text-gray-600 dark:text-gray-400">IP Address:</span>
                                                <span class="text-gray-900 dark:text-gray-100">{{ $content->ip_address ?? 'N/A' }}</span>
                                            </div>
                                            <div>
                                                <span class="text-gray-600 dark:text-gray-400">User Agent:</span>
                                                <span class="text-gray-900 dark:text-gray-100">{{ Str::limit($content->user_agent ?? 'N/A', 50) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @else
                                <div class="text-gray-500 dark:text-gray-400">Content not found or has been deleted.</div>
                            @endif
                        </div>
                    </div>

                    {{-- Moderation Actions --}}
                    @if ($moderationQueue->status === 'pending')
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Moderation Actions</h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    {{-- Approve --}}
                                    <form method="POST" action="{{ route('moderation.approve', $moderationQueue) }}" class="flex flex-col">
                                        @csrf
                                        <label for="approve-notes" class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Notes (optional)</label>
                                        <textarea name="notes" id="approve-notes" rows="3" class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 mb-3"></textarea>
                                        <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md shadow-sm">
                                            ✓ Approve
                                        </button>
                                    </form>

                                    {{-- Reject --}}
                                    <form method="POST" action="{{ route('moderation.reject', $moderationQueue) }}" class="flex flex-col">
                                        @csrf
                                        <label for="reject-reason" class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Reason *</label>
                                        <textarea name="reason" id="reject-reason" rows="3" required class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-yellow-500 focus:ring-yellow-500 mb-3"></textarea>
                                        <button type="submit" class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-md shadow-sm">
                                            ✗ Reject
                                        </button>
                                    </form>

                                    {{-- Delete --}}
                                    <form method="POST" action="{{ route('moderation.delete', $moderationQueue) }}" class="flex flex-col" onsubmit="return confirm('Are you sure you want to delete this content? This action cannot be undone.');">
                                        @csrf
                                        <label for="delete-reason" class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Reason *</label>
                                        <textarea name="reason" id="delete-reason" rows="3" required class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 mb-3"></textarea>
                                        <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md shadow-sm">
                                            🗑 Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <div class="text-center text-gray-600 dark:text-gray-400">
                                    This item has already been reviewed.
                                    <div class="mt-2">
                                        Status: <span class="font-semibold">{{ ucfirst($moderationQueue->status) }}</span>
                                    </div>
                                    @if ($moderationQueue->reviewer)
                                        <div class="mt-1">
                                            Reviewed by: <span class="font-semibold">{{ $moderationQueue->reviewer->name }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Sidebar --}}
                <div class="space-y-6">
                    {{-- Queue Item Info --}}
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Queue Information</h3>
                            <dl class="space-y-3 text-sm">
                                <div>
                                    <dt class="text-gray-600 dark:text-gray-400">Type</dt>
                                    <dd class="text-gray-900 dark:text-gray-100 font-medium">{{ ucfirst($moderationQueue->type) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-600 dark:text-gray-400">Status</dt>
                                    <dd class="text-gray-900 dark:text-gray-100 font-medium">{{ ucfirst($moderationQueue->status) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-600 dark:text-gray-400">Reason</dt>
                                    <dd class="text-gray-900 dark:text-gray-100">{{ $moderationQueue->reason ?? 'N/A' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-600 dark:text-gray-400">Submitted</dt>
                                    <dd class="text-gray-900 dark:text-gray-100">{{ $moderationQueue->created_at->format('M d, Y H:i') }}</dd>
                                </div>
                                @if ($moderationQueue->reviewed_at)
                                    <div>
                                        <dt class="text-gray-600 dark:text-gray-400">Reviewed</dt>
                                        <dd class="text-gray-900 dark:text-gray-100">{{ $moderationQueue->reviewed_at->format('M d, Y H:i') }}</dd>
                                    </div>
                                @endif
                            </dl>
                        </div>
                    </div>

                    {{-- User History --}}
                    @if ($userHistory && $content)
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">User History</h3>
                                
                                <div class="space-y-4">
                                    {{-- User Info --}}
                                    <div class="flex items-center gap-3">
                                        <img src="{{ $content->user->avatar_url }}" alt="{{ $content->user->name }}" class="w-12 h-12 rounded-full">
                                        <div>
                                            <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $content->user->name }}</div>
                                            <div class="text-sm text-gray-600 dark:text-gray-400">{{ $content->user->email }}</div>
                                        </div>
                                    </div>

                                    {{-- Reputation --}}
                                    @if ($userHistory['reputation'])
                                        <div class="bg-gray-50 dark:bg-gray-900 p-3 rounded-lg">
                                            <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Reputation</div>
                                            <div class="flex items-center gap-2">
                                                <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $userHistory['reputation']->score }}</div>
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">
                                                    {{ ucfirst($userHistory['reputation']->level) }}
                                                </span>
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Comment Statistics --}}
                                    <div class="grid grid-cols-2 gap-3 text-sm">
                                        <div class="bg-gray-50 dark:bg-gray-900 p-3 rounded-lg">
                                            <div class="text-gray-600 dark:text-gray-400">Total</div>
                                            <div class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $userHistory['total_comments'] }}</div>
                                        </div>
                                        <div class="bg-green-50 dark:bg-green-900 p-3 rounded-lg">
                                            <div class="text-green-600 dark:text-green-400">Approved</div>
                                            <div class="text-xl font-bold text-green-900 dark:text-green-100">{{ $userHistory['approved_comments'] }}</div>
                                        </div>
                                        <div class="bg-red-50 dark:bg-red-900 p-3 rounded-lg">
                                            <div class="text-red-600 dark:text-red-400">Rejected</div>
                                            <div class="text-xl font-bold text-red-900 dark:text-red-100">{{ $userHistory['rejected_comments'] }}</div>
                                        </div>
                                        <div class="bg-yellow-50 dark:bg-yellow-900 p-3 rounded-lg">
                                            <div class="text-yellow-600 dark:text-yellow-400">Flagged</div>
                                            <div class="text-xl font-bold text-yellow-900 dark:text-yellow-100">{{ $userHistory['flagged_comments'] }}</div>
                                        </div>
                                    </div>

                                    {{-- Recent Actions --}}
                                    @if ($userHistory['recent_actions']->isNotEmpty())
                                        <div>
                                            <div class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Recent Actions</div>
                                            <div class="space-y-2">
                                                @foreach ($userHistory['recent_actions']->take(5) as $action)
                                                    <div class="text-xs text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-900 p-2 rounded">
                                                        <div class="font-medium">{{ ucfirst($action->action_type) }}</div>
                                                        <div>{{ $action->created_at->diffForHumans() }}</div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Ban User Button --}}
                                    @if ($content->user->status !== \App\Enums\UserStatus::Suspended)
                                        <form method="POST" action="{{ route('moderation.ban-user', $content->user) }}" onsubmit="return confirm('Are you sure you want to ban this user?');">
                                            @csrf
                                            <input type="hidden" name="reason" value="Banned from moderation review">
                                            <input type="hidden" name="duration" value="permanent">
                                            <button type="submit" class="w-full px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md shadow-sm text-sm">
                                                Ban User
                                            </button>
                                        </form>
                                    @else
                                        <div class="text-center text-sm text-red-600 dark:text-red-400 font-semibold">
                                            User is currently banned
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

@props(['notification'])

<div 
    x-data="{ notification: notification }"
    class="px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors cursor-pointer border-b border-gray-100 dark:border-gray-700 last:border-b-0"
    @click="window.location.href = notification.action_url || '#'"
>
    <div class="flex items-start gap-3">
        <!-- Icon -->
        <div class="flex-shrink-0">
            <div class="w-10 h-10 rounded-full flex items-center justify-center bg-blue-100 dark:bg-blue-900">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <template x-if="notification.data?.type === 'comment_reply'">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                    </template>
                    <template x-if="notification.data?.type === 'new_follower'">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                    </template>
                    <template x-if="notification.data?.type === 'author_new_article'">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path>
                    </template>
                    <template x-if="notification.data?.type === 'comment_reaction'">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                    </template>
                    <template x-if="notification.data?.type === 'mention'">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                    </template>
                    <template x-if="!['comment_reply', 'new_follower', 'author_new_article', 'comment_reaction', 'mention'].includes(notification.data?.type)">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </template>
                </svg>
            </div>
        </div>

        <!-- Content -->
        <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900 dark:text-white" x-text="notification.title"></p>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-0.5" x-text="notification.message"></p>
            <p class="text-xs text-gray-500 dark:text-gray-500 mt-1" x-text="formatTime(notification.created_at)"></p>
        </div>

        <!-- Unread Indicator -->
        <template x-if="!notification.read_at">
            <div class="flex-shrink-0">
                <div class="w-2 h-2 bg-blue-600 rounded-full"></div>
            </div>
        </template>
    </div>
</div>

<script>
function formatTime(timestamp) {
    const date = new Date(timestamp);
    const now = new Date();
    const diff = Math.floor((now - date) / 1000); // difference in seconds

    if (diff < 60) return 'Just now';
    if (diff < 3600) return Math.floor(diff / 60) + ' minutes ago';
    if (diff < 86400) return Math.floor(diff / 3600) + ' hours ago';
    if (diff < 604800) return Math.floor(diff / 86400) + ' days ago';
    
    return date.toLocaleDateString();
}
</script>

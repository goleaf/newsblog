@props(['post'])

<div 
    x-data="altTextChecker"
    x-init="checkAltText({{ $post->id }})"
    class="mb-4"
>
    <div 
        x-show="hasIssues" 
        x-cloak
        class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4"
    >
        <div class="flex items-start gap-3">
            <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
            <div class="flex-1">
                <h3 class="text-sm font-semibold text-yellow-800 dark:text-yellow-200 mb-2">
                    Accessibility Warning: Missing Alt Text
                </h3>
                <div class="text-sm text-yellow-700 dark:text-yellow-300 space-y-1">
                    <template x-for="issue in issues" :key="issue.type">
                        <p x-text="issue.message"></p>
                    </template>
                </div>
                <div class="mt-3">
                    <a href="{{ route('admin.alt-text.report') }}" class="text-sm font-medium text-yellow-800 dark:text-yellow-200 hover:text-yellow-900 dark:hover:text-yellow-100 underline">
                        View Accessibility Report →
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('altTextChecker', () => ({
        hasIssues: false,
        issues: [],
        
        async checkAltText(postId) {
            try {
                const response = await fetch(`/admin/posts/${postId}/validate-alt-text`);
                const data = await response.json();
                
                this.hasIssues = data.has_issues;
                this.issues = data.issues;
            } catch (error) {
                console.error('Failed to check alt text:', error);
            }
        }
    }));
});
</script>

<style>
[x-cloak] { display: none !important; }
</style>

@props(['post', 'parentId' => null])

<div 
    x-data="{
        content: '',
        authorName: '{{ old('author_name', auth()->user()->name ?? '') }}',
        authorEmail: '{{ old('author_email', auth()->user()->email ?? '') }}',
        preview: false,
        submitting: false,
        errors: {},
        maxLength: 5000,
        pageLoadTime: Math.floor(Date.now() / 1000),
        
        get remainingChars() {
            return this.maxLength - this.content.length;
        },
        
        get isValid() {
            return this.content.trim().length > 0 && 
                   this.authorName.trim().length > 0 && 
                   this.authorEmail.trim().length > 0 &&
                   this.content.length <= this.maxLength;
        },
        
        async submit() {
            if (!this.isValid || this.submitting) return;
            
            this.submitting = true;
            this.errors = {};
            
            const formData = new FormData();
            formData.append('post_id', '{{ $post->id }}');
            formData.append('content', this.content);
            formData.append('author_name', this.authorName);
            formData.append('author_email', this.authorEmail);
            formData.append('page_load_time', this.pageLoadTime);
            formData.append('honeypot', ''); // Spam detection honeypot
            
            @if($parentId)
                formData.append('parent_id', '{{ $parentId }}');
            @endif
            
            try {
                const response = await fetch('{{ route('comments.store') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                    },
                    body: formData
                });
                
                const data = await response.json();
                
                if (!response.ok) {
                    if (data.errors) {
                        this.errors = data.errors;
                    } else {
                        throw new Error(data.message || 'Failed to submit comment');
                    }
                    return;
                }
                
                // Success - clear form and show message
                this.content = '';
                this.preview = false;
                
                this.$dispatch('toast', {
                    message: data.message || 'Comment submitted successfully! It will appear after approval.',
                    type: 'success'
                });
                
                // Dispatch event to refresh comments
                this.$dispatch('comment-submitted');
                
            } catch (error) {
                console.error('Error submitting comment:', error);
                this.$dispatch('toast', {
                    message: 'Failed to submit comment. Please try again.',
                    type: 'error'
                });
            } finally {
                this.submitting = false;
            }
        }
    }"
    class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6"
>
    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
        @if($parentId)
            Reply to Comment
        @else
            Leave a Comment
        @endif
    </h3>
    
    <form @submit.prevent="submit" class="space-y-4">
        <!-- Guest Name and Email Fields -->
        @guest
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Name Field -->
                <div>
                    <label for="author_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Name <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        id="author_name"
                        x-model="authorName"
                        class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        :class="{
                            'border-red-500 dark:border-red-500': errors.author_name,
                            'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100': !errors.author_name
                        }"
                        placeholder="Your name"
                        required
                    />
                    <p x-show="errors.author_name" class="mt-1 text-sm text-red-600 dark:text-red-400" x-text="errors.author_name?.[0]"></p>
                </div>
                
                <!-- Email Field -->
                <div>
                    <label for="author_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Email <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="email"
                        id="author_email"
                        x-model="authorEmail"
                        class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        :class="{
                            'border-red-500 dark:border-red-500': errors.author_email,
                            'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100': !errors.author_email
                        }"
                        placeholder="your@email.com"
                        required
                    />
                    <p x-show="errors.author_email" class="mt-1 text-sm text-red-600 dark:text-red-400" x-text="errors.author_email?.[0]"></p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Your email will not be published</p>
                </div>
            </div>
        @endguest
        
        <!-- Comment Content -->
        <div>
            <div class="flex items-center justify-between mb-1">
                <label for="comment_content" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Comment <span class="text-red-500">*</span>
                </label>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="preview = !preview"
                        class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300"
                    >
                        <span x-show="!preview">Preview</span>
                        <span x-show="preview">Edit</span>
                    </button>
                </div>
            </div>
            
            <!-- Textarea -->
            <div x-show="!preview">
                <textarea
                    id="comment_content"
                    x-model="content"
                    rows="6"
                    class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 resize-y"
                    :class="{
                        'border-red-500 dark:border-red-500': errors.content,
                        'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100': !errors.content
                    }"
                    placeholder="Share your thoughts..."
                    :maxlength="maxLength"
                    required
                ></textarea>
                <div class="flex items-center justify-between mt-1">
                    <p x-show="errors.content" class="text-sm text-red-600 dark:text-red-400" x-text="errors.content?.[0]"></p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 ml-auto" :class="{ 'text-red-600 dark:text-red-400': remainingChars < 100 }">
                        <span x-text="remainingChars"></span> characters remaining
                    </p>
                </div>
            </div>
            
            <!-- Preview -->
            <div 
                x-show="preview"
                class="min-h-[150px] px-3 py-2 border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 rounded-lg"
            >
                <div class="prose dark:prose-invert max-w-none" x-html="content.replace(/\n/g, '<br>')"></div>
            </div>
            
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                Markdown is supported. Your comment will be reviewed before appearing on the site.
            </p>
        </div>
        
        <!-- Honeypot (hidden spam trap) -->
        <input type="text" name="honeypot" value="" style="display: none;" tabindex="-1" autocomplete="off" />
        
        <!-- Submit Button -->
        <div class="flex items-center justify-between">
            @guest
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    <a href="{{ route('login') }}" class="text-blue-600 dark:text-blue-400 hover:underline">Login</a> 
                    to comment as a registered user
                </p>
            @else
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Commenting as <strong>{{ auth()->user()->name }}</strong>
                </p>
            @endguest
            
            <button
                type="submit"
                :disabled="!isValid || submitting"
                class="px-6 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <span x-show="!submitting">
                    @if($parentId)
                        Post Reply
                    @else
                        Post Comment
                    @endif
                </span>
                <span x-show="submitting" class="flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Submitting...
                </span>
            </button>
        </div>
    </form>
</div>

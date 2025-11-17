@props(['user'])

@php
    $isFollowing = auth()->check() && auth()->user()->isFollowing($user);
@endphp

<div x-data="followButton({{ $user->id }}, {{ $isFollowing ? 'true' : 'false' }})" class="inline-block">
    <button 
        @click="toggle"
        :disabled="loading"
        type="button"
        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest transition ease-in-out duration-150 disabled:opacity-50"
        :class="following ? 'bg-gray-600 hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900' : 'bg-blue-600 hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900'"
    >
        <span x-show="!loading" x-text="following ? 'Following' : 'Follow'"></span>
        <span x-show="loading">
            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </span>
    </button>
</div>

@push('scripts')
<script>
function followButton(userId, initialFollowing) {
    return {
        following: initialFollowing,
        loading: false,
        
        async toggle() {
            this.loading = true;
            
            try {
                const url = this.following 
                    ? `/users/${userId}/follow`
                    : `/users/${userId}/follow`;
                    
                const method = this.following ? 'DELETE' : 'POST';
                
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });
                
                const data = await response.json();
                
                if (response.ok) {
                    this.following = data.following;
                } else {
                    alert(data.message || 'An error occurred');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
@endpush

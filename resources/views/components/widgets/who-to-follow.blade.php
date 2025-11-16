@props(['widget', 'users'])

<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
    <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Who to Follow</h3>
    </div>
    <ul class="divide-y divide-gray-200 dark:divide-gray-700">
        @foreach($users as $u)
        <li class="flex items-center justify-between px-4 py-3">
            <div class="flex items-center gap-3">
                <img src="https://ui-avatars.com/api/?name={{ urlencode($u->name) }}&size=64&background=random" alt="{{ $u->name }}" class="w-8 h-8 rounded-full" />
                <div>
                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $u->name }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $u->posts_count ?? 0 }} posts</div>
                </div>
            </div>
            <div x-data="{ following: false, busy: false }">
                <button
                    x-show="!following"
                    @click="busy=true; fetch('/api/v1/users/{{ $u->id }}/follow', { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' } }).then(()=>{ following=true; busy=false;}).catch(()=>{busy=false;})"
                    :disabled="busy"
                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded bg-indigo-600 hover:bg-indigo-700 text-white disabled:opacity-50">
                    Follow
                </button>
                <button
                    x-show="following"
                    @click="busy=true; fetch('/api/v1/users/{{ $u->id }}/follow', { method: 'DELETE', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' } }).then(()=>{ following=false; busy=false;}).catch(()=>{busy=false;})"
                    :disabled="busy"
                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-300 dark:hover:bg-gray-600 disabled:opacity-50">
                    Following
                </button>
            </div>
        </li>
        @endforeach
    </ul>
</div>


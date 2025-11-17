<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Newsletter Preview') }}
            </h2>
            <a href="{{ route('admin.newsletter.index') }}" 
               class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                Back to Dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <!-- Frequency Selector -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <form method="GET" action="{{ route('admin.newsletter.preview') }}" class="flex gap-4 items-center">
                        <label for="frequency" class="text-sm font-medium text-gray-700 dark:text-gray-300">Preview Frequency:</label>
                        <select name="frequency" id="frequency" onchange="this.form.submit()" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                            <option value="daily" {{ $frequency === 'daily' ? 'selected' : '' }}>Daily</option>
                            <option value="weekly" {{ $frequency === 'weekly' ? 'selected' : '' }}>Weekly</option>
                            <option value="monthly" {{ $frequency === 'monthly' ? 'selected' : '' }}>Monthly</option>
                        </select>
                    </form>
                </div>
            </div>

            <!-- Newsletter Preview -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Email Preview</h3>
                    
                    @if($articles->isEmpty())
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                            <p class="text-yellow-800 dark:text-yellow-200">No articles found for this frequency. The newsletter cannot be sent without content.</p>
                        </div>
                    @else
                        <!-- Render the actual email template -->
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                            @include('emails.newsletter', [
                                'subject' => $subject,
                                'articles' => $articles,
                                'subscriber' => $subscriber,
                                'greeting' => $greeting,
                                'unsubscribeUrl' => '#',
                                'preferencesUrl' => '#',
                            ])
                        </div>

                        <div class="mt-6 p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                            <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">Newsletter Details</h4>
                            <dl class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <dt class="text-gray-500 dark:text-gray-400">Subject:</dt>
                                    <dd class="text-gray-900 dark:text-gray-100 font-medium">{{ $subject }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500 dark:text-gray-400">Frequency:</dt>
                                    <dd class="text-gray-900 dark:text-gray-100 font-medium">{{ ucfirst($frequency) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500 dark:text-gray-400">Articles:</dt>
                                    <dd class="text-gray-900 dark:text-gray-100 font-medium">{{ $articles->count() }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500 dark:text-gray-400">Preview Email:</dt>
                                    <dd class="text-gray-900 dark:text-gray-100 font-medium">{{ $subscriber->email }}</dd>
                                </div>
                            </dl>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

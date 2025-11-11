@extends('admin.layouts.app')

@section('title', 'Maintenance Mode')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Maintenance Mode</h2>
            </div>

            <div class="p-6">
                <div class="mb-6">
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Status</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                @if($isEnabled)
                                    Maintenance mode is currently <span class="font-semibold text-red-600 dark:text-red-400">ENABLED</span>
                                @else
                                    Maintenance mode is currently <span class="font-semibold text-green-600 dark:text-green-400">DISABLED</span>
                                @endif
                            </p>
                        </div>
                        @if($isEnabled && $downFile)
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                Enabled: {{ \Carbon\Carbon::createFromTimestamp($downFile['time'])->format('Y-m-d H:i:s') }}
                            </div>
                        @endif
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.maintenance.toggle') }}" class="space-y-4">
                    @csrf
                    
                    @if($isEnabled)
                        <input type="hidden" name="action" value="disable">
                        <button type="submit" class="w-full bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                            Disable Maintenance Mode
                        </button>
                    @else
                        <input type="hidden" name="action" value="enable">
                        <div>
                            <label for="message" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Maintenance Message (optional)
                            </label>
                            <textarea name="message" id="message" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="We are currently performing maintenance. Please check back soon."></textarea>
                        </div>
                        <button type="submit" class="w-full bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700">
                            Enable Maintenance Mode
                        </button>
                    @endif
                </form>

                <div class="mt-6 p-4 bg-yellow-50 dark:bg-yellow-900 rounded-lg">
                    <h4 class="text-sm font-medium text-yellow-800 dark:text-yellow-200 mb-2">Warning</h4>
                    <p class="text-sm text-yellow-700 dark:text-yellow-300">
                        When maintenance mode is enabled, all visitors (except administrators) will see a maintenance page. 
                        Make sure to disable it when maintenance is complete.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection


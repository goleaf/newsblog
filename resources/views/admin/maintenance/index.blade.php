<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Maintenance Mode') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Status Card -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                Maintenance Mode Status
                            </h3>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                @if($enabled)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                        <svg class="mr-1.5 h-2 w-2 animate-pulse" fill="currentColor" viewBox="0 0 8 8">
                                            <circle cx="4" cy="4" r="3" />
                                        </svg>
                                        Active
                                    </span>
                                    <span class="ml-2 text-xs">since {{ $enabled_at }}</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                        <svg class="mr-1.5 h-2 w-2" fill="currentColor" viewBox="0 0 8 8">
                                            <circle cx="4" cy="4" r="3" />
                                        </svg>
                                        Disabled
                                    </span>
                                @endif
                            </p>
                        </div>
                        <div>
                            @if($enabled)
                                <button onclick="disableMaintenance()" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                    Disable Maintenance Mode
                                </button>
                            @else
                                <button onclick="showEnableModal()" class="inline-flex items-center px-4 py-2 bg-yellow-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-700 focus:bg-yellow-700 active:bg-yellow-900 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                    Enable Maintenance Mode
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            @if($enabled)
                <!-- Current Settings -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                            Current Settings
                        </h3>

                        <form id="updateForm" class="space-y-6">
                            @csrf

                            <!-- Message -->
                            <div>
                                <label for="message" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Maintenance Message
                                </label>
                                <textarea id="message" name="message" rows="3" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ $message }}</textarea>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    This message will be displayed to visitors during maintenance.
                                </p>
                            </div>

                            <!-- Retry After -->
                            <div>
                                <label for="retry_after" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Retry After (seconds)
                                </label>
                                <input type="number" id="retry_after" name="retry_after" min="1" max="3600" value="{{ $retry_after }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    Suggested time for visitors to check back (used in HTTP Retry-After header).
                                </p>
                            </div>

                            <!-- Allowed IPs -->
                            <div>
                                <label for="allowed_ips" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Allowed IP Addresses
                                </label>
                                <textarea id="allowed_ips" name="allowed_ips" rows="3" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Enter one IP address per line">{{ implode("\n", $allowed_ips) }}</textarea>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    IP addresses that can bypass maintenance mode. Enter one per line.
                                </p>
                            </div>

                            <div class="flex items-center justify-end">
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                    Update Settings
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Bypass Information -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                            Bypass Access
                        </h3>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Secret Bypass URL
                                </label>
                                <div class="flex items-center space-x-2">
                                    <input type="text" id="bypass_url" value="{{ url('/' . $secret) }}" readonly class="flex-1 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <button onclick="copyBypassUrl()" class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        Copy
                                    </button>
                                    <button onclick="regenerateSecret()" class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        Regenerate
                                    </button>
                                </div>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    Share this URL with team members who need access during maintenance.
                                </p>
                            </div>

                            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-md p-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                                            Bypass Methods
                                        </h3>
                                        <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                                            <ul class="list-disc list-inside space-y-1">
                                                <li>Use the secret bypass URL above</li>
                                                <li>Add your IP address to the allowed list</li>
                                                <li>Administrators are automatically granted access</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Enable Maintenance Modal -->
    <div id="enableModal" class="hidden fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="hideEnableModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form id="enableForm">
                    @csrf
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 dark:bg-yellow-900 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-yellow-600 dark:text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100" id="modal-title">
                                    Enable Maintenance Mode
                                </h3>
                                <div class="mt-4 space-y-4">
                                    <div>
                                        <label for="enable_message" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Message
                                        </label>
                                        <textarea id="enable_message" name="message" rows="3" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="We are currently performing maintenance..."></textarea>
                                    </div>
                                    <div>
                                        <label for="enable_retry_after" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Retry After (seconds)
                                        </label>
                                        <input type="number" id="enable_retry_after" name="retry_after" min="1" max="3600" value="60" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-yellow-600 text-base font-medium text-white hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Enable
                        </button>
                        <button type="button" onclick="hideEnableModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function showEnableModal() {
            document.getElementById('enableModal').classList.remove('hidden');
        }

        function hideEnableModal() {
            document.getElementById('enableModal').classList.add('hidden');
        }

        document.getElementById('enableForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            const data = {
                message: formData.get('message') || 'We are currently performing maintenance. Please check back soon.',
                retry_after: parseInt(formData.get('retry_after')) || 60,
            };

            try {
                const response = await fetch('{{ route('admin.maintenance.enable') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    window.location.reload();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                alert('Failed to enable maintenance mode: ' + error.message);
            }
        });

        document.getElementById('updateForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            const allowedIps = formData.get('allowed_ips')
                .split('\n')
                .map(ip => ip.trim())
                .filter(ip => ip.length > 0);

            const data = {
                message: formData.get('message'),
                retry_after: parseInt(formData.get('retry_after')),
                allowed_ips: allowedIps,
            };

            try {
                const response = await fetch('{{ route('admin.maintenance.update') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    alert('Settings updated successfully!');
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                alert('Failed to update settings: ' + error.message);
            }
        });

        async function disableMaintenance() {
            if (!confirm('Are you sure you want to disable maintenance mode?')) {
                return;
            }

            try {
                const response = await fetch('{{ route('admin.maintenance.disable') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const result = await response.json();

                if (result.success) {
                    window.location.reload();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                alert('Failed to disable maintenance mode: ' + error.message);
            }
        }

        function copyBypassUrl() {
            const input = document.getElementById('bypass_url');
            input.select();
            document.execCommand('copy');
            alert('Bypass URL copied to clipboard!');
        }

        async function regenerateSecret() {
            if (!confirm('Are you sure you want to regenerate the secret token? The old bypass URL will no longer work.')) {
                return;
            }

            try {
                const response = await fetch('{{ route('admin.maintenance.regenerate-secret') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const result = await response.json();

                if (result.success) {
                    document.getElementById('bypass_url').value = result.data.bypass_url;
                    alert('Secret token regenerated successfully!');
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                alert('Failed to regenerate secret: ' + error.message);
            }
        }
    </script>
    @endpush
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
            {{ __('Privacy Policy') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="prose dark:prose-invert max-w-none">
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                            Last updated: {{ now()->format('F d, Y') }}
                        </p>

                        <h3 class="text-lg font-semibold mb-3">1. Introduction</h3>
                        <p class="mb-4">
                            Welcome to {{ config('app.name') }}. We respect your privacy and are committed to protecting your personal data. 
                            This privacy policy will inform you about how we look after your personal data and tell you about your privacy rights.
                        </p>

                        <h3 class="text-lg font-semibold mb-3">2. Data We Collect</h3>
                        <p class="mb-2">We may collect, use, store and transfer different kinds of personal data about you:</p>
                        <ul class="list-disc list-inside mb-4 space-y-1">
                            <li><strong>Identity Data:</strong> name, username, email address</li>
                            <li><strong>Profile Data:</strong> bio, avatar, preferences</li>
                            <li><strong>Content Data:</strong> posts, comments, reactions, bookmarks</li>
                            <li><strong>Technical Data:</strong> IP address, browser type, device information</li>
                            <li><strong>Usage Data:</strong> how you use our website and services</li>
                        </ul>

                        <h3 class="text-lg font-semibold mb-3">3. How We Use Your Data</h3>
                        <p class="mb-2">We use your personal data for the following purposes:</p>
                        <ul class="list-disc list-inside mb-4 space-y-1">
                            <li>To provide and maintain our service</li>
                            <li>To notify you about changes to our service</li>
                            <li>To provide customer support</li>
                            <li>To gather analysis or valuable information to improve our service</li>
                            <li>To monitor the usage of our service</li>
                            <li>To detect, prevent and address technical issues</li>
                        </ul>

                        <h3 class="text-lg font-semibold mb-3">4. Cookies</h3>
                        <p class="mb-4">
                            We use cookies and similar tracking technologies to track activity on our service. 
                            You can instruct your browser to refuse all cookies or to indicate when a cookie is being sent. 
                            However, if you do not accept cookies, you may not be able to use some portions of our service.
                        </p>

                        <h3 class="text-lg font-semibold mb-3">5. Your Rights</h3>
                        <p class="mb-2">Under GDPR, you have the following rights:</p>
                        <ul class="list-disc list-inside mb-4 space-y-1">
                            <li><strong>Right to Access:</strong> You can request a copy of your personal data</li>
                            <li><strong>Right to Rectification:</strong> You can request correction of inaccurate data</li>
                            <li><strong>Right to Erasure:</strong> You can request deletion of your personal data</li>
                            <li><strong>Right to Restrict Processing:</strong> You can request restriction of processing</li>
                            <li><strong>Right to Data Portability:</strong> You can request transfer of your data</li>
                            <li><strong>Right to Object:</strong> You can object to processing of your data</li>
                            <li><strong>Right to Withdraw Consent:</strong> You can withdraw consent at any time</li>
                        </ul>

                        <h3 class="text-lg font-semibold mb-3">6. Data Retention</h3>
                        <p class="mb-4">
                            We will retain your personal data only for as long as necessary for the purposes set out in this privacy policy. 
                            We will retain and use your data to the extent necessary to comply with our legal obligations, 
                            resolve disputes, and enforce our policies.
                        </p>

                        <h3 class="text-lg font-semibold mb-3">7. Data Security</h3>
                        <p class="mb-4">
                            The security of your data is important to us. We use industry-standard security measures to protect your personal data. 
                            However, no method of transmission over the Internet or electronic storage is 100% secure.
                        </p>

                        <h3 class="text-lg font-semibold mb-3">8. Your Choices</h3>
                        <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-lg mb-4">
                            <p class="mb-3">You can exercise your rights by:</p>
                            <div class="space-y-2">
                                @auth
                                    <div>
                                        <a href="{{ route('gdpr.export-data') }}" class="text-blue-600 dark:text-blue-400 hover:underline">
                                            Download your data (JSON format)
                                        </a>
                                    </div>
                                    <div>
                                        <a href="{{ route('gdpr.show-delete-account') }}" class="text-red-600 dark:text-red-400 hover:underline">
                                            Delete your account
                                        </a>
                                    </div>
                                    <div>
                                        <form method="POST" action="{{ route('gdpr.withdraw-consent') }}" class="inline">
                                            @csrf
                                            <button type="submit" class="text-blue-600 dark:text-blue-400 hover:underline">
                                                Withdraw cookie consent
                                            </button>
                                        </form>
                                    </div>
                                @else
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        Please <a href="{{ route('login') }}" class="text-blue-600 dark:text-blue-400 hover:underline">log in</a> 
                                        to manage your data and privacy settings.
                                    </p>
                                @endauth
                            </div>
                        </div>

                        <h3 class="text-lg font-semibold mb-3">9. Contact Us</h3>
                        <p class="mb-4">
                            If you have any questions about this privacy policy or our privacy practices, please contact us at:
                            <a href="mailto:{{ config('mail.from.address') }}" class="text-blue-600 dark:text-blue-400 hover:underline">
                                {{ config('mail.from.address') }}
                            </a>
                        </p>

                        <h3 class="text-lg font-semibold mb-3">10. Changes to This Policy</h3>
                        <p>
                            We may update our privacy policy from time to time. We will notify you of any changes by posting the new 
                            privacy policy on this page and updating the "Last updated" date.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
            {{ __('Privacy Policy') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                <div class="prose prose-sm max-w-none p-6 text-gray-900 dark:prose-invert dark:text-gray-100 sm:prose lg:prose-lg">
                    <h1>Privacy Policy</h1>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Last updated: {{ now()->format('F d, Y') }}</p>

                    <h2>1. Introduction</h2>
                    <p>
                        Welcome to our technology news platform. We respect your privacy and are committed to protecting your personal data. 
                        This privacy policy will inform you about how we look after your personal data and tell you about your privacy rights.
                    </p>

                    <h2>2. Data We Collect</h2>
                    <p>We may collect, use, store and transfer different kinds of personal data about you:</p>
                    <ul>
                        <li><strong>Identity Data:</strong> name, username, email address</li>
                        <li><strong>Profile Data:</strong> bio, avatar, social media links, preferences</li>
                        <li><strong>Content Data:</strong> articles, comments, bookmarks you create</li>
                        <li><strong>Technical Data:</strong> IP address, browser type, device information</li>
                        <li><strong>Usage Data:</strong> how you use our website and services</li>
                        <li><strong>Marketing Data:</strong> your preferences for receiving communications</li>
                    </ul>

                    <h2>3. How We Use Your Data</h2>
                    <p>We use your personal data for the following purposes:</p>
                    <ul>
                        <li>To provide and maintain our service</li>
                        <li>To notify you about changes to our service</li>
                        <li>To provide customer support</li>
                        <li>To gather analysis or valuable information to improve our service</li>
                        <li>To monitor the usage of our service</li>
                        <li>To detect, prevent and address technical issues</li>
                        <li>To send you newsletters (if you opted in)</li>
                    </ul>

                    <h2>4. Legal Basis for Processing</h2>
                    <p>We process your personal data based on:</p>
                    <ul>
                        <li><strong>Consent:</strong> You have given clear consent for us to process your personal data</li>
                        <li><strong>Contract:</strong> Processing is necessary for a contract you have with us</li>
                        <li><strong>Legal obligation:</strong> Processing is necessary for us to comply with the law</li>
                        <li><strong>Legitimate interests:</strong> Processing is necessary for our legitimate interests</li>
                    </ul>

                    <h2>5. Data Security</h2>
                    <p>
                        We have implemented appropriate security measures to prevent your personal data from being accidentally lost, 
                        used or accessed in an unauthorized way. We use encryption for sensitive data and secure connections (HTTPS) 
                        for all data transmission.
                    </p>

                    <h2>6. Data Retention</h2>
                    <p>
                        We will only retain your personal data for as long as necessary to fulfill the purposes we collected it for. 
                        When you delete your account, we will anonymize your data while keeping your published content available.
                    </p>

                    <h2>7. Your Rights</h2>
                    <p>Under GDPR, you have the following rights:</p>
                    <ul>
                        <li><strong>Right to access:</strong> You can request copies of your personal data</li>
                        <li><strong>Right to rectification:</strong> You can request correction of inaccurate data</li>
                        <li><strong>Right to erasure:</strong> You can request deletion of your personal data</li>
                        <li><strong>Right to restrict processing:</strong> You can request restriction of processing</li>
                        <li><strong>Right to data portability:</strong> You can request transfer of your data</li>
                        <li><strong>Right to object:</strong> You can object to processing of your personal data</li>
                    </ul>

                    <h2>8. Cookies</h2>
                    <p>
                        We use cookies and similar tracking technologies to track activity on our service. You can instruct your 
                        browser to refuse all cookies or to indicate when a cookie is being sent. However, if you do not accept 
                        cookies, you may not be able to use some portions of our service.
                    </p>

                    <h2>9. Third-Party Services</h2>
                    <p>We may employ third-party companies and individuals to facilitate our service:</p>
                    <ul>
                        <li>Analytics services (to analyze how our service is used)</li>
                        <li>Email service providers (to send newsletters and notifications)</li>
                        <li>Cloud storage providers (to store your uploaded content)</li>
                    </ul>

                    <h2>10. Children's Privacy</h2>
                    <p>
                        Our service is not intended for children under 13 years of age. We do not knowingly collect personal 
                        information from children under 13.
                    </p>

                    <h2>11. Changes to This Policy</h2>
                    <p>
                        We may update our Privacy Policy from time to time. We will notify you of any changes by posting the 
                        new Privacy Policy on this page and updating the "Last updated" date.
                    </p>

                    <h2>12. Contact Us</h2>
                    <p>
                        If you have any questions about this Privacy Policy, please contact us at: 
                        <a href="mailto:privacy@example.com" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                            privacy@example.com
                        </a>
                    </p>

                    <h2>13. Data Export and Deletion</h2>
                    <p>
                        You can export all your data or delete your account at any time from your 
                        <a href="{{ route('gdpr.show-delete-account') }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                            account settings
                        </a>.
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

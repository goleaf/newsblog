@extends('admin.layouts.app')

@section('title', 'Settings')

@section('content')
<div class="px-4 sm:px-0">
    <h1 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">Settings</h1>

    <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- General Settings -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">General</h3>
            <div class="grid grid-cols-1 gap-4">
                <div>
                    <label for="general_site_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Site Name</label>
                    <input type="text" name="general_site_name" id="general_site_name" value="{{ $groups['general']['site_name'] ?? '' }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
                <div>
                    <label for="general_site_description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Site Description</label>
                    <textarea name="general_site_description" id="general_site_description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">{{ $groups['general']['site_description'] ?? '' }}</textarea>
                </div>
            </div>
        </div>

        <!-- SEO Settings -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">SEO</h3>
            <div class="grid grid-cols-1 gap-4">
                <div>
                    <label for="seo_meta_title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Default Meta Title</label>
                    <input type="text" name="seo_meta_title" id="seo_meta_title" value="{{ $groups['seo']['meta_title'] ?? '' }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
                <div>
                    <label for="seo_meta_description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Default Meta Description</label>
                    <textarea name="seo_meta_description" id="seo_meta_description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">{{ $groups['seo']['meta_description'] ?? '' }}</textarea>
                </div>
            </div>
        </div>

        <!-- Social Settings -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Social Media</h3>
            <div class="grid grid-cols-1 gap-4">
                <div>
                    <label for="social_facebook_url" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Facebook URL</label>
                    <input type="url" name="social_facebook_url" id="social_facebook_url" value="{{ $groups['social']['facebook_url'] ?? '' }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
                <div>
                    <label for="social_twitter_url" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Twitter URL</label>
                    <input type="url" name="social_twitter_url" id="social_twitter_url" value="{{ $groups['social']['twitter_url'] ?? '' }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                Save Settings
            </button>
        </div>
    </form>
</div>
@endsection


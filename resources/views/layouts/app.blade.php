<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'TechNewsHub'))</title>
    <meta name="description" content="@yield('description', config('app.description', 'TechNewsHub - Your source for technology news'))">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 dark:bg-gray-900">
    <!-- Navigation -->
    <nav class="bg-white dark:bg-gray-800 shadow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <a href="{{ route('home') }}" class="text-xl font-bold text-gray-900 dark:text-white">
                            {{ config('app.name', 'TechNewsHub') }}
                        </a>
                    </div>
                    <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                        <a href="{{ route('home') }}" class="border-indigo-500 text-gray-900 dark:text-white inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Home
                        </a>
                        @foreach(\App\Models\Category::active()->parents()->ordered()->take(6)->get() as $category)
                            <a href="{{ route('category.show', $category->slug) }}" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                {{ $category->name }}
                            </a>
                        @endforeach
                    </div>
                </div>
                <div class="flex items-center">
                    <form method="GET" action="{{ route('search') }}" class="flex">
                        <input type="text" name="q" placeholder="Search..." class="rounded-l-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <button type="submit" class="rounded-r-md bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700">Search</button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main>
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-white dark:bg-gray-800 mt-12">
        <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-sm font-semibold text-gray-400 tracking-wider uppercase dark:text-gray-500">About</h3>
                    <p class="mt-4 text-base text-gray-500 dark:text-gray-400">{{ config('app.name', 'TechNewsHub') }} - Your source for technology news and insights.</p>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-400 tracking-wider uppercase dark:text-gray-500">Categories</h3>
                    <ul class="mt-4 space-y-4">
                        @foreach(\App\Models\Category::active()->parents()->ordered()->take(5)->get() as $category)
                            <li><a href="{{ route('category.show', $category->slug) }}" class="text-base text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">{{ $category->name }}</a></li>
                        @endforeach
                    </ul>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-400 tracking-wider uppercase dark:text-gray-500">Quick Links</h3>
                    <ul class="mt-4 space-y-4">
                        <li><a href="{{ route('home') }}" class="text-base text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">Home</a></li>
                        <li><a href="{{ route('search') }}" class="text-base text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">Search</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-400 tracking-wider uppercase dark:text-gray-500">Newsletter</h3>
                    <p class="mt-4 text-base text-gray-500 dark:text-gray-400">Subscribe to our newsletter for the latest updates.</p>
                    <form method="POST" action="#" class="mt-4">
                        @csrf
                        <input type="email" name="email" placeholder="Your email" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <button type="submit" class="mt-2 w-full rounded-md bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700">Subscribe</button>
                    </form>
                </div>
            </div>
            <div class="mt-8 border-t border-gray-200 dark:border-gray-700 pt-8">
                <p class="text-base text-gray-400 text-center">&copy; {{ date('Y') }} {{ config('app.name', 'TechNewsHub') }}. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>

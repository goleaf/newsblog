# Frontend Development Guide

## Overview

TechNewsHub uses a modern frontend stack with Blade templates, Tailwind CSS, and Alpine.js. This guide covers frontend development practices, component structure, and customization.

## Technology Stack

### Core Technologies

- **Template Engine:** Blade (Laravel's templating engine)
- **CSS Framework:** Tailwind CSS 3.x
- **JavaScript Framework:** Alpine.js 3.x
- **Build Tool:** Vite 7.x
- **Rich Text Editor:** TinyMCE 8.x
- **Date Picker:** Flatpickr 4.x
- **Icons:** Heroicons

### Build Process

```bash
# Development (with hot reload)
npm run dev

# Production build
npm run build

# Watch for changes
npm run watch
```

## Project Structure

```
resources/
├── css/
│   └── app.css                 # Main stylesheet
├── js/
│   ├── app.js                  # Main JavaScript entry
│   ├── bootstrap.js            # Bootstrap configuration
│   └── search-autocomplete.js  # Search autocomplete
└── views/
    ├── layouts/                # Layout templates
    ├── components/             # Reusable components
    ├── posts/                  # Post views
    ├── admin/                  # Admin panel views
    └── ...                     # Other views
```

## Blade Templates

### Layout Structure

#### Main Layout (`layouts/app.blade.php`)

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>{{ config('app.name', 'TechNewsHub') }}</title>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    @include('layouts.navigation')
    
    <main>
        {{ $slot }}
    </main>
    
    @include('layouts.footer')
</body>
</html>
```

#### Guest Layout (`layouts/guest.blade.php`)

Used for authentication pages (login, register, etc.)

### Creating Views

#### Basic View

```blade
<x-app-layout>
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-4">{{ $title }}</h1>
        
        <div class="prose max-w-none">
            {{ $content }}
        </div>
    </div>
</x-app-layout>
```

#### View with Sections

```blade
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">{{ $title }}</h2>
    </x-slot>
    
    <div class="py-12">
        <!-- Content here -->
    </div>
</x-app-layout>
```

### Blade Components

#### Creating a Component

```bash
php artisan make:component PostCard
```

This creates:
- `app/View/Components/PostCard.php` (Component class)
- `resources/views/components/post-card.blade.php` (Template)

#### Component Class

```php
<?php

namespace App\View\Components;

use App\Models\Post;
use Illuminate\View\Component;

class PostCard extends Component
{
    public function __construct(
        public Post $post
    ) {}

    public function render()
    {
        return view('components.post-card');
    }
}
```

#### Component Template

```blade
<article class="bg-white rounded-lg shadow-md overflow-hidden">
    @if($post->featured_image)
        <img src="{{ $post->featured_image }}" 
             alt="{{ $post->title }}" 
             class="w-full h-48 object-cover"
             loading="lazy">
    @endif
    
    <div class="p-6">
        <h3 class="text-xl font-bold mb-2">
            <a href="{{ route('posts.show', $post->slug) }}" 
               class="hover:text-blue-600">
                {{ $post->title }}
            </a>
        </h3>
        
        <p class="text-gray-600 mb-4">{{ $post->excerpt }}</p>
        
        <div class="flex items-center justify-between text-sm text-gray-500">
            <span>{{ $post->author->name }}</span>
            <span>{{ $post->published_at->diffForHumans() }}</span>
        </div>
    </div>
</article>
```

#### Using the Component

```blade
<x-post-card :post="$post" />
```

### Blade Directives

#### Common Directives

```blade
{{-- Variables --}}
{{ $variable }}
{!! $htmlVariable !!}

{{-- Conditionals --}}
@if($condition)
    <!-- Content -->
@elseif($otherCondition)
    <!-- Content -->
@else
    <!-- Content -->
@endif

@unless($condition)
    <!-- Content -->
@endunless

@isset($variable)
    <!-- Content -->
@endisset

@empty($variable)
    <!-- Content -->
@endempty

{{-- Loops --}}
@foreach($items as $item)
    {{ $item }}
@endforeach

@forelse($items as $item)
    {{ $item }}
@empty
    <p>No items found</p>
@endforelse

@for($i = 0; $i < 10; $i++)
    {{ $i }}
@endfor

@while($condition)
    <!-- Content -->
@endwhile

{{-- Authentication --}}
@auth
    <!-- Authenticated content -->
@endauth

@guest
    <!-- Guest content -->
@endguest

{{-- Authorization --}}
@can('update', $post)
    <!-- Authorized content -->
@endcan

@cannot('delete', $post)
    <!-- Not authorized content -->
@endcannot

{{-- Including Views --}}
@include('partials.header')
@include('partials.post', ['post' => $post])

{{-- Stacks --}}
@push('scripts')
    <script src="/js/custom.js"></script>
@endpush

{{-- In layout --}}
@stack('scripts')
```

## Tailwind CSS

### Configuration

Tailwind is configured in `tailwind.config.js`:

```javascript
export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    theme: {
        extend: {
            colors: {
                primary: {
                    50: '#eff6ff',
                    // ... color scale
                    900: '#1e3a8a',
                },
            },
            fontFamily: {
                sans: ['Inter', 'sans-serif'],
            },
        },
    },
    plugins: [
        require('@tailwindcss/forms'),
        require('@tailwindcss/typography'),
    ],
}
```

### Common Patterns

#### Container

```blade
<div class="container mx-auto px-4">
    <!-- Content -->
</div>
```

#### Grid Layout

```blade
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @foreach($posts as $post)
        <x-post-card :post="$post" />
    @endforeach
</div>
```

#### Responsive Design

```blade
<div class="text-sm md:text-base lg:text-lg">
    Responsive text
</div>

<div class="hidden md:block">
    Visible on medium screens and up
</div>

<div class="block md:hidden">
    Visible only on small screens
</div>
```

#### Flexbox

```blade
<div class="flex items-center justify-between">
    <div>Left content</div>
    <div>Right content</div>
</div>

<div class="flex flex-col gap-4">
    <div>Item 1</div>
    <div>Item 2</div>
    <div>Item 3</div>
</div>
```

#### Cards

```blade
<div class="bg-white rounded-lg shadow-md p-6">
    <h3 class="text-xl font-bold mb-2">Card Title</h3>
    <p class="text-gray-600">Card content</p>
</div>
```

#### Buttons

```blade
{{-- Primary Button --}}
<button class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
    Primary Button
</button>

{{-- Secondary Button --}}
<button class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded">
    Secondary Button
</button>

{{-- Danger Button --}}
<button class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
    Delete
</button>
```

#### Forms

```blade
<form method="POST" action="{{ route('posts.store') }}" class="space-y-4">
    @csrf
    
    <div>
        <label for="title" class="block text-sm font-medium text-gray-700">
            Title
        </label>
        <input type="text" 
               id="title" 
               name="title" 
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
               value="{{ old('title') }}"
               required>
        @error('title')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>
    
    <div>
        <button type="submit" 
                class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            Submit
        </button>
    </div>
</form>
```

### Dark Mode (Planned)

```blade
<div class="bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
    Content that adapts to dark mode
</div>
```

## Alpine.js

### Basic Usage

#### Data Binding

```blade
<div x-data="{ open: false }">
    <button @click="open = !open">Toggle</button>
    
    <div x-show="open" x-transition>
        Content that can be toggled
    </div>
</div>
```

#### Dropdown Menu

```blade
<div x-data="{ open: false }" @click.away="open = false">
    <button @click="open = !open">
        Menu
    </button>
    
    <div x-show="open" 
         x-transition
         class="absolute mt-2 w-48 bg-white rounded-md shadow-lg">
        <a href="#" class="block px-4 py-2 hover:bg-gray-100">Item 1</a>
        <a href="#" class="block px-4 py-2 hover:bg-gray-100">Item 2</a>
    </div>
</div>
```

#### Modal

```blade
<div x-data="{ open: false }">
    <button @click="open = true">Open Modal</button>
    
    <div x-show="open" 
         x-transition
         class="fixed inset-0 z-50 overflow-y-auto"
         @keydown.escape.window="open = false">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75" 
                 @click="open = false"></div>
            
            <div class="relative bg-white rounded-lg p-8 max-w-md w-full">
                <h3 class="text-lg font-bold mb-4">Modal Title</h3>
                <p class="mb-4">Modal content</p>
                <button @click="open = false" 
                        class="bg-blue-600 text-white px-4 py-2 rounded">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
```

#### Tabs

```blade
<div x-data="{ tab: 'tab1' }">
    <div class="flex border-b">
        <button @click="tab = 'tab1'" 
                :class="{ 'border-blue-600 text-blue-600': tab === 'tab1' }"
                class="px-4 py-2 border-b-2">
            Tab 1
        </button>
        <button @click="tab = 'tab2'" 
                :class="{ 'border-blue-600 text-blue-600': tab === 'tab2' }"
                class="px-4 py-2 border-b-2">
            Tab 2
        </button>
    </div>
    
    <div x-show="tab === 'tab1'" class="p-4">
        Tab 1 content
    </div>
    
    <div x-show="tab === 'tab2'" class="p-4">
        Tab 2 content
    </div>
</div>
```

### Search Autocomplete

```blade
<div x-data="searchAutocomplete()" x-init="init()">
    <input type="text" 
           x-model="query"
           @input.debounce.300ms="search()"
           placeholder="Search..."
           class="w-full px-4 py-2 border rounded">
    
    <div x-show="results.length > 0" 
         class="absolute mt-1 w-full bg-white rounded-md shadow-lg">
        <template x-for="result in results" :key="result.id">
            <a :href="result.url" 
               class="block px-4 py-2 hover:bg-gray-100"
               x-text="result.title"></a>
        </template>
    </div>
</div>

<script>
function searchAutocomplete() {
    return {
        query: '',
        results: [],
        
        async search() {
            if (this.query.length < 3) {
                this.results = [];
                return;
            }
            
            const response = await fetch(`/api/search/suggestions?q=${this.query}`);
            this.results = await response.json();
        }
    }
}
</script>
```

## JavaScript

### Main Entry Point (`resources/js/app.js`)

```javascript
import './bootstrap';
import Alpine from 'alpinejs';

// Initialize Alpine
window.Alpine = Alpine;
Alpine.start();

// Global functions
window.confirmDelete = function(message) {
    return confirm(message || 'Are you sure you want to delete this?');
};
```

### AJAX Requests

```javascript
// Using Fetch API
async function submitForm(formData) {
    try {
        const response = await fetch('/api/endpoint', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(formData)
        });
        
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Error:', error);
        throw error;
    }
}
```

### Form Validation

```javascript
function validateForm(form) {
    const title = form.querySelector('#title').value;
    const content = form.querySelector('#content').value;
    
    if (!title || title.length < 3) {
        alert('Title must be at least 3 characters');
        return false;
    }
    
    if (!content || content.length < 10) {
        alert('Content must be at least 10 characters');
        return false;
    }
    
    return true;
}
```

## Rich Text Editor (TinyMCE)

### Basic Setup

```blade
<textarea id="content" name="content">{{ old('content', $post->content ?? '') }}</textarea>

@push('scripts')
<script src="https://cdn.tiny.cloud/1/YOUR_API_KEY/tinymce/6/tinymce.min.js"></script>
<script>
tinymce.init({
    selector: '#content',
    plugins: 'link image code lists',
    toolbar: 'undo redo | formatselect | bold italic | alignleft aligncenter alignright | bullist numlist | link image | code',
    height: 500,
    menubar: false,
    branding: false
});
</script>
@endpush
```

## Performance Optimization

### Image Lazy Loading

```blade
<img src="{{ $post->featured_image }}" 
     alt="{{ $post->title }}" 
     loading="lazy">
```

### Asset Optimization

```javascript
// vite.config.js
export default {
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    vendor: ['alpinejs'],
                    editor: ['tinymce']
                }
            }
        }
    }
}
```

### Code Splitting

```javascript
// Dynamic imports
const module = await import('./heavy-module.js');
```

## Accessibility

### ARIA Labels

```blade
<button aria-label="Close modal" @click="open = false">
    <svg><!-- Close icon --></svg>
</button>
```

### Keyboard Navigation

```blade
<div @keydown.escape="close()" 
     @keydown.enter="submit()"
     tabindex="0">
    <!-- Content -->
</div>
```

### Focus Management

```blade
<input type="text" 
       x-ref="input"
       x-init="$refs.input.focus()">
```

## Testing

### Browser Testing

```bash
# Run browser tests
php artisan dusk
```

### JavaScript Testing

```bash
# Run JavaScript tests
npm test
```

## Best Practices

### Component Organization

1. Keep components small and focused
2. Use props for data passing
3. Emit events for parent communication
4. Document component usage

### CSS Organization

1. Use Tailwind utility classes
2. Extract repeated patterns to components
3. Use @apply for complex patterns
4. Keep custom CSS minimal

### JavaScript Organization

1. Keep Alpine components simple
2. Extract complex logic to separate files
3. Use async/await for promises
4. Handle errors gracefully

### Performance

1. Lazy load images
2. Minimize JavaScript bundle size
3. Use code splitting
4. Optimize asset delivery

## Troubleshooting

### Common Issues

**Issue: Styles not updating**
```bash
npm run build
php artisan view:clear
```

**Issue: Alpine not working**
- Check console for errors
- Verify Alpine is imported
- Check x-data syntax

**Issue: Vite not hot reloading**
```bash
# Restart dev server
npm run dev
```

---

**Last Updated:** November 12, 2025  
**Version:** 0.3.0-dev

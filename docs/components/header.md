# Header Component Documentation

## Overview

The Header component is a responsive, sticky navigation bar with scroll behavior, dark mode support, and mobile menu integration. It provides the main navigation structure for the TechNewsHub application.

## Location

`resources/views/components/layout/header.blade.php`

## Features

- ✅ Sticky positioning with auto-hide on scroll down
- ✅ Transparent/solid background based on scroll position
- ✅ Responsive design (mobile, tablet, desktop)
- ✅ Dark mode support
- ✅ Search integration
- ✅ User authentication menu
- ✅ Mobile menu toggle
- ✅ Accessibility compliant (ARIA labels, keyboard navigation)
- ✅ Alpine.js powered interactions

## Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `sticky` | boolean | `true` | Whether the header should stick to the top on scroll |
| `transparent` | boolean | `false` | Whether the header starts transparent (useful for hero sections) |

## Usage Examples

### Basic Usage (Default)

```blade
<x-layout.header />
```

This renders a sticky header with solid background.

### Transparent Header (Hero Pages)

```blade
<x-layout.header :transparent="true" />
```

Useful for landing pages with hero sections. The header becomes solid when scrolled.

### Non-Sticky Header

```blade
<x-layout.header :sticky="false" />
```

Header stays at the top but doesn't follow scroll.

### Combined Props

```blade
<x-layout.header :sticky="true" :transparent="true" />
```

## Component Structure

```
Header
├── Logo & Site Name
├── Desktop Navigation
│   ├── Home
│   ├── Categories
│   ├── Series
│   └── Articles
├── Actions
│   ├── Search Button
│   ├── Dark Mode Toggle
│   ├── User Menu (Desktop)
│   └── Mobile Menu Button
└── Mobile Menu Component
```

## Behavior

### Scroll Behavior

The header implements smart scroll behavior:

1. **Scrolled < 50px**: Shows transparent background (if `transparent="true"`)
2. **Scrolled > 50px**: Shows solid background with shadow
3. **Scrolling Down (> 100px)**: Header hides (slides up)
4. **Scrolling Up**: Header shows (slides down)

### State Management (Alpine.js)

```javascript
{
    scrolled: false,        // True when scrolled > 50px
    hidden: false,          // True when scrolling down
    lastScroll: 0,          // Last scroll position
    mobileMenuOpen: false   // Mobile menu state
}
```

## Styling

### Responsive Breakpoints

- **Mobile** (< 1024px): Shows mobile menu button, hides desktop nav
- **Desktop** (≥ 1024px): Shows desktop nav, hides mobile menu button

### Dark Mode

All elements support dark mode with `dark:` prefixes:

```html
<!-- Example -->
<a class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400">
    Link
</a>
```

### Transitions

- Header slide: `transition-all duration-300 ease-in-out`
- Link colors: `transition-colors`

## Accessibility

### ARIA Attributes

- `role="banner"` - Identifies header landmark
- `aria-label="Main navigation"` - Labels navigation region
- `aria-current="page"` - Marks active page link
- `aria-label` on buttons - Describes button actions
- `aria-expanded` on mobile menu - Indicates menu state

### Keyboard Navigation

- All interactive elements are keyboard accessible
- Tab order follows visual order
- Focus states visible on all interactive elements

### Screen Reader Support

- Semantic HTML structure
- Descriptive labels on all controls
- Current page indication

## Dependencies

### Components

- `<x-application-logo />` - Site logo
- `<x-dark-mode-toggle />` - Theme switcher
- `<x-navigation.user-menu />` - User dropdown
- `<x-layout.mobile-menu />` - Mobile navigation

### JavaScript

- Alpine.js v3 - For reactive behavior
- Event: `open-search` - Dispatched when search button clicked

## Integration Example

### In Layout File

```blade
<!-- resources/views/layouts/app.blade.php -->
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <!-- ... -->
</head>
<body>
    <x-layout.header />
    
    <main>
        {{ $slot }}
    </main>
    
    <x-layout.footer />
</body>
</html>
```

### With Hero Section

```blade
<!-- Landing page with transparent header -->
<x-guest-layout>
    <x-layout.header :transparent="true" :sticky="true" />
    
    <section class="h-screen bg-gradient-to-r from-blue-500 to-purple-600">
        <!-- Hero content -->
    </section>
    
    <section class="py-20">
        <!-- Regular content -->
    </section>
</x-guest-layout>
```

## Customization

### Modifying Navigation Links

Edit the desktop navigation section:

```blade
<nav class="hidden lg:flex items-center space-x-8">
    <a href="{{ route('home') }}">Home</a>
    <a href="{{ route('blog') }}">Blog</a>
    <!-- Add more links -->
</nav>
```

### Changing Scroll Thresholds

Modify the Alpine.js initialization:

```javascript
x-init="
    window.addEventListener('scroll', () => {
        const currentScroll = window.pageYOffset;
        scrolled = currentScroll > 100; // Change from 50
        
        if (currentScroll > lastScroll && currentScroll > 200) { // Change from 100
            hidden = true;
        } else {
            hidden = false;
        }
        lastScroll = currentScroll;
    });
"
```

### Styling Adjustments

Common customizations:

```blade
{{-- Change header height --}}
<div class="flex items-center justify-between h-20"> {{-- Changed from h-16 --}}

{{-- Change max width --}}
<div class="max-w-full mx-auto px-4"> {{-- Changed from max-w-7xl --}}

{{-- Adjust spacing --}}
<nav class="hidden lg:flex items-center space-x-12"> {{-- Changed from space-x-8 --}}
```

## Testing

Run the component tests:

```bash
php artisan test --filter=HeaderComponentTest
```

### Test Coverage

- ✅ Default rendering
- ✅ Props handling
- ✅ Navigation links
- ✅ Search button
- ✅ Dark mode toggle
- ✅ User menu (authenticated)
- ✅ Mobile menu button
- ✅ ARIA attributes
- ✅ Responsive classes
- ✅ Dark mode classes
- ✅ Scroll behavior setup

## Browser Compatibility

- ✅ Chrome/Edge (latest)
- ✅ Firefox (latest)
- ✅ Safari (latest)
- ✅ Mobile browsers (iOS Safari, Chrome Mobile)

## Performance Considerations

1. **Scroll Event**: Uses passive event listener (Alpine.js default)
2. **Transitions**: GPU-accelerated transforms
3. **Images**: Logo should be optimized SVG
4. **CSS**: Tailwind purges unused classes in production

## Common Issues

### Header Not Sticky

**Problem**: Header doesn't stick to top
**Solution**: Ensure `sticky="true"` prop is set (default)

### Mobile Menu Not Opening

**Problem**: Mobile menu button doesn't work
**Solution**: Ensure `<x-layout.mobile-menu />` component exists and Alpine.js is loaded

### Search Not Working

**Problem**: Search button doesn't open modal
**Solution**: Ensure search modal component listens for `open-search` event

### Dark Mode Not Working

**Problem**: Dark mode colors not applying
**Solution**: Ensure Tailwind dark mode is configured with `darkMode: 'class'`

## Related Components

- [Mobile Menu](./mobile-menu.md)
- [User Menu](./user-menu.md)
- [Dark Mode Toggle](./dark-mode-toggle.md)
- [Search Modal](./search-modal.md)

## Changelog

### v1.0.0 (Current)
- Initial implementation
- Sticky positioning
- Scroll behavior
- Dark mode support
- Mobile responsive
- Accessibility compliant

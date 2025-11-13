# Dark Mode Implementation

## Overview

TechNewsHub now supports dark mode with a seamless user experience that respects system preferences and persists user choices across sessions.

## Features

### 1. Dark Mode Toggle
- Located in the navigation bar (main layout) and guest layout
- Uses Alpine.js for reactive state management
- Displays sun icon in dark mode, moon icon in light mode
- Smooth transitions between modes

### 2. Persistence
- User preference stored in `localStorage`
- Persists across browser sessions
- Automatically applied on page load

### 3. System Preference Detection
- Respects `prefers-color-scheme` media query
- Automatically enables dark mode if user's system is set to dark mode
- Only applies if user hasn't manually set a preference

### 4. FOUC Prevention
- Inline script in `<head>` prevents flash of unstyled content
- Applies dark mode class before page renders
- Ensures smooth experience on page load

## Technical Implementation

### Tailwind Configuration
```javascript
// tailwind.config.js
export default {
    darkMode: 'class',
    // ...
}
```

### Layout Structure
```html
<html x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' || ... }" 
      :class="{ 'dark': darkMode }">
```

### Dark Mode Classes
All components use Tailwind's `dark:` variant:
- `dark:bg-gray-900` - Dark background
- `dark:text-gray-100` - Light text
- `dark:border-gray-700` - Dark borders
- And many more throughout the application

## Color Palette

### Light Mode
- Background: `bg-gray-50`, `bg-white`
- Text: `text-gray-900`, `text-gray-600`
- Borders: `border-gray-300`
- Accents: `bg-indigo-600`

### Dark Mode
- Background: `dark:bg-gray-900`, `dark:bg-gray-800`
- Text: `dark:text-gray-100`, `dark:text-gray-400`
- Borders: `dark:border-gray-700`
- Accents: `dark:bg-indigo-500`

## Accessibility

### WCAG AA Compliance
- All color combinations meet WCAG AA contrast ratios
- Minimum contrast ratio of 4.5:1 for normal text
- Minimum contrast ratio of 3:1 for large text
- Tested with Tailwind's default color palette

### Keyboard Navigation
- Dark mode toggle is keyboard accessible
- Proper focus states in both modes
- ARIA labels for screen readers

## Browser Support

- Modern browsers with CSS custom properties support
- Alpine.js v3 compatible browsers
- localStorage support required for persistence
- Graceful degradation for older browsers

## Usage

### For Users
1. Click the sun/moon icon in the navigation bar
2. Preference is automatically saved
3. Dark mode persists across sessions

### For Developers
To add dark mode support to new components:

```html
<!-- Background -->
<div class="bg-white dark:bg-gray-800">

<!-- Text -->
<p class="text-gray-900 dark:text-gray-100">

<!-- Borders -->
<div class="border-gray-300 dark:border-gray-700">

<!-- Hover states -->
<a class="hover:text-gray-700 dark:hover:text-gray-300">
```

## Testing

Run dark mode tests:
```bash
php artisan test --filter=DarkModeTest
```

## Files Modified

### Configuration
- `tailwind.config.js` - Added dark mode configuration and typography plugin

### Layouts
- `resources/views/layouts/app.blade.php` - Main layout with dark mode support
- `resources/views/layouts/guest.blade.php` - Guest layout with dark mode support

### Components
- `resources/views/components/dark-mode-toggle.blade.php` - New toggle component

### Tests
- `tests/Feature/DarkModeTest.php` - Comprehensive dark mode tests

### Dependencies
- `@tailwindcss/typography` - Added for prose dark mode support

## Performance

- No additional HTTP requests
- Minimal JavaScript overhead (Alpine.js)
- CSS classes compiled at build time
- localStorage access is synchronous and fast

## Future Enhancements

Potential improvements for future iterations:
- Auto-switch based on time of day
- Custom color themes
- Per-page dark mode preferences
- Animated transitions between modes

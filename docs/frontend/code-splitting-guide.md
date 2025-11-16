# Code Splitting Guide

## Overview

This document describes the code splitting strategy implemented in TechNewsHub to optimize JavaScript bundle sizes and improve page load performance.

## Strategy

### 1. Route-Based Splitting

We split JavaScript code by route/page to ensure users only download the code they need:

- **Homepage** (`resources/js/pages/homepage.js`)
  - Infinite scroll functionality
  - Hero post interactions
  - Trending posts carousel

- **Article Page** (`resources/js/pages/article.js`)
  - Reading progress indicator
  - Share functionality
  - Bookmark button
  - Post feedback
  - Series progress tracking

- **Dashboard** (`resources/js/pages/dashboard.js`)
  - User stats
  - Bookmark management
  - Activity feed

- **Search** (`resources/js/pages/search.js`)
  - Search autocomplete
  - Search click tracking
  - Filter interactions

### 2. Vendor Chunk Splitting

Third-party dependencies are split into separate vendor chunks:

- **vendor-alpine**: Alpine.js core (shared across all pages)
- **vendor-axios**: HTTP client (if used)
- **vendor**: Other node_modules dependencies

This allows for better browser caching since vendor code changes less frequently than application code.

### 3. Component Chunking

Shared components are grouped into a separate chunk:

- **components**: Reusable UI components used across multiple pages
- **stores**: Alpine.js stores for state management

### 4. Lazy Loading

Non-critical JavaScript is lazy-loaded:

- Comment threads (loaded when scrolled into view)
- Share modals (loaded on demand)
- Advanced filters (loaded when filter panel is opened)

## Configuration

### Vite Configuration

The code splitting is configured in `vite.config.js`:

```javascript
export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                // Page-specific entry points
                'resources/js/pages/homepage.js',
                'resources/js/pages/article.js',
                'resources/js/pages/dashboard.js',
                'resources/js/pages/search.js',
            ],
            refresh: true,
        }),
    ],
    build: {
        rollupOptions: {
            output: {
                manualChunks(id) {
                    // Vendor chunks
                    if (id.includes('node_modules')) {
                        if (id.includes('alpinejs')) {
                            return 'vendor-alpine';
                        }
                        return 'vendor';
                    }
                    
                    // Component chunks
                    if (id.includes('/components/')) {
                        return 'components';
                    }
                    
                    // Page-specific chunks
                    if (id.includes('pages/homepage')) {
                        return 'page-homepage';
                    }
                    // ... other pages
                },
            },
        },
    },
});
```

### Loading Page-Specific JavaScript

In Blade templates, load page-specific JavaScript using Vite directives:

```blade
{{-- Homepage --}}
@vite(['resources/js/pages/homepage.js'])

{{-- Article page --}}
@vite(['resources/js/pages/article.js'])

{{-- Dashboard --}}
@vite(['resources/js/pages/dashboard.js'])

{{-- Search --}}
@vite(['resources/js/pages/search.js'])
```

## Bundle Size Limits

We enforce the following bundle size limits:

### JavaScript
- **Individual bundle**: 500KB maximum
- **Total JavaScript**: 2MB maximum

### CSS
- **Individual bundle**: 200KB maximum
- **Total CSS**: 500KB maximum

## Testing

### Running Tests

Test the code splitting configuration:

```bash
php artisan test --filter=CodeSplittingTest
```

### Analyzing Bundle Sizes

Analyze bundle sizes after building:

```bash
npm run build
node scripts/analyze-bundle-sizes.js
```

This will output:
- Individual bundle sizes
- Total bundle sizes
- Warnings for bundles exceeding limits
- Code splitting effectiveness metrics
- Optimization recommendations

### Manual Verification

1. **Build the assets**:
   ```bash
   npm run build
   ```

2. **Check the build directory**:
   ```bash
   ls -lh public/build/js/
   ls -lh public/build/css/
   ```

3. **Inspect the manifest**:
   ```bash
   cat public/build/manifest.json | jq
   ```

## Performance Impact

### Before Code Splitting
- Single JavaScript bundle: ~1.5MB
- All code loaded on every page
- Slower initial page load
- Poor caching (entire bundle invalidated on any change)

### After Code Splitting
- Main bundle: ~200KB
- Vendor bundle: ~150KB (Alpine.js)
- Page-specific bundles: ~50-100KB each
- Better caching (vendor code cached separately)
- Faster initial page load (only load what's needed)

## Best Practices

### 1. Keep Entry Points Focused

Each page-specific entry point should only import what's needed for that page:

```javascript
// ✅ Good - only imports needed components
import readingProgress from '../components/reading-progress';
import sharePost from '../components/share-post';

// ❌ Bad - imports everything
import '../app.js';
```

### 2. Use Dynamic Imports for Heavy Features

For features that aren't immediately needed:

```javascript
// Load comment system only when needed
Alpine.data('comments', () => ({
    async loadComments() {
        const { default: commentThread } = await import('./components/comment-thread');
        // Use commentThread
    }
}));
```

### 3. Monitor Bundle Sizes

Run the bundle analyzer regularly:

```bash
npm run build && node scripts/analyze-bundle-sizes.js
```

### 4. Optimize Dependencies

- Use tree-shaking compatible packages
- Import only what you need from libraries
- Consider lighter alternatives for heavy dependencies

### 5. Test After Changes

Always run tests after modifying code splitting configuration:

```bash
npm run build
php artisan test --filter=CodeSplittingTest
```

## Troubleshooting

### Bundle Too Large

If a bundle exceeds size limits:

1. **Identify large dependencies**:
   ```bash
   npm run build -- --mode=analyze
   ```

2. **Split further**:
   - Create more specific entry points
   - Move shared code to separate chunks
   - Use dynamic imports for heavy features

3. **Optimize dependencies**:
   - Remove unused imports
   - Use lighter alternatives
   - Enable tree-shaking

### Code Not Splitting

If code isn't splitting as expected:

1. **Check Vite config**: Ensure `manualChunks` is configured correctly
2. **Verify entry points**: Ensure page-specific files exist
3. **Check imports**: Ensure components are imported correctly
4. **Rebuild**: Run `npm run build` to regenerate bundles

### Chunks Not Loading

If chunks fail to load in the browser:

1. **Check manifest**: Verify `public/build/manifest.json` is correct
2. **Check paths**: Ensure Vite directives use correct paths
3. **Clear cache**: Clear browser and Laravel cache
4. **Check console**: Look for 404 errors in browser console

## Maintenance

### Adding New Pages

When adding a new page with specific JavaScript needs:

1. **Create entry point**:
   ```bash
   touch resources/js/pages/new-page.js
   ```

2. **Update Vite config**:
   ```javascript
   input: [
       // ... existing entries
       'resources/js/pages/new-page.js',
   ]
   ```

3. **Add chunk rule**:
   ```javascript
   if (id.includes('pages/new-page')) {
       return 'page-new-page';
   }
   ```

4. **Load in template**:
   ```blade
   @vite(['resources/js/pages/new-page.js'])
   ```

5. **Test**:
   ```bash
   npm run build
   php artisan test --filter=CodeSplittingTest
   ```

### Updating Dependencies

After updating npm dependencies:

1. **Rebuild**:
   ```bash
   npm run build
   ```

2. **Analyze**:
   ```bash
   node scripts/analyze-bundle-sizes.js
   ```

3. **Test**:
   ```bash
   php artisan test --filter=CodeSplittingTest
   ```

4. **Check for size increases**: If bundles grew significantly, investigate why

## Resources

- [Vite Code Splitting](https://vitejs.dev/guide/build.html#chunking-strategy)
- [Rollup Manual Chunks](https://rollupjs.org/configuration-options/#output-manualchunks)
- [Web Performance Best Practices](https://web.dev/performance/)
- [Bundle Size Optimization](https://web.dev/reduce-javascript-payloads-with-code-splitting/)

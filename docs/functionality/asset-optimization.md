# Asset Optimization Guide

This document describes the asset optimization strategies implemented in TechNewsHub to improve performance and meet the requirements for fast page load times.

## Overview

The system implements multiple optimization techniques:
- Production build optimization with Vite
- Long-term caching for static assets (1 year)
- Lazy loading for images
- Critical CSS inlining
- Asset preloading and preconnecting

## Requirements Coverage

This implementation addresses the following requirements:
- **Requirement 12.3**: Static assets served with 1-year cache headers
- **Requirement 12.4**: Image lazy loading implementation
- **Requirement 12.5**: Critical CSS inlining for faster initial render

## Vite Configuration

### Production Build Optimization

The `vite.config.js` file is configured with:

```javascript
build: {
    chunkSizeWarningLimit: 1000,
    rollupOptions: {
        output: {
            manualChunks: {
                'vendor': ['alpinejs'],
            },
        },
    },
    minify: 'terser',
    terserOptions: {
        compress: {
            drop_console: true,
            drop_debugger: true,
        },
    },
    sourcemap: false,
    cssMinify: true,
}
```

**Features:**
- Manual chunk splitting for better caching
- Console.log removal in production
- CSS minification
- Terser minification for JavaScript

### Building for Production

```bash
npm run build
```

This generates optimized assets in the `public/build` directory with:
- Minified JavaScript and CSS
- Content-based hashing for cache busting
- Optimized chunk sizes

## Cache Headers

### SetCacheHeaders Middleware

The `SetCacheHeaders` middleware automatically applies cache headers to static assets:

```php
// Cache for 1 year (31536000 seconds)
Cache-Control: public, max-age=31536000, immutable
Expires: [1 year from now]
```

**Applies to:**
- Files in `build/` directory (Vite assets)
- Files in `storage/` directory (uploaded media)
- Files in `vendor/` directory (vendor assets)
- Static file extensions: .css, .js, .jpg, .jpeg, .png, .gif, .webp, .svg, .woff, .woff2, .ttf, .eot, .ico

**Benefits:**
- Reduces server requests for returning visitors
- Improves page load times
- Reduces bandwidth usage
- Better Lighthouse performance scores

## Image Lazy Loading

### Optimized Image Component

The `<x-optimized-image>` component provides automatic lazy loading:

```blade
<x-optimized-image 
    :src="$post->featured_image_url" 
    :alt="$post->image_alt_text ?? $post->title" 
    class="w-full h-48 object-cover"
    :eager="false"
/>
```

**Attributes:**
- `src`: Image source URL (required)
- `alt`: Alt text for accessibility (required)
- `class`: CSS classes
- `eager`: Set to `true` for above-the-fold images (default: `false`)
- `width`: Optional width attribute
- `height`: Optional height attribute

**Features:**
- `loading="lazy"` for below-the-fold images
- `loading="eager"` for above-the-fold images
- `decoding="async"` for non-blocking image decoding
- Automatic alt text from post metadata

### Usage Guidelines

**Above-the-fold images** (should load immediately):
```blade
<x-optimized-image :src="$url" :alt="$alt" eager="true" />
```

**Below-the-fold images** (should lazy load):
```blade
<x-optimized-image :src="$url" :alt="$alt" />
```

**Where it's used:**
- Home page hero image (eager)
- Featured post images (eager for first, lazy for rest)
- Post show page featured image (eager)
- Related posts (lazy)
- Post cards in listings (lazy)
- Category/tag page posts (lazy)

## Critical CSS

### What is Critical CSS?

Critical CSS is the minimal CSS required to render above-the-fold content. By inlining it in the `<head>`, we eliminate render-blocking CSS requests for the initial viewport.

### Configuration

Edit `config/performance.php`:

```php
'critical_css' => [
    'enabled' => env('CRITICAL_CSS_ENABLED', true),
    'path' => public_path('build/critical.css'),
    'max_size' => 14336, // 14KB recommended
],
```

### Generating Critical CSS

After building assets, generate critical CSS:

```bash
php artisan performance:generate-critical-css
```

This command:
1. Finds the main CSS file in `public/build`
2. Extracts critical styles (base, layout, common utilities)
3. Minifies the extracted CSS
4. Saves to `public/build/critical.css`
5. Validates size (warns if > 14KB)

### How It Works

The `<x-critical-css>` component in the layout:
1. Checks if critical CSS is enabled
2. Verifies the critical CSS file exists
3. Checks file size is within limit
4. Inlines the CSS in a `<style>` tag

### Production Workflow

1. Make CSS changes in `resources/css/app.css`
2. Build assets: `npm run build`
3. Generate critical CSS: `php artisan performance:generate-critical-css`
4. Deploy to production

### Advanced Critical CSS Generation

For more accurate critical CSS extraction, consider using:

**Option 1: Critical (npm package)**
```bash
npm install --save-dev critical
```

**Option 2: Critters (Vite plugin)**
```bash
npm install --save-dev critters
```

**Option 3: Online tools**
- https://www.sitelocity.com/critical-path-css-generator
- https://jonassebastianohlsson.com/criticalpathcssgenerator/

## Asset Preloading

### Preconnect and DNS Prefetch

The layout includes preconnect hints for external domains:

```html
<link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
<link rel="dns-prefetch" href="https://fonts.googleapis.com">
```

**Benefits:**
- Establishes early connections to external domains
- Reduces DNS lookup time
- Faster loading of external resources

### Font Preloading

For custom fonts, add to layout:

```html
<link rel="preload" href="/fonts/custom-font.woff2" as="font" type="font/woff2" crossorigin>
```

## Performance Monitoring

### Lighthouse Scores

Target scores:
- Performance: 90+
- Accessibility: 90+
- Best Practices: 90+
- SEO: 90+

### Testing

```bash
# Run Lighthouse
npx lighthouse https://your-site.com --view

# Check specific page
npx lighthouse https://your-site.com/posts/example --view
```

### Key Metrics

Monitor these Core Web Vitals:
- **LCP (Largest Contentful Paint)**: < 2.5s
- **FID (First Input Delay)**: < 100ms
- **CLS (Cumulative Layout Shift)**: < 0.1

## Troubleshooting

### Images Not Lazy Loading

1. Check browser support (all modern browsers support it)
2. Verify component is being used: `<x-optimized-image>`
3. Check browser DevTools Network tab for lazy loading behavior

### Cache Headers Not Applied

1. Verify middleware is registered in `bootstrap/app.php`
2. Check file path matches patterns in `SetCacheHeaders::isStaticAsset()`
3. Clear browser cache and test in incognito mode

### Critical CSS Not Inlining

1. Check if file exists: `public/build/critical.css`
2. Verify config: `config('performance.critical_css.enabled')`
3. Check file size is within limit
4. Run: `php artisan config:clear`

### Build Issues

```bash
# Clear Vite cache
rm -rf node_modules/.vite

# Rebuild
npm run build
```

## Best Practices

### Images

1. **Always provide alt text** for accessibility
2. **Use eager loading** only for above-the-fold images
3. **Optimize images** before upload (ImageProcessingService handles this)
4. **Use appropriate formats**: WebP with JPEG fallback

### CSS

1. **Keep critical CSS under 14KB**
2. **Avoid @import** in CSS (use Vite imports instead)
3. **Use Tailwind's purge** to remove unused styles
4. **Minimize custom CSS**

### JavaScript

1. **Defer non-critical scripts**
2. **Use dynamic imports** for large libraries
3. **Minimize third-party scripts**
4. **Use Alpine.js** for lightweight interactivity

## Environment Variables

Add to `.env`:

```env
# Enable/disable critical CSS
CRITICAL_CSS_ENABLED=true

# Vite configuration
VITE_APP_NAME="${APP_NAME}"
```

## Deployment Checklist

- [ ] Run `npm run build` to generate production assets
- [ ] Run `php artisan performance:generate-critical-css`
- [ ] Test Lighthouse scores
- [ ] Verify cache headers in browser DevTools
- [ ] Check lazy loading behavior
- [ ] Test on slow 3G connection
- [ ] Verify images have alt text
- [ ] Check Core Web Vitals in Google Search Console

## Related Documentation

- [Caching Strategy](./caching-strategy.md)
- [Performance Optimization](./performance-optimization.md)
- [Image Processing](../admin/nova-user-guide.md#media-library)

## References

- [Vite Build Optimization](https://vitejs.dev/guide/build.html)
- [Web.dev: Optimize LCP](https://web.dev/optimize-lcp/)
- [MDN: Lazy Loading](https://developer.mozilla.org/en-US/docs/Web/Performance/Lazy_loading)
- [Critical CSS Guide](https://web.dev/extract-critical-css/)

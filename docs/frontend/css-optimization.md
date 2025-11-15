# CSS Optimization Guide

## Overview

This document describes the CSS optimization strategy implemented for TechNewsHub to achieve fast page loads and optimal performance.

## Goals

- **First Contentful Paint < 1.5s**: Inline critical CSS for instant rendering
- **Main CSS < 50 KB (gzipped)**: Remove unused CSS and minify
- **Total CSS < 100 KB (gzipped)**: Optimize overall CSS bundle size
- **No Flash of Unstyled Content (FOUC)**: Proper CSS loading strategy

## Implementation

### 1. Critical CSS

Critical CSS contains the minimal styles needed for above-the-fold content to render immediately.

**Location**: `resources/css/critical.css`

**Contents**:
- Base HTML styles
- Header and navigation
- Hero section skeleton
- Post card skeleton
- Loading states
- Dark mode basics
- Accessibility styles

**Size Target**: < 14 KB (for optimal inlining)

### 2. CSS Loading Strategy

#### Production Mode

```blade
<!-- Inline critical CSS -->
<style id="critical-css">
    /* Critical styles inlined here */
</style>

<!-- Preload main CSS -->
<link rel="preload" href="/build/assets/app-[hash].css" as="style" onload="this.onload=null;this.rel='stylesheet'">

<!-- Fallback for no-JS -->
<noscript>
    <link rel="stylesheet" href="/build/assets/app-[hash].css">
</noscript>

<!-- Async load main CSS -->
<script>
    // Load CSS asynchronously and remove critical CSS after load
</script>
```

#### Development Mode

```blade
<!-- Load CSS normally for easier debugging -->
@vite(['resources/css/app.css'])
```

### 3. Unused CSS Removal

**Tool**: PurgeCSS (via PostCSS)

**Configuration**: `postcss.config.js`

**Process**:
1. Scans all Blade templates and JavaScript files
2. Identifies used CSS classes
3. Removes unused classes in production builds
4. Preserves safelisted classes (Alpine.js, dynamic classes, etc.)

**Safelist**:
- Prose classes (for article content)
- Dark mode classes
- Dynamic utility classes
- Alpine.js directives
- Third-party library classes (Highlight.js, Flatpickr, TinyMCE)

### 4. CSS Minification

**Tool**: cssnano (via PostCSS)

**Optimizations**:
- Remove comments
- Normalize whitespace
- Minify font values
- Minify gradients
- Merge duplicate rules
- Optimize calc() expressions

### 5. Code Splitting

CSS is split into separate files for better caching:

- `critical-[hash].css` - Critical above-the-fold styles
- `app-[hash].css` - Main application styles
- Page-specific CSS chunks (if needed)

## Usage

### Using the Optimized CSS Component

In your Blade layouts:

```blade
<head>
    <!-- Other head elements -->
    
    <x-optimized-css :page="$page ?? 'default'" />
</head>
```

The component automatically:
- Inlines critical CSS in production
- Preloads main CSS
- Provides no-JS fallback
- Removes critical CSS after main CSS loads

### Building for Production

```bash
# Standard build
npm run build

# Build with optimization analysis
npm run build:optimized

# Analyze CSS without building
npm run analyze-css
```

### Optimization Report

After running `npm run build:optimized`, a report is generated at:
`storage/app/css-optimization-report.json`

**Report Contents**:
- Total CSS size
- Individual file sizes
- Gzipped estimates
- Performance recommendations
- Target compliance

## Performance Targets

| Metric | Target | Current |
|--------|--------|---------|
| Critical CSS | < 14 KB | Check report |
| Main CSS (gzipped) | < 50 KB | Check report |
| Total CSS (gzipped) | < 100 KB | Check report |
| First Contentful Paint | < 1.5s | Run Lighthouse |
| Largest Contentful Paint | < 2.5s | Run Lighthouse |

## Testing

Run CSS optimization tests:

```bash
php artisan test --filter=CssOptimizationTest
```

**Tests verify**:
- Critical CSS file exists and is properly sized
- Optimized CSS component exists
- PostCSS configuration includes optimization plugins
- Vite configuration includes critical CSS
- Layout uses optimized CSS component
- CSS files are minified in production
- PurgeCSS configuration exists
- Optimization scripts exist

## Monitoring

### Lighthouse Audit

```bash
# Install Lighthouse CLI
npm install -g lighthouse

# Run audit
lighthouse https://your-site.com --view
```

**Key Metrics**:
- Performance score > 90
- First Contentful Paint < 1.5s
- Largest Contentful Paint < 2.5s
- Cumulative Layout Shift < 0.1

### WebPageTest

Visit [webpagetest.org](https://www.webpagetest.org/) and test your site.

**Check**:
- Start Render time
- Speed Index
- CSS blocking time
- Total CSS size

## Troubleshooting

### Critical CSS Too Large

If critical CSS exceeds 14 KB:

1. Review `resources/css/critical.css`
2. Remove non-essential styles
3. Move styles to main CSS
4. Consider splitting into multiple critical files per page type

### Styles Missing in Production

If styles are missing after build:

1. Check PurgeCSS safelist in `postcss.config.js`
2. Add missing patterns to safelist
3. Verify content paths include all template files
4. Check for dynamic class names (use safelist for these)

### FOUC (Flash of Unstyled Content)

If you see unstyled content briefly:

1. Verify critical CSS is inlined
2. Check theme script runs before body
3. Ensure preload link has correct onload handler
4. Test with slow network throttling

### Dark Mode Flashing

If dark mode flashes light theme:

1. Verify theme script in `<head>` before CSS
2. Check localStorage is accessible
3. Ensure `preload` class is removed after load
4. Test with different theme preferences

## Best Practices

### Adding New Styles

1. **Critical styles**: Add to `resources/css/critical.css` only if needed for initial render
2. **Main styles**: Add to `resources/css/app.css` or component-specific files
3. **Dynamic classes**: Add patterns to PurgeCSS safelist if generated dynamically

### Maintaining Performance

1. **Regular audits**: Run Lighthouse monthly
2. **Monitor bundle size**: Check optimization report after changes
3. **Test on slow connections**: Use Chrome DevTools throttling
4. **Review critical CSS**: Keep under 14 KB

### Development Workflow

1. Develop with `npm run dev` (no optimization)
2. Test locally with `npm run build`
3. Run `npm run build:optimized` before deployment
4. Review optimization report
5. Run performance tests
6. Deploy to production

## Configuration Files

### PostCSS Configuration

**File**: `postcss.config.js`

```javascript
{
  plugins: {
    'postcss-import': {},
    tailwindcss: {},
    autoprefixer: {},
    '@fullhuman/postcss-purgecss': { /* production only */ },
    cssnano: { /* production only */ }
  }
}
```

### PurgeCSS Configuration

**File**: `purgecss.config.js`

Contains safelist patterns and content paths.

### Vite Configuration

**File**: `vite.config.js`

Includes critical CSS in input array and configures CSS minification.

## Resources

- [Critical CSS Guide](https://web.dev/extract-critical-css/)
- [PurgeCSS Documentation](https://purgecss.com/)
- [cssnano Documentation](https://cssnano.co/)
- [Web.dev Performance](https://web.dev/performance/)
- [Lighthouse Documentation](https://developers.google.com/web/tools/lighthouse)

## Changelog

### Version 1.0.0 (2025-11-15)

- Initial CSS optimization implementation
- Critical CSS extraction
- PurgeCSS integration
- cssnano minification
- Optimization reporting
- Comprehensive testing

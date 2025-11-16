# CSS Optimization Guide

## Overview

This guide documents the CSS optimization strategy implemented for TechNewsHub to achieve fast page loads and optimal performance.

## Performance Targets

- **Critical CSS**: < 14 KB (inline)
- **Main CSS**: < 50 KB (gzipped)
- **Total CSS**: < 100 KB (gzipped)
- **First Contentful Paint**: < 1.5s
- **Largest Contentful Paint**: < 2.5s

## Implementation

### 1. Critical CSS Strategy

Critical CSS contains the minimal styles needed for above-the-fold content rendering. We maintain separate critical CSS files for different page types:

- `critical.css` - Default critical styles
- `critical-home.css` - Homepage-specific critical styles
- `critical-article.css` - Article page-specific critical styles

#### Usage

In your Blade views, specify the page type:

```blade
@extends('layouts.app', ['page' => 'home'])
```

Available page types:
- `home` - Homepage
- `article` - Article/post pages
- `category` - Category pages
- `search` - Search results
- `dashboard` - User dashboard
- `default` - Fallback for other pages

### 2. Optimized CSS Loading Component

The `<x-optimized-css>` component handles intelligent CSS loading:

**Development Mode:**
- Loads CSS normally via Vite for hot module replacement
- No inline CSS for easier debugging

**Production Mode:**
- Inlines critical CSS in the `<head>` for instant rendering
- Preloads main CSS file
- Defers non-critical CSS loading
- Provides noscript fallback

### 3. PostCSS Configuration

The PostCSS pipeline includes:

1. **postcss-import** - Resolves @import statements
2. **Tailwind CSS** - Processes utility classes
3. **Autoprefixer** - Adds vendor prefixes
4. **PurgeCSS** (production only) - Removes unused CSS
5. **cssnano** (production only) - Minifies CSS

#### PurgeCSS Safelist

Important classes are safelisted to prevent removal:

- Prose classes (`/^prose/`)
- Dark mode classes (`/^dark/`)
- Dynamic classes (`/^bg-/`, `/^text-/`, etc.)
- Animation classes (`/^animate-/`, `/^transition-/`)
- Alpine.js directives (`x-cloak`, `x-show`, etc.)
- Third-party library classes (`hljs`, `flatpickr`, etc.)

### 4. Build Process

#### Development Build

```bash
npm run dev
```

Starts Vite dev server with hot module replacement.

#### Production Build

```bash
npm run build:production
```

This runs:
1. Vite build with production optimizations
2. Critical CSS extraction and optimization
3. CSS analysis and reporting

#### Analyze CSS

```bash
npm run analyze-css
```

Generates a detailed report of CSS file sizes and optimization opportunities.

### 5. Vite Configuration

The Vite config includes:

- **Code Splitting**: Separate chunks for vendors, stores, components, and pages
- **CSS Minification**: Using esbuild for fast minification
- **Asset Optimization**: Optimized file names with content hashes
- **Tree Shaking**: Removes unused code
- **Modern Target**: ES2020 for smaller bundles

### 6. Tailwind Configuration

Tailwind is configured to:

- Scan all Blade templates and JavaScript files
- Use JIT mode for faster builds
- Support dark mode with class strategy
- Include custom color palette
- Extend with typography and forms plugins

## Best Practices

### 1. Keep Critical CSS Small

- Only include styles for above-the-fold content
- Avoid complex selectors
- Use shorthand properties
- Remove comments and whitespace

### 2. Use Tailwind Utilities

- Prefer Tailwind utilities over custom CSS
- Use `@apply` sparingly (increases bundle size)
- Leverage Tailwind's purge feature

### 3. Optimize Images

- Use lazy loading for below-the-fold images
- Serve responsive images with srcset
- Use WebP format with fallbacks

### 4. Minimize CSS Specificity

- Avoid deep nesting
- Use single class selectors when possible
- Leverage Tailwind's utility-first approach

### 5. Test Performance

Run Lighthouse audits regularly:

```bash
# Install Lighthouse CLI
npm install -g lighthouse

# Run audit
lighthouse https://your-site.com --view
```

## Monitoring

### CSS Size Reports

After each production build, check the reports:

- `storage/app/css-optimization-report.json` - Overall CSS analysis
- `storage/app/critical-css-report.json` - Critical CSS sizes

### Performance Metrics

Monitor these metrics in production:

- First Contentful Paint (FCP)
- Largest Contentful Paint (LCP)
- Cumulative Layout Shift (CLS)
- Time to Interactive (TTI)

## Troubleshooting

### Critical CSS Not Inlining

**Problem**: Critical CSS is not being inlined in production.

**Solution**:
1. Ensure `APP_ENV=production` in `.env`
2. Run `npm run build:production`
3. Check that critical CSS files exist in `public/build/assets/`

### Styles Missing After Build

**Problem**: Some styles are missing after production build.

**Solution**:
1. Check if classes are dynamically generated
2. Add patterns to PurgeCSS safelist in `postcss.config.js`
3. Verify Tailwind content paths include all template files

### Large CSS Bundle Size

**Problem**: CSS bundle exceeds size targets.

**Solution**:
1. Run `npm run analyze-css` to identify large files
2. Review unused Tailwind utilities
3. Consider code splitting for page-specific styles
4. Check for duplicate CSS rules

### Flash of Unstyled Content (FOUC)

**Problem**: Page flashes unstyled content on load.

**Solution**:
1. Ensure critical CSS is properly inlined
2. Check that theme script runs before body renders
3. Verify `preload` class is removed after page load
4. Test with throttled network in DevTools

## Additional Resources

- [Tailwind CSS Documentation](https://tailwindcss.com/docs)
- [Vite Documentation](https://vitejs.dev/)
- [Web.dev Performance Guide](https://web.dev/performance/)
- [PurgeCSS Documentation](https://purgecss.com/)

## Testing

Run the CSS optimization test suite:

```bash
php artisan test --filter=CssOptimizationTest
```

All tests should pass before deploying to production.

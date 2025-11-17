# CDN Setup Guide

This guide explains how to configure CloudFront CDN with S3 for optimal asset delivery.

## Overview

The platform supports CDN integration for serving static assets (images, CSS, JavaScript) through CloudFront, which provides:

- Global edge caching for faster asset delivery
- Reduced server load
- Better performance for international users
- Automatic compression (gzip, brotli)
- HTTPS by default

## Prerequisites

1. AWS Account with S3 and CloudFront access
2. AWS credentials configured in `.env`
3. S3 bucket created for asset storage

## Configuration Steps

### 1. Configure AWS S3

Add your AWS credentials to `.env`:

```env
AWS_ACCESS_KEY_ID=your_access_key
AWS_SECRET_ACCESS_KEY=your_secret_key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket-name
```

### 2. Create CloudFront Distribution

1. Go to AWS CloudFront Console
2. Create a new distribution
3. Set Origin Domain to your S3 bucket
4. Configure Origin Access Identity (OAI) for security
5. Set Default Cache Behavior:
   - Viewer Protocol Policy: Redirect HTTP to HTTPS
   - Allowed HTTP Methods: GET, HEAD, OPTIONS
   - Cache Policy: CachingOptimized
   - Compress Objects Automatically: Yes
6. Note the CloudFront distribution domain (e.g., `d123456abcdef.cloudfront.net`)

### 3. Configure CDN URL

Add the CloudFront URL to your `.env`:

```env
CDN_URL=https://d123456abcdef.cloudfront.net
```

### 4. Sync Assets to S3

Run the sync command to upload your assets:

```bash
php artisan assets:sync-s3
```

Options:
- `--directory=public` - Local directory to sync (default: public)
- `--s3-directory=` - S3 directory prefix (optional)
- `--dry-run` - Preview what would be synced without uploading

### 5. Update Asset References

The platform automatically uses CDN URLs when configured. You can use:

**In Blade templates:**

```blade
{{-- For build assets --}}
<link rel="stylesheet" href="@cdn('build/assets/app.css')">
<script src="@cdn('build/assets/app.js')"></script>

{{-- For storage files --}}
<img src="@cdnStorage('media/image.jpg')" alt="Image">
```

**In PHP:**

```php
use App\Services\CdnService;

$cdnService = app(CdnService::class);

// Asset URL
$cssUrl = $cdnService->assetUrl('build/assets/app.css');

// Storage URL
$imageUrl = $cdnService->storageUrl('media/image.jpg');
```

**Helper functions:**

```php
// Responsive image URL
$url = responsive_image_url('media/image.jpg', 'medium');

// Responsive srcset
$srcset = responsive_image_srcset($variants);
```

### 6. Lazy Loading Images

Use the lazy-image component for optimized image loading:

```blade
<x-lazy-image 
    src="{{ $article->featured_image }}"
    alt="{{ $article->title }}"
    width="800"
    height="600"
    webp="{{ $article->featured_image_webp }}"
    loading="lazy"
    class="w-full h-auto"
/>
```

## Cache Headers

The platform automatically sets appropriate cache headers:

- **CSS/JS/Fonts**: 1 year (immutable)
- **Images**: 30 days
- **Other assets**: 1 day

These are configured in:
- `app/Http/Middleware/SetCacheHeaders.php`
- `app/Services/CdnService.php`

## Deployment Workflow

### Production Deployment

1. Build assets locally:
   ```bash
   npm run build:production
   ```

2. Sync to S3:
   ```bash
   php artisan assets:sync-s3
   ```

3. Invalidate CloudFront cache (if needed):
   ```bash
   # Using AWS CLI
   aws cloudfront create-invalidation \
     --distribution-id YOUR_DISTRIBUTION_ID \
     --paths "/*"
   ```

### Automated Deployment

Add to your CI/CD pipeline:

```yaml
# .github/workflows/deploy.yml
- name: Build assets
  run: npm run build:production

- name: Sync to S3
  run: php artisan assets:sync-s3
  env:
    AWS_ACCESS_KEY_ID: ${{ secrets.AWS_ACCESS_KEY_ID }}
    AWS_SECRET_ACCESS_KEY: ${{ secrets.AWS_SECRET_ACCESS_KEY }}
```

## Image Optimization

The platform automatically optimizes images:

1. **Compression**: Images are compressed using GD library
2. **WebP Generation**: Modern WebP format for better compression
3. **Responsive Variants**: Multiple sizes (thumbnail, medium, large)
4. **Lazy Loading**: Images load only when visible

### Upload Process

When an image is uploaded:

1. Original is compressed and EXIF stripped
2. Responsive variants are generated (200px, 800px, 1600px)
3. WebP versions are created for each variant
4. All files are uploaded to S3 with cache headers
5. CDN URLs are returned

## Monitoring

Check CDN status:

```php
$cdnService = app(CdnService::class);

if ($cdnService->isConfigured()) {
    echo "CDN is configured";
}

if ($cdnService->isS3Configured()) {
    echo "S3 is configured";
}
```

## Troubleshooting

### Assets not loading from CDN

1. Verify CDN_URL is set in `.env`
2. Check CloudFront distribution is deployed
3. Verify S3 bucket permissions
4. Check browser console for CORS errors

### Images not displaying

1. Verify files exist in S3 bucket
2. Check S3 bucket policy allows public read
3. Verify CloudFront OAI has S3 access
4. Check image paths are correct

### Cache not updating

1. Invalidate CloudFront cache
2. Check cache headers are set correctly
3. Verify versioned asset names (Vite adds hashes)

## Performance Tips

1. **Use WebP**: Always provide WebP versions for modern browsers
2. **Lazy Load**: Use lazy loading for below-the-fold images
3. **Responsive Images**: Serve appropriate sizes for device
4. **Preload Critical Assets**: Add preload hints for above-the-fold images
5. **Optimize Build**: Run production builds with minification

## Security

1. **Use HTTPS**: Always serve assets over HTTPS
2. **Restrict S3 Access**: Use CloudFront OAI, don't allow direct S3 access
3. **Set CORS**: Configure CORS headers if needed
4. **Signed URLs**: Use signed URLs for private content (future enhancement)

## Cost Optimization

1. **Enable Compression**: CloudFront automatically compresses assets
2. **Set Appropriate TTLs**: Longer cache = fewer origin requests
3. **Use Regional Edge Caches**: Enabled by default in CloudFront
4. **Monitor Usage**: Check AWS Cost Explorer regularly

## References

- [AWS CloudFront Documentation](https://docs.aws.amazon.com/cloudfront/)
- [S3 Best Practices](https://docs.aws.amazon.com/AmazonS3/latest/userguide/best-practices.html)
- [Laravel Filesystem Documentation](https://laravel.com/docs/filesystem)

## Phase 13 – Performance Checklist

 - [x] Redis caching + fragment caching + invalidation
 - [x] DB indexes, eager loading, select optimization
 - [ ] Queues + Horizon
 - [x] Vite production build, image optimization, CDN
 
 Notes:
 - CacheService implements view/query/model caching and invalidation; observers clear caches on post/category/tag changes
 - Controllers use selects and with() for eager loading; migrations include multiple performance indexes
 - Queued jobs (e.g., TrackPostView) in use; Horizon not configured in composer
 - Vite configured; ImageProcessingService generates variants/WebP; S3 + AWS_URL supports CDN

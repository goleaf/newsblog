## Analytics & Reporting (Priority)

1) Fix search query logging (ensure `query` saved)
2) Replace inline validations with Form Requests:
   - `EngagementMetricController@track`
   - `SearchClickController@track`
3) Views last 30 days chart (data + JS renderer)
4) Popular categories analytics (controller + view)
5) Traffic sources breakdown (direct/search/social/referral)
6) Tests:
   - View tracking + dedup (existing coverage)
   - Analytics calculations (categories, sources, 30d views)
   - Search logging (queries saved, clicks)
7) Code style & assets:
   - Run Pint
   - npm run build

## Broken Link Checker (Task 30)

- [ ] 30.1 Create BrokenLink model and migration
  - [ ] Migration with: post_id, url, status, checked_at, response_code, timestamps
  - [ ] Model with relation to `Post`
- [ ] 30.2 Implement CheckBrokenLinks job
  - [ ] Scan all published posts for external links
  - [ ] Check HTTP status; mark 404/timeouts as broken
  - [ ] Upsert results and build basic report (logs)
  - [ ] Schedule weekly
- [ ] 30.3 Build Broken Links UI in Nova
  - [ ] Nova resource for `BrokenLink`
  - [ ] Actions: Fix (replace URL in post), Ignore
  - [ ] Show last checked timestamp, response code
- [ ] 30.4 Tests
  - [ ] Test link scanning
  - [ ] Test broken link detection
  - [ ] Test ignore internal links and ok/broken states

## Security Measures (Priority)

- [x] 26.1 Rate limiting middleware
  - [x] Login limiting 5/min (`throttle:login`)
  - [x] Comment submissions 3/min (`throttle:comments`)
  - [x] API limiting 60/min public, 120/min auth (`throttle:api`)
  - [x] Sliding window via Redis (`throttleWithRedis`)

- [x] 26.2 Security headers
  - [x] X-Frame-Options
  - [x] X-Content-Type-Options
  - [x] Content-Security-Policy (nonce-based)
  - [x] Referrer-Policy
  - [x] Permissions-Policy

- [x] 26.3 CSRF protection
  - [x] Tokens on all forms (`@csrf`)
  - [x] CSRF middleware enabled globally
  - [x] Token refresh handled by framework session flow

- [x] 26.4 Input sanitization
  - [x] Sanitize user HTML content (SimpleSanitizer)
  - [x] Escape output with Blade `{{ }}`
  - [x] Validate uploads (image mime + size)

- [x] 26.5 Security tests
  - [x] Rate limiting
  - [x] CSRF protection
  - [x] XSS prevention
  - [x] File upload validation

- [x] 14.1 Create Bookmark model and migration
  - Added `bookmarks` table with `reader_token`, `post_id`, timestamps, and unique composite index.
  - Created `App\Models\Bookmark` with relation to `Post`.
- [x] 14.2 Create BookmarkController
  - Implemented `index`, `store`, `destroy`, `toggle` with anonymous `reader_token` cookie.
  - Added Form Requests with validation and messages.
- [ ] 14.3 Add bookmark button to post cards and articles
  - Added `resources/views/components/bookmark-button.blade.php`.
  - Injected on post cards and article page.
  - Implemented `resources/js/bookmarks.js` and imported in `resources/js/app.js`.
- [ ] 14.4 Write bookmark system tests
  - Added `tests/Feature/Bookmarks/BookmarkTest.php` covering create, duplicate, remove, index.

## Phase 2: Content Management & Admin (Nova)

- [x] 5. Set up Laravel Nova admin panel

- [x] 5.1 Create Nova resources for core models
  - [x] PostResource with fields and filters
  - [x] CategoryResource with parent selector
  - [x] TagResource
  - [x] UserResource with role management
  - [x] MediaResource with preview

- [x] 5.2 Add Nova actions for post management
  - [x] PublishPost action
  - [x] SchedulePost action
  - [x] ArchivePost action
  - [x] BulkPublish action

- [x] 5.3 Create Nova dashboard with metrics
  - [x] TotalPosts metric card
  - [x] PostsPerDay trend metric
  - [x] PendingComments value metric
  - [x] PopularPosts table

- [x] 5.4 Customize Nova appearance
  - [x] Configure branding (logo, colors) in `config/nova.php`
  - [x] Customize navigation menu in `app/Providers/NovaServiceProvider.php`
  - [x] Add custom CSS for admin panel at `public/css/nova/custom.css`

Notes:
- Nova path configured at `/admin`.
- Access control via `NovaServiceProvider::gate()` (roles: admin, editor, author).
- Main dashboard registered as `App\Nova\Dashboards\Main`.

## 46. Menu Builder (Priority)

- [x] Data layer:
  - `Menu` + `MenuItem` models with enums and relationships
  - Migrations with location/type/order/parent_id and FKs
  - Factories for tests
- [x] Rendering:
  - `<x-menu>` component (header/footer/mobile)
  - Unlimited nesting, active state, mobile toggle
- [ ] Nova UI:
  - Menu + MenuItem resources
  - Drag-and-drop ordering (follow-up resource tool)
- [ ] Tests:
  - Creation, ordering, nesting feature tests
  - Run minimal test filter

# TODO: Related Posts Algorithm

## Priority: High

## Widgets (Priority)

- [x] Weather widget (API, geolocation, cache 30m, fallback)
- [x] Stock ticker (API, color-coded, update 60s, links)
- [x] Countdown widget (labels customizable, per-second updates)
- [x] Add endpoints and Form Requests
- [x] Add i18n strings
- [x] Integrate JS modules and import in `resources/js/app.js`
- [x] Write tests:
  - [x] Weather endpoint + caching
  - [x] Stock endpoint + update cadence
  - [x] Countdown render test
  - [x] Poll voting (placeholder skipped)

- [x] 23. Implement SEO optimization features
  - [x] 23.1 Create SEO meta tags component (Open Graph, Twitter, Schema.org, canonical)
  - [x] 23.2 Implement SitemapService with lastmod/changefreq/priority and splitting
  - [x] 23.3 Create sitemap generation command and regenerate on post publish
  - [x] 23.4 Add dynamic robots.txt route including sitemap URL
  - [x] 23.5 Write SEO tests (meta, sitemap verified; robots.txt added)

- [x] 13.1 Verify and enhance RelatedPostsService
  - [x] Verify category weight is 40%
  - [x] Verify tag matching weight is 40%
  - [x] Verify date proximity weight is 20%
  - [x] Verify caching for 1 hour (3600 seconds)
  - [x] Verify limit defaults to 4 posts
  - [x] Update PostController to use limit of 4 instead of 5

- [x] 13.2 Enhance related posts section on article page
  - [x] Add publication date display
  - [x] Ensure "Read more" link is visible (or make it more explicit)
  - [x] Verify featured images display correctly
  - [x] Verify title display

- [x] 13.3 Enhance related posts algorithm tests
  - [x] Add test for exact weight calculations (category = 40%, tags = 40%, date = 20%)
  - [x] Add test for cache TTL (1 hour = 3600 seconds)
  - [x] Add test for edge case: no related posts found (empty collection)
  - [x] Add test for date proximity weight calculation (same day = 20%, 30+ days = 0%)
  - [x] Run all tests and fix any failures

## Phase 3: Frontend Public Pages

- [x] 9. Build homepage with featured content

- [x] 9.1 Create HomeController and index view
  - [x] Implement hero section with featured post
  - [x] Add breaking news section
  - [x] Create category-based content sections
  - [x] Add "Most Popular" sidebar widget
  - [x] Implement "Trending Now" widget

- [ ] 9.2 Add skeleton loading screens
  - [ ] Create skeleton components for post cards
  - [ ] Implement shimmer animation effect
  - [ ] Add fade-in transition when content loads

- [x] 9.3 Implement lazy loading for images
  - [x] Add loading="lazy" attribute to images
  - [x] Create intersection observer for below-fold images
  - [x] Implement blur-up placeholder technique

- [ ] 9.4 Write homepage tests
  - [x] Test featured post display
  - [x] Test breaking news ticker
  - [ ] Test lazy loading functionality

# Widget System Tasks

- [x] 20.1 Create Widget and WidgetArea models
  - [x] Migrations for `widgets` and `widget_areas`
  - [x] Add fields: type, settings (JSON), order, active
  - [x] Implement relationships (`WidgetArea` hasMany `Widget`)

- [x] 20.2 Create WidgetService
  - [x] Implement renderWidget for all types
  - [x] Add renderArea and caching helpers
  - [x] Align view paths; add missing views

- [x] 20.3 Build built-in widgets
  - [x] RecentPostsWidget
  - [x] PopularPostsWidget
  - [x] CategoriesWidget
  - [x] TagsCloudWidget
  - [x] NewsletterWidget
  - [x] SearchWidget
  - [x] CustomHTMLWidget

- [ ] 20.4 Create widget management UI in Nova
  - [x] Add `Widget` and `WidgetArea` Nova resources
  - [ ] Add drag-and-drop ordering in Nova (follow-up)
  - [ ] Add rich configuration forms in Nova (follow-up)

- [x] 20.5 Write widget system tests
  - [x] Test widget rendering
  - [x] Test widget ordering
  - [x] Test widget configuration

## 43. Content Calendar

- [x] 43.1 Build calendar view component
  - [x] Create monthly calendar grid
  - [x] Display posts on their dates
  - [x] Color-code by status (published, scheduled, draft)
  - [x] Add month navigation
  - _Requirements: 40_

- [x] 43.2 Implement drag-and-drop scheduling
  - [x] Allow dragging posts to different dates
  - [x] Update scheduled_at or published_at
  - [x] Show confirmation on drop
  - [x] Validate date changes
  - _Requirements: 40_

- [x] 43.3 Add calendar sidebar
  - [x] Show posts for selected date
  - [x] Display post details
  - [x] Add quick edit options
  - _Requirements: 40_

- [x] 43.4 Write content calendar tests
  - [x] Test calendar rendering
  - [x] Test drag-and-drop
  - [x] Test date updates

- [x] 43.5 Filters and export
  - [x] Filter by author and category
  - [x] iCal export (month/week/day ranges)

- [x] 43.6 Additional views and insights
  - [x] Week and Day view (list layout)
  - [x] Publish frequency stats (counts)
  - [x] Content gaps visualization (days with no posts)
  - [x] Notify author on reschedule

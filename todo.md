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

# TODO: Related Posts Algorithm

## Priority: High

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

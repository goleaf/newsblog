## ADDED Requirements

### Requirement: Frontend Layout & Homepage
The public site MUST use a Tailwind-based layout (`resources/views/layouts/app.blade.php`) providing a top utility bar (logo, social icons, dark mode toggle, search icon), primary navigation with horizontal menu + mega menu for categories/subcategories + featured posts, responsive hamburger for mobile, and expandable search. The homepage must include: hero featured post with overlay metadata + CTA, trending side rail (3-4 posts), latest posts grid (3 columns desktop, responsive), popular categories cards (icon, color badge, counts), horizontal editor's picks carousel, newsletter box, sidebar widgets (search, categories, popular posts, tags, newsletter, social, ad slot), and a footer with three content columns plus bottom bar (copyright, socials, back-to-top button). All components must be responsive, accessible, and support lazy-loaded images.

#### Scenario: Render homepage blocks
- **WHEN** a visitor opens `/`
- **THEN** the hero displays the featured post with overlay, trending list appears beside it, the latest grid paginates or offers Load More, popular categories + editor's picks render with counts, sidebar widgets show, newsletter form validates email, and footer/back-to-top controls appear.

### Requirement: Single Post Experience
Single post pages MUST show breadcrumbs, category badge, large title, metadata (author avatar/name link, relative date, reading time, view count, share buttons). Featured image supports captions/alt text; article content honors typography (headings, code syntax highlighting, blockquotes, tables, responsive embeds). Footer includes tag chips, share section, author bio box with stats/social links, related posts (3-4 from same category), comment form (name/email/website/comment, validation, note) if allowed, threaded comments (Gravatar, reply button, nested up to 3 levels), load more comments, disabled-state messaging, pending approval acknowledgement, previous/next navigation, reading progress bar, sticky/floating share controls, print-friendly option, and schema.org Article metadata.

#### Scenario: Post comment submission
- **GIVEN** comments enabled and moderation required
- **WHEN** a visitor submits the form with valid data
- **THEN** the request validates (min lengths), shows success toast “Comment submitted for approval,” clears the form, and the pending comment either appears inline (if auto-approved) or shows a pending message without reload via AJAX.

### Requirement: Listing, Archive, and Search Pages
Category pages (`/category/{slug}`) MUST show header (name, description, counts, breadcrumb, subscribe/RSS), filters (sort latest/popular/commented, view toggle grid/list, time filter All/Today/Week/Month), grids (3-col desktop, degrade) with hover animations and metadata; list view shows image left, content right. Pagination or infinite scroll MUST show counters (“Showing 1-12 of N”). Empty state suggests alternatives. Tag pages mirror category but highlight tag name/related tags. Author pages include large avatar, bio, social links, stats (total posts, views, comments), join date, and list posts. Archive pages (e.g., `/archive/2024/11`) group posts by day with navigation between months. Search page shows “Search results for” heading, count, filters (type: posts/pages/all; category; sort), highlight query terms, provide empty-state suggestions. 404 page offers search, links to home, categories, recent posts, plus friendly illustration.

#### Scenario: Toggle list view on category page
- **WHEN** a reader switches to List view and sorts by Popular
- **THEN** cards re-render as single-column rows sorted by view count, filters remain sticky across pagination or infinite scroll, and the breadcrumb + header stay visible.

### Requirement: Interactive Features & Engagement
Frontend MUST deliver: live global search with debounced (300ms) AJAX suggestions (posts w/ thumbnails, categories, tags, “View all results”), keyboard navigation + ESC close; AJAX comments with inline reply forms, character counts, localStorage for name/email; social sharing (Facebook, Twitter/X, LinkedIn, WhatsApp, copy link, email) with share counts and floating bars; newsletter forms (footer + popup triggered by scroll/time; double opt-in message; AJAX submission + duplicate handling). Post views increment via AJAX with session gating; popular posts widgets reflect view data refreshed daily. Reading progress bar sits at top, sticky, reflecting scroll depth. Estimated reading time displays using word counts. Dark mode toggle stores preference (localStorage) and animates transitions using CSS variables. Images and widgets must lazy load with blur-up placeholders, infinite scroll for listings updates pushState, print button triggers print stylesheet that hides chrome and shows URLs.

#### Scenario: Use live search keyboard navigation
- **WHEN** a user types in the header search, arrow keys through suggestions, and presses Enter
- **THEN** the highlighted suggestion opens directly (post/category/tag) while ESC or outside click closes the panel without page scroll jitter.

### Requirement: Accessibility & Focus Management
All interactive elements MUST be reachable via keyboard (tab order), show visible focus outlines, activate via Enter/Space, and modals/dropdowns close via ESC. Provide skip-to-content links, semantic HTML landmarks (`nav`, `main`, `article`, `aside`), ARIA labels, and alt text for every image. Text must default to ≥16px with line-height ≥1.6, maintain WCAG AA contrast, support 200% zoom without layout breakage, avoid text-in-images, and manage focus trapping in modals (e.g., media lightbox, menus) returning focus on close. Screen reader cues should announce state changes (e.g., toasts, dark mode). Breadcrumbs and table-of-contents anchors require ARIA attributes for navigation.

#### Scenario: Navigate via keyboard
- **WHEN** a user tabs through the homepage
- **THEN** focus order follows logical navigation (skip link → header controls → nav → hero CTA...), outlines remain visible in both themes, and pressing ESC closes any open dropdown returning focus to the triggering control.

### Requirement: Feedback, Media, and Motion Enhancements
TechNewsHub MUST provide toast notifications (success/info/warning/error, green/blue/yellow/red) stacked top-right, auto-dismiss after 5s, pause on hover, closable, sliding animations, used for actions like “Comment submitted,” “Subscribed,” “Link copied,” validation errors. Implement lightbox overlays for in-article images with dark backdrop, close button, ESC/overlay click to close, navigation arrows for multiple images, caption display, zoom controls, and counters. Sticky header must hide on scroll down, reveal on scroll up with smaller logo + shadow; sidebar sticks on desktop without overlapping footer. Anchor links (TOC, back-to-top, comment links) must smooth scroll. Provide back-to-top button showing after 500px, fixed bottom-right, fade transitions. Loading states include initial page spinner, skeleton cards for content, AJAX button spinners with disabled state and “Loading…” text, blur placeholders + fade-in for images, broken-image fallbacks. 

#### Scenario: Open image lightbox
- **WHEN** a reader clicks an inline article image
- **THEN** a full-screen lightbox opens with dark overlay, caption, navigation arrows (if gallery), zoom controls, accessible focus trap, ESC/background click close, and the previous scroll position is preserved when closing.

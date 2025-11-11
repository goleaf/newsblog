## ADDED Requirements

### Requirement: Laravel 11 Foundation
TechNewsHub MUST run on Laravel 11 using SQLite in local/dev, ship with Laravel Breeze authentication scaffolding, Intervention/Image for media processing, and Laravel Sanctum for API auth. Environment defaults MUST configure queue, cache, and mail drivers compatible with SQLite-based development setups.

#### Scenario: Install project dependencies
- **GIVEN** a fresh clone of TechNewsHub
- **WHEN** `composer install` and `npm install` complete
- **THEN** Laravel 11, Breeze, Sanctum, Intervention/Image, and Tailwind build tooling are present and configured for the `TechNewsHub` app name.

### Requirement: Database Schema Coverage
The application MUST provide migrations for: extended `users`; hierarchical `categories`; `tags`; `posts` with metadata, scheduling, flags, soft deletes; `post_tag`; nested `comments` with soft deletes; `media_library`; `pages`; `newsletters`; key/value `settings`; `post_views`; and `contact_messages`. Each table MUST include the columns, enums, indexes, and foreign keys described in Prompt 1 (e.g., status enums, meta fields, parent references, timestamps).

#### Scenario: Run migrations
- **GIVEN** a configured `.env`
- **WHEN** `php artisan migrate` executes
- **THEN** every table listed above exists with correct nullable/enum/default constraints, foreign keys (e.g., `posts.user_id -> users.id`, `comments.parent_id -> comments.id`), and soft delete columns where required.

### Requirement: User Model Enhancements
The `User` model MUST expose has-many relationships to posts, comments, and media; computed `full_name` and `avatar_url` accessors; scopes for `active`, `admins`, `editors`, `authors`; and helper methods `isAdmin()`, `isEditor()`, `isAuthor()` returning booleans derived from the `role` attribute.

#### Scenario: Filter admins
- **GIVEN** users with various `role` values and statuses
- **WHEN** `User::admins()->active()->get()` runs
- **THEN** only active users whose role equals `admin` are returned and each instance answers `true` for `isAdmin()`.

### Requirement: Category Hierarchy Behavior
`Category` MUST support `parent()` self-belongs-to, `children()` has-many, `posts()` relation, and accessors/scopes: `url` attribute, `active()`, `parents()` (only top-level), and `ordered()` (by `display_order`). `getPostsCount()` MUST return eager-counted totals respecting nested posts.

#### Scenario: Retrieve ordered active parents
- **WHEN** `Category::active()->parents()->ordered()->get()` executes
- **THEN** the result includes only active root categories sorted by `display_order`, each with `url` resolving to `/category/{slug}`.

### Requirement: Post Domain Logic
`Post` MUST belong to `user` and `category`, relate to `tags` (many-to-many), `comments` (approved scope default), and `views`. It MUST expose accessors for `formatted_date`, truncated `excerpt_limited`, `reading_time_text`, and `featured_image_url`. Query scopes must include `published`, `featured`, `trending`, `scheduled`, `byCategory`, `byTag`, `byAuthor`, `recent`, `popular`. Domain methods include `incrementViewCount()`, `isPublished()`, `canBeEditedBy($user)`. Observers (or model events) MUST auto-generate unique slugs and calculate/stash reading time minutes on save using word counts.

#### Scenario: Schedule future post
- **GIVEN** a post draft with `scheduled_at` tomorrow
- **WHEN** it is saved with status `scheduled`
- **THEN** the slug auto-generates from title if missing, `reading_time` is recalculated, and the post surfaces via `Post::scheduled()` but not via `Post::published()` until `published_at` is past.

### Requirement: Tag & Newsletter Behavior
`Tag` MUST belongToMany posts, expose a `url` accessor, and implement `getPostsCount()`. `Newsletter` must provide scopes `subscribed`, `unsubscribed`, `verified`, plus methods `verify()` (set status + timestamp) and `unsubscribe()` (set status + token handling). Both models must guard against duplicate slugs/emails.

#### Scenario: Verify newsletter subscriber
- **WHEN** `Newsletter::first()->verify()` is called with a valid token
- **THEN** the subscriber status updates to `subscribed`, `verified_at` is timestamped, and future queries via `Newsletter::verified()` include the record.

### Requirement: Comment Threading & Moderation
`Comment` MUST belong to `post`, optional `user`, optional parent; expose `replies()` has-many; scopes `approved`, `pending`, `recent`, `forPost($postId)`; and helper methods `isApproved()`, `markAsApproved()`, `markAsSpam()`. Soft deletes are required for moderation workflows.

#### Scenario: Approve pending comment
- **GIVEN** a pending comment on a post
- **WHEN** `markAsApproved()` executes
- **THEN** the status flips to `approved`, the record appears in `Comment::approved()->forPost($postId)` results, and timestamps remain intact.

### Requirement: Media, Page, and Auxiliary Models
`Media` MUST belong to a user, expose `url` and `size_human_readable` accessors, scopes `images`, `documents`, `recent`, and track metadata (file type, size, alt text, captions). `Page` requires `url` accessor, `active()` and `ordered()` scopes, template + order fields. `Setting` entries must support grouped retrieval. `PostView`, `ContactMessage`, and pivot models must be defined with their relationships.

#### Scenario: Fetch recent images
- **WHEN** `Media::images()->recent()->take(10)->get()` runs
- **THEN** only image-type media created most recently are returned with populated `url`/`size_human_readable` attributes referencing stored files.

## ADDED Requirements

### Requirement: A/B Testing Platform
Editors MUST create experiments on post titles, featured images, or CTA blocks with traffic splitting (default 50/50), metrics tracking (views, clicks, engagement), statistical significance detection, and winner declaration powering automatic rollout. Experiments should allow scheduling, variant preview, and history audit.

#### Scenario: Declare experiment winner
- **WHEN** an experiment comparing two headlines reaches statistical significance with Variant B outperforming
- **THEN** the system notifies the editor, auto-publishes Variant B as canonical, archives Variant A, and records the winning metrics in the experiment log.

### Requirement: Internal Link Suggestions
While editing posts, the system MUST suggest relevant internal links based on content similarity, showing titles/URLs in a sidebar with one-click insertion, tracking inserted suggestions, and providing orphaned posts reports (no inbound links).

#### Scenario: Insert suggested link
- **WHEN** an editor writes about “AI regulation”
- **THEN** the sidebar lists matching posts with score indicators; clicking “Insert” adds the hyperlink at the cursor with proper localization and logs the linkage for analytics.

### Requirement: Image Alt Text Assistant
The media manager MUST flag images missing alt text, offer bulk edit, and optionally generate AI-suggested descriptions (with manual approval). Provide best-practice tips and validation to prevent empty alt attributes.

#### Scenario: Bulk add alt text
- **WHEN** an editor selects five images lacking alt text and requests AI suggestions
- **THEN** the system queues generation, presents proposed text for review, and updates the media records upon approval while noting provenance.

### Requirement: Social Auto-Posting
Publishing workflows MUST support automatic posting to Twitter/X, Facebook Page, and LinkedIn with customizable message templates, hashtags, featured image attachments, scheduling, retries, and success tracking. Credentials stored securely and configurable per channel.

#### Scenario: Publish with social queue
- **WHEN** a post is published with auto-post enabled
- **THEN** jobs enqueue social posts per channel, attach the featured image, include generated hashtags, retry on failure with backoff, and log delivery status in the social queue dashboard.

### Requirement: Email Digest Automation
The system MUST provide daily/weekly/monthly email digests featuring new/popular/trending posts personalized by user interests, responsive templates built with Tailwind, unsubscribe links, open/click tracking, and scheduling via queue. Digests should localize content and leverage stored preferences.

#### Scenario: Send weekly digest
- **WHEN** the weekly digest job runs Sunday evening
- **THEN** subscribers receive an email with top posts, trending topics, CTA buttons, localized strings, and tracking pixels, and unsubscribing updates preferences immediately.

## ADDED Requirements

### Requirement: Print Optimized Experience
The public site MUST ship a dedicated Tailwind print stylesheet (`resources/css/print.css`) that removes non-content chrome, enforces black-on-white typography, reveals link URLs, adds page breaks before major headings, prints metadata (site logo, author, publish date, print timestamp, canonical URL), and optionally renders a QR code/URL footer. Print actions shall omit comments, ads, navigation, and respect responsive images while preventing orphaned captions.

#### Scenario: Print article snapshot
- **WHEN** a reader triggers print on a post page
- **THEN** the print preview hides header/footer/sidebar/comments, renders body text in legible 12pt+ black font on white background, shows each hyperlink as `text (https://example.com/path)`, inserts a page break before each `h2`, displays the site logo and post metadata header, includes the canonical URL and print date in the footer, and keeps images within page bounds without breaking captions.

### Requirement: Advanced Search Experience
Search MUST support full-text queries across posts, pages, and media metadata (title, content, excerpt, tags) using Laravel Scout (database driver by default, extensible to Meilisearch/Algolia), with filters (date range, author, category, tags), sort options (relevance, newest, popularity), saved searches, recent searches per user/session, and the ability to refine within results. Result listings must highlight matched terms and expose pagination or “load more.”

#### Scenario: Refine search results
- **GIVEN** a reader searched “AI policy” and sees initial results
- **WHEN** they filter by Category = “Opinion,” set Date Range = “Last 30 days,” and sort by Popularity
- **THEN** the result list updates without full reload, highlights query matches in excerpts, persists the filter chips, and allows saving the refined query to “My Searches” for future reuse.

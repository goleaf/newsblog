## ADDED Requirements

### Requirement: Article Views
The application MUST render article list and detail pages using Tailwind, including pagination for index and a reading progress indicator on detail. Create/edit forms MUST support validation feedback and preview.

#### Scenario: List page with pagination
- WHEN requesting the article index route
- THEN a paginated list renders with titles, excerpts, meta, and navigation links.

#### Scenario: Detail page with reading progress
- WHEN reading an article
- THEN a progress indicator or similar feedback is visible and updates as the user scrolls.


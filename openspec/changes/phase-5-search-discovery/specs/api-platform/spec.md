## ADDED Requirements

### Requirement: Search Endpoint & Filters
The API/web MUST expose a search endpoint with filters for category, author, tags, date range, and reading time, returning paginated results with highlights where supported.

#### Scenario: Filtered search
- WHEN querying with a category and date range
- THEN only matching items are returned and results are paginated.

### Requirement: Query & Click Logging
Search queries and clicked results MUST be logged with count and timestamps to power analytics.

#### Scenario: Log click
- WHEN a user clicks a result
- THEN a click entry is recorded with the associated query context.


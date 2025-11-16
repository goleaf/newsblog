## ADDED Requirements

### Requirement: Analytics Metrics & Caching
The platform MUST calculate and cache article/user/traffic metrics with invalidation on updates and scheduled aggregation jobs.

#### Scenario: Cached dashboard
- WHEN loading the dashboard after metrics aggregation
- THEN data is served from cache and invalidated on relevant content updates.


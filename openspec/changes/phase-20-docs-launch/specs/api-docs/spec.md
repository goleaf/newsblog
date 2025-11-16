## ADDED Requirements

### Requirement: API Documentation via Scribe
The system SHALL generate API documentation using Scribe covering all public and authenticated endpoints added in this phase.

#### Scenario: Generate docs for new endpoints
- WHEN the documentation generator runs
- THEN it SHALL include endpoints for tokens (list/create/delete), tags (list/articles), comments (update/delete), articles (create/update/delete), and social share tracking
- AND descriptions, parameters, and example responses SHALL be rendered from controller docblocks


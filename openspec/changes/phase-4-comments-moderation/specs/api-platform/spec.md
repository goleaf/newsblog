## ADDED Requirements

### Requirement: Comment Endpoints
API/web routes MUST provide endpoints for create/update/delete/reply and reaction toggles (like/helpful/insightful).

#### Scenario: Toggle reaction
- WHEN a user toggles a reaction on a comment
- THEN counts update and the user's reaction state is reflected on subsequent fetch.


## ADDED Requirements

### Requirement: Admin CRUD for Posts, Categories, Tags, and Media
The admin area MUST provide CRUD interfaces for posts, categories, tags, and media with appropriate validation and authorization via policies.

#### Scenario: Admin can manage posts
- GIVEN an admin session
- WHEN accessing the admin posts section
- THEN list/create/edit/delete actions are available and guarded by policies.

#### Scenario: Admin can manage categories and tags
- GIVEN an admin session
- WHEN accessing category/tag sections
- THEN CRUD actions exist and persist changes with slugs validated and generated.


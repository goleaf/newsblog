## ADDED Requirements

### Requirement: User Roles & Policy Mapping
The `User` domain MUST expose role helpers (e.g., `isAdmin`, `isEditor`, `isAuthor`, `isUser`) and policies MUST be mapped for core resources (User, Post, Category, Tag) to enforce view/update/delete rules.

#### Scenario: Role helpers return correct booleans
- GIVEN users with roles: admin, editor, author, user
- WHEN calling the respective helpers
- THEN the correct boolean is returned for each role.

#### Scenario: Policy enforcement
- GIVEN policy mappings for User, Post, Category, Tag
- WHEN checking abilities (viewAny, view, create, update, delete)
- THEN the rules reflect role‑based access consistent with platform conventions.


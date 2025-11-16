## ADDED Requirements

### Requirement: Authentication Views (Breeze or Equivalent)
The application MUST provide user‑facing forms for registration, login, password reset request/reset, and email verification notice with validation feedback.

#### Scenario: Registration and login pages exist
- GIVEN the web routes
- WHEN requesting the registration and login endpoints
- THEN forms render with expected fields and CSRF protection enabled.


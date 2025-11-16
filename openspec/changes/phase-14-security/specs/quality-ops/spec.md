## ADDED Requirements

### Requirement: Security Headers & CSRF/Ratelimits
The platform MUST apply security headers, validate CSRF on web, and enforce rate limits for sensitive routes.

#### Scenario: Headers on responses
- WHEN accessing web pages
- THEN Referrer-Policy, Content-Security-Policy, and related headers are present.


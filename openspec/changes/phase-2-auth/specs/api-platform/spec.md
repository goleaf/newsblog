## ADDED Requirements

### Requirement: Authentication Endpoints & Flows
The platform MUST provide endpoints and controllers for registration, login, logout, password reset, and email verification, implemented using Laravel controllers and Form Requests.

#### Scenario: Registration flow
- GIVEN a guest
- WHEN POSTing valid data to the registration endpoint
- THEN a user account is created, the user is authenticated, and an email verification is queued (or marked verified if configured for local).

#### Scenario: Login flow with rate limiting
- GIVEN correct credentials and login rate limits
- WHEN POSTing to the login endpoint repeatedly
- THEN successful login returns a session or Sanctum‑backed cookie, and excessive attempts trigger the `login` RateLimiter with a 429 response.

#### Scenario: Password reset flow
- GIVEN a registered user
- WHEN submitting a password reset request and following the emailed token
- THEN the password can be updated and the user can log in with the new password.

#### Scenario: Email verification flow
- GIVEN an unverified user
- WHEN following the verification link
- THEN the account is marked verified and guarded routes requiring verification become accessible.


## ADDED Requirements

### Requirement: Two-Factor Authentication
The authentication system MUST allow admins/authors to enable TOTP-based two-factor authentication via QR code provisioning (Google Authenticator compatible), backup codes, remember-device (30 days), and recovery workflows. Login flow shall prompt for OTP after password, enforce rate limiting, and support disabling with confirmation.

#### Scenario: Enroll TOTP and login
- **WHEN** an admin enables 2FA, scans the QR code, and confirms using a 6-digit code
- **THEN** backup codes are generated and displayed once, stored hashed, and subsequent logins require both password and valid TOTP unless the device is trusted.

### Requirement: Spam Protection Layer
Forms (comments, contact, newsletter) MUST include honeypot fields, submission timing checks, IP/keyword blocking, optional reCAPTCHA v3, Akismet integration, and per-IP rate limiting. Provide admin controls to configure sensitivity, whitelist trusted users, and review spam logs. Spam submissions should be quarantined for review with a log of reasons.

#### Scenario: Detect bot comment
- **WHEN** a bot submits a comment within 1 second triggering honeypot and keyword filters
- **THEN** the request is rejected without revealing validation details, the attempt logs IP, user agent, and reason, and rate limiting increments preventing subsequent spam bursts.

## Phase 2 – Auth & User Management Checklist

- [ ] Controllers present and aligned with Laravel conventions
  - RegisterController, LoginController, PasswordResetController, EmailVerificationController
- [ ] Form Requests for auth flows
  - RegisterRequest, LoginRequest, PasswordResetRequest, UpdatePasswordRequest
- [ ] Views present (Breeze) or equivalent UX
  - Registration, Login, Password Reset, Email Verification Notice
- [ ] Policies and gates
  - UserPolicy (view/update/delete), role‑based checks wired
- [ ] Rate limits configured for login and sensitive endpoints
  - Verify `login` limiter present


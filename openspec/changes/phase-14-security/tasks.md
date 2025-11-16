## Phase 14 – Security Checklist

 - [x] Password policy, hashing configuration
 - [x] Session security + CSRF + headers (CSP, Referrer-Policy, etc.)
 - [x] Rate limits for login/API/comments/search
 - [x] HTML sanitization for user content
 - [x] GDPR: export/delete/anonymize + privacy policy
 
 Notes:
 - Hashing via PasswordController/Hash; session config hardened; CSRF enforced; SecurityHeaders middleware present with tests (SecurityHeadersTest)
 - RateLimiter rules in AppServiceProvider for api, login, search, comments; routes use throttle
 - SimpleSanitizer sanitizes comment HTML with HTMLPurifier safe profile and cached definitions
 - GDPR service and privacy-policy route present with tests (GdprComplianceTest)

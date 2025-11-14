## Priority Queue – 2025-11-13

1. Document current UI issues on `https://newsblog.test/post/jenkins-security-best-practices` using Playwright capture.
2. Refactor the associated Blade template to Tailwind-only utilities, removing Bootstrap classes and inline styling.
3. Move any residual inline scripts/styles into dedicated files under `resources/js` and `resources/css`.
4. Ensure all text strings are moved to JSON language files for multilanguage support.
5. Update controllers to leverage dedicated request classes with validation and localized error messages.
6. Expand automated tests covering the affected routes and controllers, then execute targeted test suite runs.


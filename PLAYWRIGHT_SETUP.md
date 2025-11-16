# Playwright E2E Testing Setup

## Installation

Playwright has been installed and configured for end-to-end browser testing.

## Configuration

- **Config File**: `playwright.config.js`
- **Test Directory**: `tests/Playwright/`
- **Browsers**: Chromium, Firefox, WebKit (Desktop & Mobile)

## Running Tests

```bash
# Run all E2E tests
npm run test:e2e

# Run with UI mode (interactive)
npm run test:e2e:ui

# Run in headed mode (see browser)
npm run test:e2e:headed

# Debug mode
npm run test:e2e:debug
```

## Test Files

- `tests/Playwright/homepage.spec.js` - Homepage functionality tests
- `tests/Playwright/post-detail.spec.js` - Post detail page tests

## Features Tested

- Homepage loading and content display
- Featured posts section
- Latest articles grid
- Sort functionality
- Mobile responsiveness
- Dark mode support
- Post detail pages
- Share buttons

## Notes

- Tests automatically start the Laravel development server
- Screenshots are captured on test failures
- Traces are collected for debugging failed tests


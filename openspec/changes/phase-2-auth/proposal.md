# Change: Phase 2 – Authentication & User Management

## Why
Deliver and verify core authentication and user management flows using Laravel 12 conventions: controllers, Form Requests, views (where applicable), and policies. Aligns with Implementation Plan Phase 2 while keeping changes minimal-first.

## What Changes
- Specify requirements for auth controllers and Form Requests (register, login, password reset, email verification).
- Define policy mapping and role‑based checks at a high level.
- No package changes; rely on existing Sanctum/Breeze setup.

## Impact
- Affects: platform-core (User), api-platform (auth flows), frontend-experience (auth views minimal expectations).
- Risk: Low to medium depending on scope; this change focuses on specs and checklist.

## Acceptance Criteria
- `openspec validate phase-2-auth --strict` passes.
- Tasks enumerate verification and minimal scaffolding needed for Phase 2.


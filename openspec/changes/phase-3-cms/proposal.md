# Change: Phase 3 – Content Management System

## Why
Deliver core CMS flows: authoring and managing articles, categories, tags, and media with minimal-first views and controllers, plus a small service layer for article business logic and view tracking.

## What Changes
- Add specification deltas and a checklist for Article controllers/views/requests, ArticleService, view tracking, Category & Tag management, and Media management.
- No dependency changes.

## Impact
- Affects: platform-core (Article domain + services), frontend-experience (article/category/tag views), admin-panel (admin CRUD), quality-ops (view tracking minimal behavior).

## Acceptance Criteria
- `openspec validate phase-3-cms --strict` passes.
- Tasks enumerate items and can be implemented incrementally.


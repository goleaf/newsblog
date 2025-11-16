## ADDED Requirements

### Requirement: Comment Threading & Statuses
Comments MUST support parent/child threading, statuses (pending/approved/rejected/flagged), and helpers to transition status.

#### Scenario: Reply threading
- WHEN creating a reply with `parent_id`
- THEN it appears in `replies()` for the parent and respects approved scope in listings.

### Requirement: Auto Moderation Hook
An AutoModerationService MUST evaluate new comments for prohibited terms or spam heuristics and flag them when detected.

#### Scenario: Auto-moderation flags risky content
- WHEN submitting a comment containing prohibited phrases
- THEN the comment is created with a flagged/pending status for review.


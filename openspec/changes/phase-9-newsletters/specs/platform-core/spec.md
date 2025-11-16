## ADDED Requirements

### Requirement: Newsletter Generation & Sending
The platform MUST generate newsletter content with a responsive HTML template and send via queued jobs with retry and rate limiting.

#### Scenario: Batched sends queued
- WHEN scheduling a newsletter
- THEN jobs are enqueued in batches and delivery status is tracked.


## Phase 9 – Newsletter Checklist

 - [x] NewsletterController: subscribe/confirm/unsubscribe/update preferences
- [ ] NewsletterService: generate content + template
 - [x] SendNewsletterJob: basic sending (marks sent) + ready for retries
 - [x] Tracking: opens + clicks
   - Implemented newsletter open pixel and click redirect with Cache-based counters
 - [x] Scheduling: base command to queue sends (newsletters:send) + daily/weekly schedules
- [x] NewsletterService: generate digest content (daily/weekly/monthly)
 - [x] Metrics API: GET /api/v1/newsletters/sends/{id}/metrics (opens, clicks)
 - [x] Admin sends view: /admin/newsletters/sends lists recent sends with opens/clicks
- [ ] Admin UI: list/preview/manual send/metrics/subscriber mgmt

## ADDED Requirements

### Requirement: API Rate Limiting Dashboard
The admin API console MUST display per-endpoint usage, rate-limit violations, top API consumers, response time metrics, and error rates with charts. It should allow adjusting rate limits, blocking abusive IPs/API keys, and exporting stats. Data collected via logs/metrics pipeline updated at least hourly.

#### Scenario: Investigate rate limit spike
- **WHEN** a client exceeds the rate limit on `/api/v1/posts`
- **THEN** the dashboard highlights the endpoint with violation counts, shows the offending API key/IP, enables the admin to throttle further or block it, and logs any rule changes with timestamp and actor.

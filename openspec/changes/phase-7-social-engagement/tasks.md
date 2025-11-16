## Phase 7 – Social & Engagement Checklist

- [x] SocialShareController: track share events (POST /api/v1/shares)
  - Implemented controller + routes, returns total_shares per post
- [x] FollowController: follow/unfollow/list followers/following/check
  - Implemented follow/unfollow/list under /api/v1/users/{id}/...
  - Added suggestions endpoint: GET /api/v1/users/suggestions
- [ ] ActivityService/Controller: record generate personal/following feed
  - [x] ActivityController: /api/v1/activity/me and /api/v1/activity/following with ActivityResource
- [ ] Views: share buttons, follow UI, activity feed

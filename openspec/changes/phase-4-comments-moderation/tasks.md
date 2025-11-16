## Phase 4 – Comments & Moderation Checklist

- [ ] CommentController: store/update/destroy/reply
- [ ] Form Requests: StoreCommentRequest, UpdateCommentRequest
- [ ] Comment views: list + form + reply UI
- [ ] Reactions: store/toggle + counts
  - [x] API endpoint: POST /api/v1/comments/{commentId}/reactions (sanctum)
  - [x] Controller: App\Http\Controllers\Api\CommentReactionController@react
  - [x] Tests: tests/Feature/Api/CommentReactionControllerTest.php
- [ ] AutoModerationService: word lists + simple spam heuristics
- [ ] Moderation queue: list/review/approve/reject/bulk
  - [x] List open flags: GET /api/v1/moderation/flags (admin/editor)
  - [x] Review flag: POST /api/v1/moderation/flags/{flag}/review (status=reviewed|resolved|rejected)
  - [x] Bulk review: POST /api/v1/moderation/flags/bulk-review (ids[], status)
  - [x] Approve/Reject comment via web routes (comments.approve/comments.reject)
  - [x] Comment flagging: POST /api/v1/comments/{commentId}/flags with reason/notes

# TODO: Related Posts Algorithm

## Priority: High

- [ ] 13.1 Verify and enhance RelatedPostsService
  - [ ] Verify category weight is 40%
  - [ ] Verify tag matching weight is 40%
  - [ ] Verify date proximity weight is 20%
  - [ ] Verify caching for 1 hour (3600 seconds)
  - [ ] Verify limit defaults to 4 posts
  - [ ] Update PostController to use limit of 4 instead of 5

- [ ] 13.2 Enhance related posts section on article page
  - [ ] Add publication date display
  - [ ] Ensure "Read more" link is visible (or make it more explicit)
  - [ ] Verify featured images display correctly
  - [ ] Verify title display

- [ ] 13.3 Enhance related posts algorithm tests
  - [ ] Add test for exact weight calculations (category = 40%, tags = 40%, date = 20%)
  - [ ] Add test for cache TTL (1 hour = 3600 seconds)
  - [ ] Add test for edge case: no related posts found (empty collection)
  - [ ] Add test for date proximity weight calculation (same day = 20%, 30+ days = 0%)
  - [ ] Run all tests and fix any failures

# Nova Integration User Acceptance Testing Checklist

## Overview
This checklist covers all aspects of the Nova integration for user acceptance testing. Testers should complete all items relevant to their role.

**Testing Environment:** Staging  
**Nova URL:** `/nova`  
**Test Date:** _______________  
**Tester Name:** _______________  
**Tester Role:** [ ] Admin [ ] Editor [ ] Author

---

## 1. Authentication & Authorization

### 1.1 Admin Access
- [ ] Admin user can log in to Nova dashboard
- [ ] Admin user sees all resources in sidebar
- [ ] Admin user can access all features
- [ ] Admin user can manage all resources

### 1.2 Editor Access
- [ ] Editor user can log in to Nova dashboard
- [ ] Editor user sees content-related resources (Posts, Categories, Tags, Comments, Media)
- [ ] Editor user cannot access User management
- [ ] Editor user cannot access Settings

### 1.3 Author Access
- [ ] Author user can log in to Nova dashboard
- [ ] Author user can create and edit their own posts
- [ ] Author user cannot delete posts
- [ ] Author user cannot access User management
- [ ] Author user cannot access Settings

### 1.4 Unauthorized Access
- [ ] Regular user (no admin/editor/author role) cannot access Nova
- [ ] Unauthenticated user is redirected to login
- [ ] Accessing `/nova` without proper role shows 403 error

---

## 2. Resource Management

### 2.1 Post Resource
- [ ] **List View**
  - [ ] Posts are displayed in a table/list
  - [ ] Columns show: Title, Author, Category, Status, Published Date
  - [ ] Pagination works correctly
  - [ ] Sorting works for all columns
  - [ ] Search functionality finds posts by title/content

- [ ] **Create Post**
  - [ ] Can create new post
  - [ ] All fields are available: title, slug, excerpt, content, featured image, category, tags, status, scheduling, SEO metadata
  - [ ] Rich text editor works for content field
  - [ ] Image upload works for featured image
  - [ ] Category selection works
  - [ ] Tag selection/creation works
  - [ ] Status dropdown works (draft, published, scheduled)
  - [ ] Scheduling date/time picker works
  - [ ] SEO fields (meta title, meta description, meta keywords) save correctly
  - [ ] Post is saved successfully

- [ ] **Edit Post**
  - [ ] Can edit existing post
  - [ ] All fields load with correct values
  - [ ] Changes are saved correctly
  - [ ] Featured image can be changed
  - [ ] Category can be changed
  - [ ] Tags can be added/removed

- [ ] **Delete Post**
  - [ ] Can delete post (admin/editor only)
  - [ ] Confirmation dialog appears
  - [ ] Post is removed from list after deletion
  - [ ] Soft delete works (post can be restored if needed)

- [ ] **View Post**
  - [ ] Post detail view shows all information
  - [ ] Related posts are displayed (if applicable)
  - [ ] Post revisions are accessible (if applicable)

### 2.2 Category Resource
- [ ] **List View**
  - [ ] Categories are displayed
  - [ ] Shows: Name, Slug, Parent Category, Post Count
  - [ ] Hierarchical structure is visible

- [ ] **Create Category**
  - [ ] Can create new category
  - [ ] Name and slug fields work
  - [ ] Parent category selection works
  - [ ] Category is saved successfully

- [ ] **Edit Category**
  - [ ] Can edit existing category
  - [ ] Can change parent category
  - [ ] Changes are saved correctly

- [ ] **Delete Category**
  - [ ] Can delete category
  - [ ] Warning appears if category has posts
  - [ ] Category is removed after deletion

### 2.3 Tag Resource
- [ ] **List View**
  - [ ] Tags are displayed
  - [ ] Shows: Name, Slug, Post Count

- [ ] **Create Tag**
  - [ ] Can create new tag
  - [ ] Name and slug fields work
  - [ ] Tag is saved successfully

- [ ] **Edit Tag**
  - [ ] Can edit existing tag
  - [ ] Changes are saved correctly

- [ ] **Delete Tag**
  - [ ] Can delete tag
  - [ ] Tag is removed after deletion

### 2.4 Comment Resource
- [ ] **List View**
  - [ ] Comments are displayed
  - [ ] Shows: Post Title, Author, Content, Status, Creation Date
  - [ ] Status is clearly visible (pending, approved, rejected, spam)

- [ ] **Approve Comment**
  - [ ] Can approve pending comments
  - [ ] Comment status changes to approved
  - [ ] Comment appears on frontend after approval

- [ ] **Reject Comment**
  - [ ] Can reject comments
  - [ ] Comment status changes to rejected
  - [ ] Comment does not appear on frontend

- [ ] **Mark as Spam**
  - [ ] Can mark comments as spam
  - [ ] Comment status changes to spam
  - [ ] Spam comments are filtered correctly

- [ ] **Delete Comment**
  - [ ] Can delete comments
  - [ ] Comment is removed after deletion

- [ ] **Reply to Comment**
  - [ ] Can reply to comments (if feature exists)
  - [ ] Reply is saved correctly
  - [ ] Reply appears in thread

### 2.5 User Resource
- [ ] **List View**
  - [ ] Users are displayed
  - [ ] Shows: Name, Email, Role, Status, Registration Date

- [ ] **Create User**
  - [ ] Can create new user
  - [ ] All fields work: name, email, password, role, avatar, bio, status
  - [ ] Role dropdown works (admin, editor, author, user)
  - [ ] User is saved successfully

- [ ] **Edit User**
  - [ ] Can edit existing user
  - [ ] Can change role
  - [ ] Can change status
  - [ ] Password can be updated
  - [ ] Changes are saved correctly

- [ ] **Delete User**
  - [ ] Can delete user (with appropriate warnings)
  - [ ] User is removed after deletion

### 2.6 Media Resource
- [ ] **List View**
  - [ ] Media files are displayed
  - [ ] Shows: Thumbnail, Filename, File Type, Size, Upload Date
  - [ ] Images display thumbnails correctly

- [ ] **Upload Media**
  - [ ] Can upload new media files
  - [ ] File type validation works (images only, or configured types)
  - [ ] File size validation works
  - [ ] Upload progress is shown
  - [ ] File appears in list after upload

- [ ] **View Media**
  - [ ] Can view media details
  - [ ] Shows: Dimensions, Alt Text, Associated Posts
  - [ ] Full-size image can be viewed

- [ ] **Delete Media**
  - [ ] Can delete media files
  - [ ] Warning appears if media is used in posts
  - [ ] Media is removed after deletion

- [ ] **Filter Media**
  - [ ] Can filter by file type
  - [ ] Can filter by upload date
  - [ ] Filters work correctly

---

## 3. Dashboard Metrics

### 3.1 Value Metrics
- [ ] **Total Posts**
  - [ ] Metric displays correct total post count
  - [ ] Number updates when posts are added/deleted

- [ ] **Total Users**
  - [ ] Metric displays correct total user count
  - [ ] Number updates when users are added/deleted

- [ ] **Total Views**
  - [ ] Metric displays view count for current period
  - [ ] Number is accurate

### 3.2 Trend Metrics
- [ ] **Post Creation Trend**
  - [ ] Trend chart displays post creation over time
  - [ ] Chart is readable and accurate
  - [ ] Time period selector works (7 days, 30 days, etc.)

### 3.3 Partition Metrics
- [ ] **Posts by Status**
  - [ ] Partition chart shows posts grouped by status
  - [ ] Shows: Draft, Published, Scheduled counts
  - [ ] Percentages are accurate

- [ ] **Posts by Category**
  - [ ] Partition chart shows posts grouped by category
  - [ ] All categories are represented
  - [ ] Percentages are accurate

### 3.4 Dashboard Loading
- [ ] Dashboard loads within 3 seconds
- [ ] All metrics load correctly
- [ ] No errors appear in browser console
- [ ] Dashboard is responsive on different screen sizes

---

## 4. Custom Actions

### 4.1 Bulk Post Actions
- [ ] **Bulk Publish**
  - [ ] Can select multiple draft posts
  - [ ] "Publish Posts" action is available
  - [ ] Action publishes selected posts
  - [ ] Success message appears
  - [ ] Posts status changes to published

- [ ] **Bulk Feature**
  - [ ] Can select multiple posts
  - [ ] "Feature Posts" action is available
  - [ ] Action marks selected posts as featured
  - [ ] Success message appears
  - [ ] Posts are marked as featured

- [ ] **Bulk Export** (if implemented)
  - [ ] Can select multiple posts
  - [ ] "Export Posts" action is available
  - [ ] CSV file is downloaded
  - [ ] File contains correct data

### 4.2 Bulk Comment Actions
- [ ] **Bulk Approve**
  - [ ] Can select multiple pending comments
  - [ ] "Approve Comments" action is available
  - [ ] Action approves selected comments
  - [ ] Success message appears
  - [ ] Comments status changes to approved

- [ ] **Bulk Reject**
  - [ ] Can select multiple comments
  - [ ] "Reject Comments" action is available
  - [ ] Action rejects selected comments
  - [ ] Success message appears
  - [ ] Comments status changes to rejected

---

## 5. Filters & Search

### 5.1 Post Filters
- [ ] **Status Filter**
  - [ ] Can filter posts by status (draft, published, scheduled)
  - [ ] Filter works correctly
  - [ ] Results update immediately

- [ ] **Category Filter**
  - [ ] Can filter posts by category
  - [ ] Filter shows all categories
  - [ ] Filter works correctly

- [ ] **Date Range Filter**
  - [ ] Can filter posts by date range
  - [ ] Date picker works
  - [ ] Filter works correctly

- [ ] **Author Filter**
  - [ ] Can filter posts by author
  - [ ] Filter shows all authors
  - [ ] Filter works correctly

### 5.2 Comment Filters
- [ ] **Status Filter**
  - [ ] Can filter comments by status (pending, approved, rejected, spam)
  - [ ] Filter works correctly

- [ ] **Date Range Filter**
  - [ ] Can filter comments by date range
  - [ ] Filter works correctly

### 5.3 Search Functionality
- [ ] **Global Search**
  - [ ] Global search bar is visible
  - [ ] Can search across all resources
  - [ ] Search results are relevant
  - [ ] Can navigate to resource from search results

- [ ] **Resource Search**
  - [ ] Can search within Post resource
  - [ ] Can search within User resource
  - [ ] Can search within Category resource
  - [ ] Search finds correct results

- [ ] **Relationship Search**
  - [ ] When selecting category for post, can search categories
  - [ ] When selecting tags for post, can search tags
  - [ ] Relationship search works correctly

---

## 6. Performance

### 6.1 Response Times
- [ ] **Resource Lists**
  - [ ] Post list loads within 2 seconds
  - [ ] User list loads within 2 seconds
  - [ ] Category list loads within 2 seconds
  - [ ] Comment list loads within 2 seconds
  - [ ] Media list loads within 2 seconds

- [ ] **Dashboard**
  - [ ] Dashboard loads within 3 seconds
  - [ ] All metrics load within 3 seconds

- [ ] **Resource Detail Pages**
  - [ ] Post detail page loads within 2 seconds
  - [ ] User detail page loads within 2 seconds

### 6.2 Query Performance
- [ ] No N+1 query issues (check browser network tab)
- [ ] Database queries are optimized
- [ ] No duplicate queries

### 6.3 Media/Images
- [ ] Images load correctly
- [ ] Thumbnails are generated and displayed
- [ ] Image uploads don't cause timeouts
- [ ] Large images are handled gracefully

---

## 7. User Experience

### 7.1 Navigation
- [ ] Sidebar navigation works correctly
- [ ] Can navigate between resources easily
- [ ] Breadcrumbs are accurate
- [ ] Back button works correctly

### 7.2 Forms
- [ ] Form validation works
- [ ] Error messages are clear and helpful
- [ ] Required fields are marked
- [ ] Form submission works correctly
- [ ] Success messages appear after actions

### 7.3 Responsive Design
- [ ] Nova interface works on desktop (1920x1080)
- [ ] Nova interface works on tablet (768x1024)
- [ ] Nova interface works on mobile (375x667)
- [ ] All features are accessible on mobile

### 7.4 Accessibility
- [ ] Keyboard navigation works
- [ ] Screen reader compatibility (if applicable)
- [ ] Color contrast is sufficient
- [ ] Focus indicators are visible

---

## 8. Error Handling

### 8.1 Error Messages
- [ ] Error messages are user-friendly
- [ ] Error messages provide helpful information
- [ ] 404 errors show appropriate message
- [ ] 403 errors show appropriate message
- [ ] 500 errors show appropriate message (in staging)

### 8.2 Edge Cases
- [ ] Empty states are handled (no posts, no users, etc.)
- [ ] Very long content is handled correctly
- [ ] Special characters in content work correctly
- [ ] Concurrent edits are handled (if applicable)

---

## 9. Integration

### 9.1 Frontend Integration
- [ ] Posts created in Nova appear on frontend
- [ ] Posts edited in Nova update on frontend
- [ ] Comments approved in Nova appear on frontend
- [ ] Media uploaded in Nova is accessible on frontend

### 9.2 Data Consistency
- [ ] Post counts are accurate
- [ ] Category post counts are accurate
- [ ] Tag post counts are accurate
- [ ] User post counts are accurate

---

## 10. Security

### 10.1 Authorization
- [ ] Users can only access resources they're authorized for
- [ ] Actions are restricted by role
- [ ] CSRF protection works

### 10.2 Data Protection
- [ ] Sensitive data is not exposed
- [ ] Passwords are not visible in forms
- [ ] User data is protected

---

## Issues Found

### Critical Issues (Blocking)
1. _________________________________________________
2. _________________________________________________
3. _________________________________________________

### High Priority Issues
1. _________________________________________________
2. _________________________________________________
3. _________________________________________________

### Medium Priority Issues
1. _________________________________________________
2. _________________________________________________
3. _________________________________________________

### Low Priority Issues / Suggestions
1. _________________________________________________
2. _________________________________________________
3. _________________________________________________

---

## Sign-off

**Tester Name:** _______________  
**Date:** _______________  
**Overall Status:** [ ] Pass [ ] Pass with Issues [ ] Fail

**Comments:**
_________________________________________________
_________________________________________________
_________________________________________________

---

## Notes
- Test with different browsers: Chrome, Firefox, Safari, Edge
- Test with different user roles
- Document any unexpected behavior
- Take screenshots of issues
- Note any performance concerns


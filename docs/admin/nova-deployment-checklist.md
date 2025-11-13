# Laravel Nova Deployment Checklist

This checklist guides you through deploying and monitoring the Laravel Nova integration for the Tech News Platform.

## Pre-Deployment Preparation

### 1. Environment Configuration

- [ ] Verify Nova license key is set in `.env`:
  ```bash
  NOVA_LICENSE_KEY=your-license-key-here
  ```

- [ ] Confirm environment variables are correct:
  ```bash
  APP_ENV=staging  # or production
  APP_DEBUG=false  # MUST be false for production
  APP_URL=https://your-domain.com
  NOVA_APP_NAME="Tech News Admin"
  ```

- [ ] Verify database configuration is correct
- [ ] Confirm mail settings for notifications
- [ ] Check cache and queue drivers are production-ready

### 2. Code Verification

- [ ] All Nova tests passing:
  ```bash
  php artisan test --filter=Nova
  ```

- [ ] All application tests passing:
  ```bash
  php artisan test
  ```

- [ ] Code formatted with Pint:
  ```bash
  vendor/bin/pint --dirty
  ```

- [ ] No uncommitted changes in version control
- [ ] Latest code merged to deployment branch

### 3. Backup Strategy

- [ ] Database backup created
- [ ] Application files backed up
- [ ] `.env` file backed up securely
- [ ] Rollback plan documented and tested

## Staging Deployment

### 1. Deploy to Staging

```bash
# Run the staging deployment script
./deploy-staging.sh
```

### 2. Verify Staging Installation

- [ ] Application loads without errors
- [ ] Nova admin panel accessible at `/nova`
- [ ] Login works with test admin account
- [ ] Dashboard displays correctly with metrics
- [ ] All resources load (Posts, Users, Categories, etc.)

### 3. Functional Testing

#### Authentication & Authorization
- [ ] Admin user can access all resources
- [ ] Editor user has appropriate access
- [ ] Author user can only access own posts
- [ ] Regular user cannot access Nova
- [ ] Logout works correctly

#### Resource Management
- [ ] Create new post with all fields
- [ ] Edit existing post
- [ ] Delete post (with confirmation)
- [ ] Upload featured image
- [ ] Assign categories and tags
- [ ] Schedule post for future publication

#### User Management
- [ ] Create new user
- [ ] Edit user profile
- [ ] Change user role
- [ ] Update user status
- [ ] View user's posts

#### Comment Moderation
- [ ] View pending comments
- [ ] Approve comments (single and bulk)
- [ ] Mark comments as spam
- [ ] Delete comments

#### Media Library
- [ ] Upload new media
- [ ] View media details
- [ ] Edit media metadata (alt text, title)
- [ ] Filter media by type
- [ ] Search media files

#### Custom Actions
- [ ] Bulk publish posts
- [ ] Bulk feature posts
- [ ] Export posts to CSV
- [ ] Approve comments in bulk

#### Filters
- [ ] Filter posts by status
- [ ] Filter posts by category
- [ ] Filter posts by author
- [ ] Filter posts by date range
- [ ] Filter users by role
- [ ] Filter comments by status

#### Search
- [ ] Global search works
- [ ] Search posts by title/content
- [ ] Search users by name/email
- [ ] Search categories
- [ ] Search tags

#### Dashboard Metrics
- [ ] Total Posts metric displays correctly
- [ ] Total Users metric displays correctly
- [ ] Total Views metric displays correctly
- [ ] Posts Per Day trend chart works
- [ ] Posts By Status partition chart works
- [ ] Posts By Category partition chart works

#### Custom Tools
- [ ] Maintenance Mode tool works
  - [ ] Enable maintenance mode
  - [ ] Set custom message
  - [ ] Whitelist IP addresses
  - [ ] Disable maintenance mode

- [ ] Cache Manager tool works
  - [ ] Clear application cache
  - [ ] Clear route cache
  - [ ] Clear config cache
  - [ ] Clear view cache
  - [ ] Clear all caches

- [ ] System Health tool works
  - [ ] Database status displays
  - [ ] Queue status displays
  - [ ] Storage usage displays
  - [ ] Recent errors display
  - [ ] Auto-refresh works

### 4. Performance Testing

- [ ] Page load times acceptable (<2s for most pages)
- [ ] Resource index pages load quickly with pagination
- [ ] Search responds quickly (<1s)
- [ ] Dashboard metrics load without timeout
- [ ] Image uploads complete successfully
- [ ] No N+1 query issues (check logs)

### 5. User Acceptance Testing

- [ ] Admin users test all workflows
- [ ] Editor users test content management
- [ ] Author users test post creation
- [ ] Gather feedback on usability
- [ ] Document any issues or concerns
- [ ] Verify all feedback addressed

## Production Deployment

### 1. Pre-Production Checklist

- [ ] All staging tests passed
- [ ] User acceptance testing completed
- [ ] Performance benchmarks met
- [ ] Security review completed
- [ ] Backup verified and tested
- [ ] Rollback plan ready
- [ ] Deployment window scheduled
- [ ] Team notified of deployment

### 2. Deploy to Production

```bash
# Run the production deployment script
./deploy-production.sh
```

### 3. Immediate Post-Deployment Verification (First 15 minutes)

- [ ] Application loads without errors
- [ ] Nova admin panel accessible
- [ ] Login works with production admin account
- [ ] Dashboard displays correctly
- [ ] Create test post (then delete)
- [ ] Check error logs for issues:
  ```bash
  tail -f storage/logs/laravel.log
  ```

### 4. Critical Path Testing (First Hour)

- [ ] Admin login and navigation
- [ ] Create/edit/delete post
- [ ] Upload media
- [ ] Moderate comments
- [ ] User management
- [ ] Search functionality
- [ ] Custom actions work
- [ ] Tools accessible

## Monitoring Period (48 Hours)

### Hour 1-4: Intensive Monitoring

Check every 30 minutes:

- [ ] Error logs for exceptions:
  ```bash
  tail -100 storage/logs/laravel.log | grep ERROR
  ```

- [ ] Application performance:
  ```bash
  # Check response times in logs
  # Monitor server resources (CPU, memory, disk)
  ```

- [ ] Nova-specific errors:
  ```bash
  grep -i "nova" storage/logs/laravel.log | tail -50
  ```

- [ ] User activity (check activity logs in Nova)

### Hour 4-24: Regular Monitoring

Check every 2-4 hours:

- [ ] Error log review
- [ ] Performance metrics
- [ ] User feedback
- [ ] Database performance
- [ ] Queue status (if using queues)
- [ ] Storage usage

### Hour 24-48: Ongoing Monitoring

Check every 4-8 hours:

- [ ] Error trends
- [ ] Performance trends
- [ ] User adoption metrics
- [ ] Feature usage statistics
- [ ] Gather user feedback

## Monitoring Commands

### Check Application Health

```bash
# View recent errors
tail -100 storage/logs/laravel.log

# Check Nova routes are registered
php artisan route:list | grep nova

# Verify database connection
php artisan tinker
>>> DB::connection()->getPdo();

# Check queue status (if applicable)
php artisan queue:failed

# View cache status
php artisan cache:table
```

### Performance Monitoring

```bash
# Check slow queries (if query logging enabled)
grep "slow query" storage/logs/laravel.log

# Monitor disk usage
df -h

# Check memory usage
free -m

# View active processes
ps aux | grep php
```

### Nova-Specific Checks

```bash
# Verify Nova assets are published
ls -la public/vendor/nova

# Check Nova version
composer show laravel/nova

# Test Nova API endpoint
curl -I https://your-domain.com/nova-api/
```

## Performance Metrics to Track

### Response Times
- Dashboard load time: Target <2s
- Resource index load time: Target <1.5s
- Resource detail load time: Target <1s
- Search response time: Target <1s
- Action execution time: Target <3s

### Database Queries
- Queries per request: Target <50
- Query execution time: Target <100ms average
- N+1 queries: Target 0

### Resource Usage
- Memory usage: Monitor for leaks
- CPU usage: Should be stable
- Disk usage: Monitor growth rate
- Cache hit rate: Target >80%

## Common Issues and Solutions

### Issue: Nova not loading / 404 errors

**Solutions:**
1. Verify Nova assets published:
   ```bash
   php artisan nova:publish --force
   ```

2. Clear and rebuild caches:
   ```bash
   php artisan config:clear
   php artisan route:clear
   php artisan cache:clear
   php artisan config:cache
   php artisan route:cache
   ```

3. Check NovaServiceProvider is registered in `bootstrap/providers.php`

### Issue: Authorization errors

**Solutions:**
1. Verify user roles in database
2. Check policy registration in `AuthServiceProvider`
3. Clear config cache: `php artisan config:clear`
4. Review `Nova::auth()` gate in `NovaServiceProvider`

### Issue: Slow performance

**Solutions:**
1. Check for N+1 queries in logs
2. Verify eager loading in resource `indexQuery` methods
3. Check database indexes are present
4. Enable query caching for metrics
5. Optimize images and assets

### Issue: File upload failures

**Solutions:**
1. Check storage permissions: `chmod -R 775 storage`
2. Verify disk configuration in `config/filesystems.php`
3. Check PHP upload limits in `php.ini`
4. Ensure storage link exists: `php artisan storage:link`

### Issue: Dashboard metrics not loading

**Solutions:**
1. Check metric cache configuration
2. Verify database queries in metric classes
3. Check for timeout issues
4. Review error logs for exceptions

## Rollback Procedure

If critical issues are discovered:

### 1. Immediate Rollback

```bash
# Restore database backup
cp database/backups/production_backup_YYYYMMDD_HHMMSS.sqlite database/database.sqlite

# Revert code to previous version
git checkout previous-stable-tag

# Clear caches
php artisan config:clear
php artisan route:clear
php artisan cache:clear

# Rebuild caches
php artisan config:cache
php artisan route:cache
```

### 2. Notify Stakeholders

- [ ] Inform team of rollback
- [ ] Document issues encountered
- [ ] Schedule post-mortem meeting
- [ ] Plan remediation steps

### 3. Post-Rollback Verification

- [ ] Application functioning normally
- [ ] All critical features working
- [ ] Users can access system
- [ ] No data loss occurred

## User Feedback Collection

### Methods

1. **Direct Feedback**
   - Schedule feedback sessions with admin users
   - Create feedback form in Nova (use Feedback tool)
   - Monitor support tickets/emails

2. **Usage Analytics**
   - Track Nova login frequency
   - Monitor resource usage patterns
   - Review activity logs for adoption

3. **Performance Feedback**
   - Ask about page load times
   - Gather input on UI responsiveness
   - Collect suggestions for improvements

### Feedback Questions

- [ ] Is Nova easier to use than the old admin panel?
- [ ] Are there any missing features?
- [ ] Is performance acceptable?
- [ ] Are there any confusing workflows?
- [ ] What improvements would you suggest?
- [ ] Are there any bugs or issues?

## Success Criteria

The deployment is considered successful when:

- [ ] Zero critical errors in 48-hour period
- [ ] Performance metrics meet targets
- [ ] User feedback is positive (>80% satisfaction)
- [ ] All core workflows functioning correctly
- [ ] No data loss or corruption
- [ ] Rollback not required
- [ ] Team trained and comfortable with Nova

## Post-Deployment Tasks

### Week 1
- [ ] Daily error log review
- [ ] Gather user feedback
- [ ] Address any minor issues
- [ ] Document lessons learned

### Week 2-4
- [ ] Weekly performance review
- [ ] Implement user-requested improvements
- [ ] Optimize slow queries
- [ ] Update documentation based on feedback

### Month 2+
- [ ] Monthly health check
- [ ] Review and archive old activity logs
- [ ] Plan feature enhancements
- [ ] Update Nova if new version available

## Documentation Updates

After successful deployment:

- [ ] Update README with Nova information
- [ ] Document any configuration changes
- [ ] Update user guides with screenshots
- [ ] Create video tutorials for common tasks
- [ ] Document troubleshooting procedures
- [ ] Update deployment runbook

## Sign-Off

### Staging Deployment
- Deployed by: _________________ Date: _________
- Tested by: _________________ Date: _________
- Approved by: _________________ Date: _________

### Production Deployment
- Deployed by: _________________ Date: _________
- Verified by: _________________ Date: _________
- Approved by: _________________ Date: _________

### 48-Hour Monitoring Complete
- Monitored by: _________________ Date: _________
- Issues found: _________________
- Resolution: _________________
- Final approval: _________________ Date: _________

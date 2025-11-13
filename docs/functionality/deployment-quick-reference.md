# Nova Deployment Quick Reference Card

**Quick access guide for deploying and monitoring the Laravel Nova integration**

---

## 🚀 Staging Deployment

```bash
# 1. Deploy to staging
./deploy-staging.sh

# 2. Verify Nova is accessible
curl -I https://staging.your-domain.com/nova

# 3. Run tests
php artisan test --filter=Nova

# 4. Access Nova
https://staging.your-domain.com/nova
```

**Default Admin Credentials** (staging):
- Email: admin@example.com
- Password: Check seeder or .env

---

## 🎯 Production Deployment

```bash
# 1. Verify environment
grep APP_ENV .env  # Should be "production"
grep APP_DEBUG .env  # Should be "false"
grep NOVA_LICENSE_KEY .env  # Should be set

# 2. Deploy to production
./deploy-production.sh

# 3. Verify immediately
curl -I https://your-domain.com/nova

# 4. Start monitoring
./scripts/monitor-nova-deployment.sh
```

---

## 📊 Monitoring Commands

### Quick Health Check
```bash
# Run monitoring script
./scripts/monitor-nova-deployment.sh

# Check error logs
tail -100 storage/logs/laravel.log | grep ERROR

# Check Nova errors
grep -i "nova" storage/logs/laravel.log | grep ERROR | tail -20
```

### Performance Check
```bash
# Check response time
time curl -I https://your-domain.com/nova

# Check disk usage
df -h

# Check memory usage
free -m  # Linux
vm_stat  # macOS
```

### Database Check
```bash
# Check database connection
php artisan tinker --execute="DB::connection()->getPdo();"

# Check database size
ls -lh database/database.sqlite

# Check query performance (if Telescope installed)
# Navigate to /telescope/queries
```

---

## 🔍 Troubleshooting

### Nova Not Loading
```bash
# Republish Nova assets
php artisan nova:publish --force

# Clear and rebuild caches
php artisan config:clear
php artisan route:clear
php artisan cache:clear
php artisan config:cache
php artisan route:cache
```

### Authorization Errors
```bash
# Clear config cache
php artisan config:clear

# Check user role in database
php artisan tinker
>>> User::find(1)->role
```

### Slow Performance
```bash
# Check for N+1 queries
grep "select \* from" storage/logs/laravel.log | sort | uniq -c | sort -rn

# Clear all caches
php artisan cache:clear
php artisan view:clear
php artisan route:clear
php artisan config:clear
```

---

## 📋 Monitoring Schedule

### First 4 Hours (Intensive)
- ✅ Every 30 minutes: Run monitoring script
- ✅ Check error logs
- ✅ Verify critical paths

### Hours 4-24 (Regular)
- ✅ Every 2-4 hours: Run monitoring script
- ✅ Review performance metrics
- ✅ Check user feedback

### Hours 24-48 (Ongoing)
- ✅ Every 4-8 hours: Run monitoring script
- ✅ Review trends
- ✅ Gather feedback

---

## 🔄 Rollback Procedure

```bash
# 1. Restore database
cp database/backups/production_backup_YYYYMMDD_HHMMSS.sqlite database/database.sqlite

# 2. Revert code
git checkout previous-stable-tag

# 3. Clear caches
php artisan config:clear
php artisan route:clear
php artisan cache:clear

# 4. Rebuild caches
php artisan config:cache
php artisan route:cache

# 5. Verify
curl -I https://your-domain.com
```

---

## 📞 Emergency Contacts

- **Development Lead**: _________________
- **Operations Lead**: _________________
- **On-Call Support**: _________________

---

## 📚 Documentation Links

- **Full Deployment Checklist**: `docs/nova-deployment-checklist.md`
- **UAT Checklist**: `docs/nova-uat-checklist.md`
- **Performance Monitoring**: `docs/nova-performance-monitoring.md`
- **Deployment Readiness**: `docs/nova-deployment-readiness.md`
- **User Guide**: `docs/admin/nova-user-guide.md`
- **Troubleshooting**: `docs/admin/nova-troubleshooting.md`

---

## ✅ Pre-Deployment Checklist

- [ ] All tests passing
- [ ] Environment configured
- [ ] Nova license key set
- [ ] Database backed up
- [ ] Team notified
- [ ] Rollback plan ready

---

## 🎯 Success Criteria

- ✅ Zero critical errors in 48 hours
- ✅ Response time <2s
- ✅ User satisfaction >80%
- ✅ All features functional
- ✅ No rollback required

---

**Keep this card handy during deployment!**

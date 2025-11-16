## Phase 18 – Deployment & Infra Checklist

 - [ ] Dockerfiles + docker-compose for services
 - [ ] Nginx config + SSL + compression + headers
 - [x] Env configurations (dev/staging/prod) and docs
 - [x] CI/CD workflows for tests/build
 - [x] Deployment scripts: migrate/cache/assets/restart queues
 - [x] Backups: command + schedule + retention
   - Implemented `backup:database` command for sqlite with 30-day retention; scheduled daily at 03:10
 - [ ] Monitoring/alerts: uptime, error rates, performance
 
 Notes:
 - .env.* files committed for environments
 - GitHub Actions CI workflow present: tests, Pint, audit
 - Deployment scripts exist in repo (deploy.sh, deploy-staging.sh, deploy-production.sh)

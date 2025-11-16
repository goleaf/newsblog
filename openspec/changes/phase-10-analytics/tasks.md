## Phase 10 – Analytics Checklist

 - [x] AnalyticsService: article/user/traffic metrics
 - [x] AnalyticsController: dashboard + performance/traffic/user endpoints + export
 - [x] Views: dashboards and charts
 - [x] Caching: daily metrics, article metrics, dashboard
 - [x] Jobs: daily/weekly/monthly aggregation and cleanup
   - Implemented jobs under app/Jobs/Analytics and scheduled in routes/console.php
 
 Notes:
 - Admin AnalyticsController provides charts and metrics; DashboardService/MonitoringService compute counts; caching used for dashboard

# M12.4 Acceptance

- Liveness: `/api/v1/health/live`
- Compatibility: `/api/v1/health`
- Readiness: `/api/v1/health/ready`
- Readiness checks PostgreSQL and Laravel cache.
- Dependency failure returns HTTP 503.
- Feature tests cover live, compatibility, and healthy readiness.
- External monitoring vendors and M12.5 rollback/backup are out of scope.

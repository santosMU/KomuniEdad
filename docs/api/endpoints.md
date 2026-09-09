# Backend API

## Initialization Endpoints

### GET /api/health

Purpose: verifies that the Next.js backend process is running.

Response:

```json
{
  "status": "ok",
  "service": "phase2-backend",
  "timestamp": "ISO-8601 timestamp"
}
```

### GET /api/db-health

Purpose: verifies connectivity from the backend to Supabase/PostgreSQL by
calling the `health_check()` database function created by
`supabase/schema.sql`.

Successful response:

```json
{
  "status": "ok",
  "database": {
    "status": "ok",
    "checked_at": "database timestamp"
  }
}
```

## Domain Endpoints

Add endpoints only after their corresponding requirements and tables have been
approved.

| Method | Route | Requirement | Purpose |
| --- | --- | --- | --- |
| TBD | TBD | TBD | TBD |

# Source security audit

Audit date: 2026-09-29. Scope: browser, Laravel routes/middleware/controllers/service, Blade, all eight Supabase migrations. Observations are limited to reviewed code and executed tests.

| ID | File / function | Risk or observation | Verification / result | Action |
|---|---|---|---|---|
| AUD-01 | CommunityController::login | Auth cookie outlived token | AUTH-23 regression; bounded expiry passes | BUG-001 |
| AUD-02 | profile.blade.php | Nested forms | HYP-02 parses form nesting; passes | BUG-002 |
| AUD-03 | CommunitySession::handle | Client token must be verified remotely | Mocked invalid/expired token gives 401; disabled profile 403; temporary outage 503 preserves session | No local JWT decoding used as authority |
| AUD-04 | Community::api | REST query parameters and JSON RPC arguments, not SQL concatenation | SQLI-01..14; local DB literal round trips | Retain typed UUIDs and parameter flow |
| AUD-05 | migrations / RPC functions | SECURITY DEFINER functions require explicit authorization | DB cross-user and coordinator checks; grant/revoke and fixed search_path reviewed | Do not grant private helper execution |
| AUD-06 | migrations / SQL construction | No dynamic SQL construction found | EXECUTE occurrences are trigger declarations or GRANT/REVOKE EXECUTE, not executable strings | Risk reduced, not proof of immunity |
| AUD-07 | Blade templates | User text must be encoded in HTML/attributes | XSS-01..13; no untrusted raw Blade output found | Keep double-brace escaping |
| AUD-08 | community.js search/updatePage | innerHTML consumes escaped server-rendered Blade; DOMParser consumes same-origin HTML | XSS-11 and browser search | Treat future raw HTML paths as security-sensitive |
| AUD-09 | JsonFormResponse::relativeTarget | Double-slash redirect path | ERROR-05 reproduced and passes after normalization | BUG-009 |
| AUD-10 | routes/web.php / CSRF | State changes are POST with web CSRF | Real middleware enforced in CSRF-01/02; invalid tokens 419 | No CSRF exemptions added |
| AUD-11 | api/index.php / cache.php | Array cache loses serverless throttle counters | Source confirms forced array; AUTH-25 persists counters through fresh applications with file limiter | BUG-013; shared production store still required |
| AUD-12 | Community::api | Upstream errors may disclose SQL or server details | ERROR-01..07 use synthetic secret markers; friendly responses verified | Allowlist domain messages only |
| AUD-13 | public JS / browser storage | Auth tokens must not be JavaScript-readable | Dedicated HttpOnly cookie; localStorage only sidebar preference | No token localStorage introduced |
| AUD-14 | SecurityHeaders | CSP permits local scripts and known image/font/style sources | HYP-04; local scanner retest | Inline styles remain an explicit limitation |
| AUD-15 | production config | Debug and cookie settings | .env.production.example provided; entry point forces debug false and secure encrypted cookie session | Deployment values still need verification |
| AUD-16 | activities constraints | Direct RPC bypasses Laravel field limits | DB-24..26 reject oversized values | Migration 008 |
| AUD-17 | Community::demo / demo role route | Demo must not elevate hosted users | demo requires local/testing environment; existing test denies role switch in live mode | Set APP_ENV=production and KOMUNIEDAD_DEMO=false |
| AUD-18 | error logs / evidence | Raw scanner captures contain cookie values | Only sanitized reports committed; raw runtime ignored | Never commit storage/phase4-private |

## Limits

No live synthetic account credentials were used in this Phase 4 run. PGlite tests use real SQL/RLS with an auth.uid shim; they cannot verify hosted Auth settings, network boundaries or simultaneous sessions. The service does not implement refresh tokens. Expired sessions require sign-in. The login remember-me checkbox currently does not extend the token lifetime and should not be represented as persistent sign-in.

Reviewed searches found no shell execution, eval, raw SQL builders or untrusted raw Blade interpolation in application paths. Static searches alone are not security proof.


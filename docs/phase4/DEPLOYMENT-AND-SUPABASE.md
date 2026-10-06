# Remaining deployment and Supabase steps

## 1. Current Phase 4 release state

The final tested source/evidence baseline is `a1ca211af34e685e758741603ec7c6f149061f54` on `feature/laravel-phase4`. GitHub Actions run 51 passed on that exact commit. Phase 3 was not modified or merged.

If application code changes after this baseline, rerun the complete verification commands in section 5 and record the new tested commit. Documentation-only synchronization commits may follow without changing the application-source baseline. Do not force-push Phase 4.

## 2. Supabase migration review

Use the intended project. Review existing migrations 001 through 007 and do not blindly rerun them. Migration 008 depends on the cash-payment schema from 007.

First run this read-only preflight in SQL Editor:

```sql
select activity_id, capacity, length(requirements) as requirements_length, fee
from public.activities
where capacity > 10000 or length(requirements) > 2000 or fee > 100000;

select conname
from pg_constraint
where conrelid = 'public.activities'::regclass
and conname in ('activity_capacity_upper_bound',
               'activity_requirements_length',
               'activity_fee_upper_bound');
```

If invalid rows exist, review and correct their intended values. Do not delete records to make migration succeed. If all three constraints already exist, inspect their definitions rather than running the migration again.

Otherwise review and run supabase/migrations/202609290008_phase4_validation.sql once. It adds constraints, not sample users. A failure rolls back the transaction. Capture sanitized success/constraint evidence.

## 3. Synthetic accounts and role checks

Use existing approved synthetic accounts if available: senior A/B, coordinator A/B, administrator. Create missing test users through Supabase Authentication. Do not put passwords, JWTs or keys in SQL files, screenshots or Git. Profiles should be provisioned by the account trigger; verify each role and active status. Use an existing authorized administrator workflow to assign roles and verification.

In the live Laravel application with KOMUNIEDAD_DEMO=false:

- Senior A enrolls; senior B cannot see or withdraw A's private enrollment.
- Coordinator A creates an activity; coordinator B cannot edit it, manage attendance/payment or read its roster.
- Admin verifies a senior and makes an authorized correction with an audit reason.
- Paid activity blocks attendance until staff records cash received.
- Eligible completed/attended senior submits feedback once.
- Invalid/expired credentials fail safely; disabled account cannot access protected endpoints.
- Sign out and attempt a protected URL.
- Read records again in a new session to verify persistence.
- Capture expected/actual/status and redacted evidence. Never mark a test passed merely because a row exists.

RLS tests in the repository are local evidence, not proof of hosted policy deployment.

## 4. Vercel / production configuration

Deploy the Phase 4 branch intentionally. A Git push does not prove the production domain deployed that branch.

Set APP_ENV=production, APP_DEBUG=false, HTTPS APP_URL, a valid secret APP_KEY, KOMUNIEDAD_DEMO=false, SUPABASE_URL and SUPABASE_ANON_KEY (publishable/anon only). Keep session encryption, HttpOnly and Secure enabled. Do not expose a service-role key to the browser or application.

Configure a shared cache for every serverless instance. The code now honors CACHE_STORE and CACHE_LIMITER. The example uses Redis; it requires reachable Redis credentials and the PHP Redis extension. Verify the runtime extension before selecting phpredis. Never assume local file or array caches provide global serverless throttling. Do not fall back to array while claiming the limiter is enabled.

On an authorized synthetic/staging target, verify repeated separate requests reach 429 and counters persist across instances. Record Retry-After and safe errors. No shared cache was provisioned by this work.

## 5. Final verification

Run php artisan test, npm run test:db, node --check public/community.js, composer audit and npm audit --omit=dev. Preserve actual outputs.

Repeat the narrow hosted passive scan using PHASE4_TARGET_URL=https://komuni-edad.vercel.app and tests/phase4-scan-plan.mjs. Review the plan before execution, retain raw reports only in ignored storage/phase4-private, then run tests/phase4-sanitize-scan.mjs. Do not active-scan production.

Complete the human usability forms and leader contribution ratings. Record the final commit SHA and deployment ID. Report hosted failures honestly and retest after fixes.

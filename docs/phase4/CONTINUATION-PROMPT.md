# Continuation prompt

Copy the following into the next session. A regular ChatGPT chat may need the repository files uploaded or accessible; it must not pretend it can read local paths.

---

Continue KomuniEdad Phase 4 Security and Testing. Work in D:\02_Projects\06_ProjectsDev\KomuniEdad on feature/laravel-phase4 only. Repository: https://github.com/santosMU/KomuniEdad. Do not modify or merge Phase 3. Preserve existing uncommitted changes.

Read docs/phase4/README.md, SECURITY-AND-TESTING-REPORT.md, BUG-LOG.md, TEST-CASES.md, FUNCTIONAL-TEST-MATRIX.md, EVIDENCE-REGISTER.md and DEPLOYMENT-AND-SUPABASE.md first. The Phase 4 section in docs/PHASES-1-TO-4-CHECKLIST.md is updated. Earlier phases retain their historical status.

## Verified state on 2026-09-29

- Current tested source/evidence baseline: `a1ca211af34e685e758741603ec7c6f149061f54` (`security: finish Phase 4 local verification and evidence`). GitHub Actions run 51 passed on this exact commit. Verify whether later commits are documentation-only or include application changes before relying on this baseline.
- Final local tests: 175 PHP tests passed, 603 assertions; 26 additional named PGlite checks passed, plus the existing database scenario suite. JS syntax passed. Composer audit found no advisories. npm audit --omit=dev found zero production vulnerabilities. Outputs are under docs/phase4/evidence/automated/final-*.txt.
- PHP tests use demo state or mocked Supabase HTTP. PGlite runs SQL/RLS with an auth.uid shim. These are not hosted Auth/RLS or concurrent multi-connection evidence.
- Existing fixes include bounded auth-cookie expiry, separate profile/logout forms, live UUID validation, safe AJAX redirects, restored search and profile links, optional field defaults, mobile navigation scope and migration 008 database limits.
- Latest changes strengthen CSP with script-src self, move password toggles into community.js, use root-relative logos, add verified stylesheet SRI, remove X-Powered-By, preserve configured CACHE_STORE in api/index.php and add CACHE_LIMITER configuration. A fresh-application limiter regression passes using a file cache. Production still requires a real shared backend.
- The repository has complete draft Phase 4 report, case tables, all 27 FR mappings, source audit, bug log, evidence register, usability plan/results form, contribution template and deployment/Supabase instructions.
- Seven current screenshots cover registration errors, gardening search, three discovery viewport sizes and stored/reflected XSS. Human feedback has not been collected.

## Scanner evidence

ZAP 2.17.0 performed passive unauthenticated GET /login, /register and /health only. The authorized hosted target is https://komuni-edad.vercel.app. Do not active-scan production or scan Supabase/Pexels/jsDelivr infrastructure.

Hosted initial distinct alerts: 3 medium, 4 low, 3 informational, zero high. Local before: 4 medium, 2 low, 1 informational. Local retest: 1 medium (style unsafe-inline), 1 low (XSRF-TOKEN intentionally JavaScript-readable), 1 informational (session detection). Access-token HttpOnly is separately tested. Hosted fixes are not verified deployed. Sanitized reports are in docs/phase4/evidence/scanner; raw cookie-bearing reports stay in ignored storage/phase4-private.

## Immediate priorities

1. Configure shared cache for Vercel throttling. api/index.php no longer overwrites CACHE_STORE, and config/cache.php supports CACHE_LIMITER. Array and serverless local files are insufficient across instances. .env.production.example uses Redis placeholders; verify PHP client availability and reachable credentials. Do not claim rate limiting fixed in production until verified across independent requests/instances.
2. Review/apply supabase/migrations/202609290008_phase4_validation.sql after preflight. It adds capacity <=10000, requirements <=2000 characters and fee <=100000. Hosted installation has not been done. Do not blindly rerun prior migrations or delete records.
3. Deploy Phase 4 deliberately, then verify live synthetic senior A/B, coordinator A/B and admin workflows, RLS ownership, disabled users, token expiry, cash attendance gate, feedback, waitlist persistence, audit logs and counts. No live credentials are embedded in this handoff.
4. Finish staff mobile drawer/keyboard/zoom/full role workflow screenshots and real-device checks. Three viewport widths currently cover discovery only. Reports render in tests but full aggregate reconciliation remains pending. Concurrent capacity testing remains pending.
5. Collect actual feedback from at least three human testers and actual leader contribution ratings. Keep pending rows until supplied. Never invent feedback or PASS results.
6. Repeat hosted passive scan after deployment, triage findings, run regressions after any code changes, update report/checklist, and preserve honest limitations.

## Tools and constraints

PHP is C:\xampp\php\php.exe. Composer PHAR is C:\Users\User\Documents\Codex\2026-09-15\b\work\composer.phar. npm.cmd works. Portable ZAP is storage/phase4-tools/zap/ZAP_2.17.0/zap-2.17.0.jar; Java is C:\Program Files\Microsoft\jdk-21.0.12.8-hotspot\bin\java.exe. Do not commit scanner binaries, jbrofuzz, local .env, raw reports, dependencies or secrets. Earlier local agent Git writes had Windows permission/network issues, but the final local verification work was subsequently committed and pushed by the user. Do not assume any future local working tree is clean; verify Git state first.

The current local demo may still run on http://127.0.0.1:8044. Verify process state before restarting. Its data is synthetic browser-session state, not hosted persistence. Browser clicks sometimes mis-targeted in the automation provider; keyboard activation worked. Viewport override was reset.

Do not call Phase 4 fully complete while the hosted setup, human testing and other stated gaps remain. Prioritize the outstanding high-severity deployment rate-limit issue. Keep responses concise, no emojis or em dashes. Check usage periodically and refresh this continuation prompt before 20% remaining.

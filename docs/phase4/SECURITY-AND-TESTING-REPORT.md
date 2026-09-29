# KomuniEdad Phase 4 Security & Testing Report

## 1. Scope

Security and functional verification of the Phase 4 branch. Authorized targets: local demo at http://127.0.0.1:8044 and passive public-page inspection of https://komuni-edad.vercel.app. No direct Supabase infrastructure scan, destructive payload, active production scan or real-person data was used.

## 2. Environment

Windows, PHP 8.2.12, Laravel 12, Node 24.18.0, PGlite 0.5.8, ZAP 2.17.0. Local demo uses file sessions/cache, an ephemeral key, APP_DEBUG=false and synthetic browser data. PHP tests use array sessions and mocked Auth responses; AUTH-25 uses a unique file cache across fresh applications.

## 3. Branch and Commit

Branch: feature/laravel-phase4. Final tested source/evidence baseline: `a1ca211af34e685e758741603ec7c6f149061f54` (`security: finish Phase 4 local verification and evidence`). GitHub Actions run 51 completed successfully on that exact commit. Phase 3 was not modified or merged. Later documentation-only synchronization commits do not change the tested application source baseline.

## 4. Testing Methodology

Reproduce defects, add regression checks, apply fixes, run full PHP/database suites and JS syntax, audit dependencies, verify selected browser flows, and rerun passive scanning. PASS applies only to executed assertions. Test requirement rows overlap tests; they are not extra suite counts.

## 5. Tools Used

PHPUnit through artisan; Laravel Http::fake; actual Laravel CSRF middleware with its testing bypass disabled; local PostgreSQL through PGlite with auth.uid shim; browser automation; Composer/npm audits; ZAP passive automation. Nessus was not used.

## A. Input Validation Tests

All VAL-01..35 passed locally. The full field/input/expected/actual table is in [TEST-CASES](TEST-CASES.md#a-input-validation). HTTP 422 was asserted for invalid fields and malformed live UUID; missing demo activity/enrollment returned 404. Screenshot shows registration password errors. Server-side validation is tested independently of browser constraints.

## B. SQL Injection Tests

SQLI-01..14 passed within local scope. [Payload and result table](TEST-CASES.md#b-sql-injection). Harmless quote/OR markers were rejected as invalid email/UUID, returned no search matches, or persisted as literal text. Database checks independently round-trip writable fields.

Laravel sends structured query parameters and JSON RPC arguments. SQL functions use typed arguments/static SQL; review found no dynamic SQL string execution. GRANT EXECUTE and trigger EXECUTE FUNCTION are not dynamic SQL. This reduces injection risk; it does not establish immunity.

## C. Authentication Tests

AUTH-01..24 passed using local/mocked responses; AUTH-25 passed across fresh application instances with a persistent local limiter store. [Detailed table](TEST-CASES.md#c-authentication). Checks cover credentials, password rules, logout, protected pages, invalid/expired tokens, disabled profiles, session/CSRF rotation and cookie flags/lifetime.

Hosted Auth settings, actual token expiry, email confirmation, revocation behavior and shared-cache throttling remain unverified. Cookie expiry is bounded; no refresh-token flow is implemented.

## D. Authorization Tests

AUTHZ-01..30 passed locally, with AUTHZ-25 covered by DB-17. [Role/action table](TEST-CASES.md#d-authorization). Seniors cannot use staff/admin actions; coordinators cannot manage foreign activities or admin functions; admins can perform allowed corrections. Guest and disabled access is rejected. Local RLS/RPC checks independently cover cross-user isolation rather than relying only on Laravel 403 responses.

## E. XSS Tests

XSS-01..13 passed. [Payload/output table](TEST-CASES.md#e-xss). Blade encodes text and attributes; JSON-generated cards use escaped Blade HTML. Stored and reflected browser markers do not trigger an alert. Current history shows feedback scores but does not render comments. Future comment rendering must retain escaping.

## F. Functional Tests

All FR-01..27 map to executed local tests in [FUNCTIONAL-TEST-MATRIX](FUNCTIONAL-TEST-MATRIX.md). Assertions include role workflows, activity CRUD, enrollment, waitlists, withdrawal, attendance, cash payment, feedback and audit. Coverage is bounded: rendered report pages do not prove every aggregate, and sequential capacity tests do not prove multi-connection concurrency.

## G. Usability Tests

[Plan](USABILITY-TEST-PLAN.md) and [results form](USABILITY-RESULTS.md) prepared. Three human tester rows remain AWAITING HUMAN TESTER. Engineering checks found no document horizontal overflow on discovery at 375x812, 768x1024 and 1366x768. Automated observations are not human feedback.

## H. Vulnerability Scanner Results

ZAP 2.17.0, 2026-09-29, unauthenticated passive GET /login, /register, /health only. No spider, active attacks or third-party resource scan. Counts below are distinct alert entries, not unique exploitable vulnerabilities.

| Target / run | Critical / High | Medium | Low | Informational |
|---|---|---|---|---|
| Hosted initial | 0 / 0 | 3 | 4 | 3 |
| Local before final header fixes | 0 / 0 | 4 | 2 | 1 |
| Local retest | 0 / 0 | 1 | 1 | 1 |

| Alert | Assessment / action | Retest |
|---|---|---|
| Missing CSP, frame header, nosniff | Global middleware supplies headers | Not reported locally; hosted not redeployed/verified |
| CSP wildcard / script unsafe-inline | Explicit default-src and script-src self, external password handler | Not reported locally |
| CSP style unsafe-inline | Existing inline presentation requires it | Remains medium; open hardening limitation |
| SRI absent | Pinned stylesheet SHA-384 verified and applied | Not reported locally |
| Mixed-content logo | Root-relative image path | Not reported locally |
| X-Powered-By | Removed by middleware | Not reported locally; platform headers still need hosted check |
| Cookie no HttpOnly | Alert parameter is XSRF-TOKEN, intended for CSRF client use; it is not the access-token cookie | Low scanner alert retained; auth cookie HttpOnly separately tested |
| Session identification | Informational detection of session cookie | No vulnerability established |
| Cache-control/cache retrieval | Hosted initial informational findings | Local responses private/no-store; hosted verification pending |

Sanitized reports preserve alert names, locations and parameters while omitting request/response bodies, cookie values and raw evidence strings. Raw scans stay ignored. No high findings in this limited scan does not establish that all security risks are absent.

## I. Bugs Discovered and Corrected

[BUG-LOG](BUG-LOG.md) records BUG-001..013 and open issues. Twelve source fixes are committed in the Phase 4 branch, including cookie lifetime, forms, validation, missing controls, redirects, headers, resource integrity and DB bounds. Staff mobile drawer retest is still pending. BUG-013 remains partial until production shared-cache configuration is verified.

## J. Final Testing Summary

| Metric | Result |
|---|---|
| Counted automated test units | 201: 175 PHP cases plus 26 additional named DB checks |
| Passed | 201 |
| Failed in final counted runs | 0 |
| PHP assertions | 603 |
| Existing DB scenario suite | Passed additionally; not assigned an invented test count |
| JS syntax | Passed |
| Composer audit | No vulnerability advisories; local cache write warning did not prevent audit |
| npm audit --omit=dev | 0 vulnerabilities in production dependency scope |
| Fixed bugs | 12 local source fixes; BUG-013 partial |
| Remaining critical identified | 0 within tested scope |
| Remaining high | 1 deployment requirement: shared-cache rate limiting |
| Blocked/manual work | Hosted deployment/Auth/RLS/migration evidence, full browser workflows, concurrent capacity, report reconciliation, three human testers, contribution ratings |

Blocked work is not included in the 201 executed-unit denominator. Individual manual test counts remain undefined until the hosted/tester plans are executed.

## K. Conclusion

Local automated regressions pass and the public-page passive scan improved after fixes. Phase 4 is not fully complete: hosted verification, shared-cache setup, additional browser evidence, human feedback and contribution ratings remain required. No final submission-ready or production-security certification is claimed.


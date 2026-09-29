# Phase 4 bug and issue log

Date: 2026-09-29. Local fixes refer to the working tree based on 4cca23f. Hosted deployment is not verified.

| ID | Requirement / severity | Reproduction and root cause | Action | Status and evidence |
|---|---|---|---|---|
| BUG-001 | AUTH-23 / Medium | Login cookie used a five-year lifetime even though the access token expires sooner. | Bound lifetime to expires_in and configured session duration; session cookie when expiry absent. | FIXED LOCALLY; AUTH-21..24, final PHP log. Actual hosted token expiry not measured. |
| BUG-002 | Profile / Low | Logout form nested inside profile form. | Separate associated logout form. | FIXED LOCALLY; HYP-02. |
| BUG-003 | Headers / Medium | Initial hosted scan lacked CSP, frame protection and nosniff. | Global headers middleware and explicit resource policy. | FIXED LOCALLY; HYP-04 and ZAP retest. Hosted still unverified. |
| BUG-004 | VAL-30 / Low | Malformed route IDs could reach RPC rather than be rejected locally. | UUID middleware in live mode. | FIXED LOCALLY; VAL-30, ERROR-06; malformed IDs never sent to RPC. |
| BUG-005 | FUNC-07 / Medium | Omitted optional requirements broke activity detail rendering. Missing category description and announcement activity_id also caused demo errors. | Default optional values. | FIXED LOCALLY; functional-regressions-before.txt and final PHP suite. |
| BUG-006 | FUNC-10 / Medium | Search implementation existed but discovery page lacked controls. | Restore search/category/status/Clear controls. | FIXED LOCALLY; FUNC-10 and search screenshot. |
| BUG-007 | Navigation / Medium | Staff mobile drawer control hidden by CSS intended for seniors. Senior profile lacked history/announcement shortcuts. | Scope hidden controls to senior shell; add shortcuts and refresh mobile navigation after AJAX. | Source fixed; profile-link test passes. Staff mobile drawer full browser retest still needed. |
| BUG-008 | DB validation / Medium | Direct RPC accepted capacity above 10000; requirements and fee upper bounds also absent in schema. | Migration 008 adds three constraints. | FIXED LOCALLY; DB-24..26. Hosted migration pending. |
| BUG-009 | Redirect integrity / Medium | A double-slash path from a Referer produced a protocol-relative AJAX redirect. | Force one leading slash in PHP and normalize client URL. | FIXED LOCALLY; ERROR-05 and redirect-regression-before.txt. |
| BUG-010 | Auth layout / Low | Validation messages appeared beside a squeezed login/register panel. | Column layout for authentication content. | FIXED LOCALLY; validation screenshot. |
| BUG-011 | Resources / Low | Hosted logo generated as HTTP and icon stylesheet lacked integrity metadata. | Relative logo URL and verified SHA-384 stylesheet integrity. | FIXED LOCALLY; auth render test and ZAP retest. |
| BUG-012 | Information exposure / Low | PHP version exposed via X-Powered-By. | Remove header in middleware; recommend expose_php=Off. | FIXED LOCALLY in passive scan; upstream hosting may add headers. |
| BUG-013 | Rate limiting / High | api/index.php forced CACHE_STORE=array, losing counters between invocations and ignoring configured shared cache. | Preserve CACHE_STORE and expose CACHE_LIMITER. Fresh-application file-cache test proves limiter persistence when configured. | PARTIAL: code fixed; hosted shared backend configuration/verification BLOCKED. |
| ISSUE-014 | CSP styles / Medium scanner rating | Existing Blade uses inline styles. | Keep narrowly scoped style unsafe-inline for current UI compatibility. | OPEN hardening limitation; local ZAP still reports it. Scripts disallow unsafe-inline. |
| ISSUE-015 | Hosting / Unassessed | Final hosted Auth/RLS workflows, production config and multi-connection concurrency not executed. | Deployment checklist and evidence tasks prepared. | NEEDS LIVE SYNTHETIC EVIDENCE. |
| ISSUE-016 | Usability / Unassessed | No genuine human tester feedback supplied. | Three-tester plan and result forms prepared. | AWAITING HUMAN TESTERS. |

Confirmed source fixes do not imply hosted remediation. No critical defect was identified within the executed scope; this is not a claim that none exists. Fixed-bug counts exclude partial BUG-013 and open issues.


# KomuniEdad — Master Requirements and Submission Checklist: Phases 1–4

**Updated for Phase 3**  
**Branch reviewed:** `feature/laravel-phase3`  
**Date:** 23 September 2026  
**Stack:** Laravel 12, Blade, Bootstrap 5.3.8, JavaScript/Fetch, Supabase Auth, Supabase PostgreSQL

---

## How to use this checklist

Use the status labels below consistently:

| Status | Meaning |
|---|---|
| **DONE** | Implemented and supported by source or available evidence. |
| **LIVE VERIFIED** | Observed working through the running Laravel application against hosted Supabase. |
| **TESTED LOCALLY** | Verified by automated/local testing, but not necessarily by hosted end-to-end testing. |
| **PARTIAL** | Implementation exists, but acceptance evidence or full verification is incomplete. |
| **VERIFY** | Current state still needs direct verification. |
| **PENDING** | Work or required submission evidence is not yet completed. |
| **N/A** | Not implemented or not required for the current scope. |

Do not mark an unexecuted security or usability test as passed.

---

# Current Progress Dashboard

| Phase | Current state | Main remaining work |
|---|---|---|
| **Phase 1 — Proposal** | Core proposal, requirements, architecture and database planning exist. | Final consistency, client/policy confirmations, approval/submission evidence. |
| **Phase 2 — Backend & Database** | Laravel workflows, Supabase Auth, RLS/RPC, migrations, roles, hosted sample data and live authentication are working. | Full live authorization/concurrency checks and remaining policy confirmation. |
| **Phase 3 — Frontend & API** | Responsive Blade UI, JavaScript/Fetch, JSON responses, no-reload search/forms and live Supabase integration are implemented. | Final screenshots, browser Network evidence, final test output, contribution records and demo rehearsal. |
| **Phase 4 — Security & Testing** | Some validation, role, XSS and workflow tests already exist. | Formal security, SQL injection, authorization, usability, bug log, report and evidence. |

---

# Confirmed Building Blocks

- [x] **DONE** — Laravel application routes, controllers, services and middleware are implemented.
- [x] **DONE** — Supabase Auth integration is implemented.
- [x] **DONE** — Four database migration files are present after the base schema, including capacity/waitlist promotion logic.
- [x] **DONE** — Senior workflows are implemented.
- [x] **DONE** — Coordinator workflows are implemented.
- [x] **DONE** — Administrator workflows are implemented.
- [x] **DONE** — Blade templates, Bootstrap and project CSS are implemented.
- [x] **DONE** — Student-written JavaScript exists in `public/community.js`.
- [x] **DONE** — Laravel JSON responses support Fetch/AJAX behavior.
- [x] **LIVE VERIFIED** — Laravel successfully authenticated against hosted Supabase.
- [x] **LIVE VERIFIED** — Administrator workspace loaded using the live hosted project.
- [x] **LIVE VERIFIED** — Senior activity data loaded from hosted Supabase.
- [x] **LIVE VERIFIED** — Dynamic search for `digital` returned the correct activity without a full-page reload.
- [x] **LIVE VERIFIED** — Senior A showed completed and confirmed enrollment data.
- [x] **LIVE VERIFIED** — Senior B showed the expected waitlisted enrollment data.
- [x] **TESTED LOCALLY** — Interactive tests cover JSON search, enrollment/withdrawal, validation, role checks, service failure handling, waitlist promotion and publishable-key behavior.

---

# Phase 1 — Proposal and Agreed Scope

## Documents and planning

- [ ] **PARTIAL** — Final proposal contains the correct project title, team members, course, version and date.
- [ ] **PENDING** — Intended client organization is formally identified and validated if required by the instructor.
- [ ] **PARTIAL** — Problem statement and objectives match the final implementation.
- [x] **DONE** — Senior, coordinator and administrator roles are clearly distinguished.
- [ ] **PARTIAL** — Scope and exclusions remain consistent with the proposal.
- [ ] **PARTIAL** — Functional and non-functional requirements have stable requirement IDs.
- [ ] **PARTIAL** — Requirements traceability maps requirements to screens, database objects, code and tests.
- [ ] **PARTIAL** — ERD matches the final schema and relationships.
- [ ] **PARTIAL** — Architecture and sitemap match Laravel + Bootstrap + Supabase.
- [x] **DONE** — XAMPP is documented as the local PHP environment; Supabase PostgreSQL is the application database.
- [ ] **PENDING** — Client confirms enrollment cutoff and withdrawal rules.
- [ ] **PENDING** — Client confirms verification, approval and walk-in rules.
- [ ] **PENDING** — Client confirms waitlist promotion policy, including capacity increase behavior.
- [ ] **PENDING** — Maintain a change/decision log where implementation differs from the proposal.
- [ ] **PENDING** — Final proposal approval/submission evidence is retained.

---

# Phase 2 — Backend and Database

## Reproducible setup

- [x] **DONE** — Working branch is now `feature/laravel-phase3`, based on the Phase 2 implementation.
- [x] **DONE** — PHP/Composer/XAMPP setup instructions exist.
- [ ] **VERIFY** — Fresh checkout installs dependencies and runs cleanly using README instructions.
- [x] **DONE** — `.env.example` contains required environment variable names.
- [x] **LIVE VERIFIED** — Local `.env` configuration successfully connected Laravel to hosted Supabase.
- [x] **LIVE VERIFIED** — Live testing used `KOMUNIEDAD_DEMO=false`.
- [x] **DONE** — The Supabase project key may be a publishable key or legacy anon JWT.
- [x] **DONE** — The application does not require a service-role key.
- [x] **DONE** — `.gitignore` excludes `.env`, logs, caches, vendor dependencies and other local artifacts.
- [ ] **PENDING** — Final production/demo environment disables debug stack traces and uses safe error handling.

## Supabase schema and identity

- [x] **LIVE VERIFIED** — Hosted schema exists.
- [x] **LIVE VERIFIED** — Migration 4 was installed and `promote_after_capacity_increase()` was verified.
- [x] **LIVE VERIFIED** — Core application tables exist:
  - `activities`
  - `announcements`
  - `attendance`
  - `audit_logs`
  - `categories`
  - `enrollments`
  - `feedback`
  - `profiles`
  - `senior_profiles`
  - `settings`
- [x] **LIVE VERIFIED** — `settings.require_verification=false` for the current test setup.
- [ ] **PARTIAL** — Primary/foreign keys, status constraints and indexes exist but still need formal final verification evidence.
- [ ] **PARTIAL** — One active enrollment per senior/activity should be formally verified.
- [ ] **PARTIAL** — Attendance and feedback uniqueness should be formally verified.
- [ ] **PARTIAL** — Capacity/concurrency behavior should still be tested under simultaneous requests.
- [x] **LIVE VERIFIED** — Auth test accounts exist.
- [x] **LIVE VERIFIED** — Test users were assigned intended application roles.
- [x] **LIVE VERIFIED** — Administrator test account works.
- [x] **LIVE VERIFIED** — Coordinator A and Coordinator B accounts exist.
- [x] **LIVE VERIFIED** — Senior A and Senior B accounts exist.
- [x] **LIVE VERIFIED** — Sample activities and senior workflow records were loaded.
- [x] **LIVE VERIFIED** — Hosted records remained available across account sign-out/sign-in.
- [ ] **PENDING** — Confirm seed/script reruns do not produce unintended duplicates.

## Authentication and roles

- [x] **LIVE VERIFIED** — Login works against real Supabase Auth.
- [x] **LIVE VERIFIED** — Logout/account switching works.
- [ ] **PARTIAL** — Registration validation exists but final hosted registration tests are incomplete.
- [x] **TESTED LOCALLY** — Invalid live token handling is covered by tests.
- [ ] **PENDING** — Test disabled account behavior in hosted mode.
- [x] **DONE** — Session IDs are regenerated on login.
- [x] **DONE** — Logout invalidates the Laravel session.
- [x] **DONE** — Role enforcement exists in controllers/middleware.
- [x] **DONE** — Database RLS/RPC rules provide a second authorization layer.
- [ ] **PENDING** — Execute the complete hosted senior/coordinator/admin authorization matrix.

## Domain workflows

- [x] **DONE** — Activity create/read/update/status workflows are implemented.
- [x] **DONE** — Activity records include category, coordinator, title, description, venue, dates, cutoff, capacity, requirements and status.
- [x] **DONE** — Enrollment validates eligibility, duplicate enrollment and capacity.
- [x] **DONE** — Full activities support waitlisting.
- [x] **TESTED LOCALLY** — Waitlist promotion after withdrawal is covered.
- [x] **DONE** — Capacity increase waitlist promotion migration exists.
- [ ] **PENDING** — Live withdrawal → automatic promotion was not executed in the hosted verification sequence.
- [x] **DONE** — Attendance workflow exists.
- [x] **LIVE VERIFIED** — Existing completed/attended sample data appears in senior history.
- [x] **LIVE VERIFIED** — Existing feedback sample appears in hosted data.
- [ ] **PENDING** — New live attendance mutation and new feedback submission still need formal evidence.
- [x] **DONE** — Announcements workflow exists.
- [x] **DONE** — Profile maintenance exists.
- [x] **DONE** — Administrator account/role/category workflows exist.
- [x] **DONE** — Reports and summary-count workflows exist.
- [x] **DONE** — Temporary Supabase failures return safe messages and preserve the session where appropriate.

## Backend deliverables

- [x] **DONE** — Versioned backend source exists.
- [x] **DONE** — Migration files exist.
- [x] **DONE** — Setup and sample-data SQL scripts exist.
- [x] **DONE** — ERD/data documentation exists.
- [ ] **PARTIAL** — Final test results should be tied to the final Phase 3 commit.
- [ ] **PENDING** — Cross-check final backend deliverables against any separate Phase 2 rubric if provided.

---

# Phase 3 — Frontend + API

## 1. Responsive interface

- [x] **DONE** — Senior/public screens use responsive Blade + Bootstrap/custom CSS.
- [x] **DONE** — Coordinator/admin screens use the same responsive application shell.
- [x] **TESTED LOCALLY** — Reported browser checks covered mobile/tablet/desktop layouts.
- [ ] **PENDING** — Capture final screenshots at approximately:
  - 375px mobile
  - 768px tablet
  - 1366px desktop
- [ ] **PENDING** — Test on at least one real phone if required.
- [ ] **VERIFY** — No clipped controls or unintended full-page horizontal scrolling.
- [ ] **VERIFY** — Wide data tables remain usable on small screens.
- [x] **DONE** — Forms use labels and accessibility-oriented markup.
- [x] **DONE** — Skip link and live status regions exist.
- [ ] **PENDING** — Perform final keyboard-navigation and zoom checks.

## 2. JavaScript functionality

- [x] **DONE** — `public/community.js` contains project-written JavaScript.
- [x] **DONE** — Live text search is implemented with a debounce.
- [x] **DONE** — Category/status filters trigger dynamic updates.
- [x] **DONE** — Clear/reset triggers dynamic search.
- [x] **DONE** — Result count updates dynamically.
- [x] **DONE** — Search status updates dynamically.
- [x] **DONE** — Protected POST forms use Fetch where applicable.
- [x] **DONE** — Duplicate submission prevention is implemented using busy flags and disabled buttons.
- [x] **DONE** — Backend validation errors can be shown next to fields.
- [x] **DONE** — Dynamic focus handling is implemented after page-region refresh.
- [x] **DONE** — Stale requests are prevented using `AbortController` and request versioning.
- [ ] **VERIFY** — Final browser console shows no missing files or JavaScript errors.

## 3. API integration

- [x] **LIVE VERIFIED** — Live Supabase data is used, not only session demo data.
- [x] **DONE** — Browser JavaScript calls same-origin Laravel endpoints.
- [x] **DONE** — Laravel communicates with Supabase Auth/REST/RPC.
- [x] **DONE** — Browser JavaScript does not receive Supabase service-role credentials.
- [x] **DONE** — Activity search returns JSON containing:
  - `data`
  - `count`
  - rendered `html`
- [x] **DONE** — Protected POST redirects can be converted to JSON through `JsonFormResponse`.
- [x] **LIVE VERIFIED** — Successful live read was demonstrated through dynamic activity search.
- [ ] **PARTIAL** — A live no-reload write should be captured if required for Phase 3 evidence.
- [ ] **PENDING** — Capture redacted request/response evidence from browser Network tools.

## 4. AJAX / Fetch

- [x] **LIVE VERIFIED** — Search/filter works using `fetch()` without a full-page reload.
- [x] **TESTED LOCALLY** — Enrollment and withdrawal JSON workflows are covered in feature tests.
- [x] **TESTED LOCALLY** — Staff JSON form validation/save behavior is covered.
- [x] **DONE** — Laravel CSRF token is included in protected Fetch POST requests.
- [x] **DONE** — Loading states are implemented.
- [x] **DONE** — Success messages are implemented.
- [x] **DONE** — Empty-result messages are implemented.
- [x] **DONE** — Error messages are implemented.
- [x] **DONE** — Non-2xx HTTP responses are not treated as success.
- [x] **DONE** — Session-expired and CSRF-expired messages are handled.
- [x] **DONE** — The current page region refreshes after a successful mutation.
- [x] **DONE** — Newer search requests cannot be overwritten by stale responses.
- [ ] **PENDING** — Capture Network-panel Fetch/XHR evidence showing no document reload.

## 5. Form validation

- [x] **DONE** — Login and registration required fields are validated.
- [x] **DONE** — Registration email validation exists.
- [x] **DONE** — Profile fields have server validation.
- [x] **DONE** — Announcement fields have server validation.
- [x] **DONE** — Activity capacity/date/status validation exists.
- [x] **DONE** — Feedback rating validation exists.
- [x] **DONE** — Role and ownership checks occur server-side.
- [x] **DONE** — Dynamic forms can render backend validation errors without full-page navigation.
- [x] **TESTED LOCALLY** — Server validation still rejects invalid input even when client-side validation is bypassed.
- [ ] **PENDING** — Capture final validation-error screenshot evidence.

## 6. Dynamic search/filter

- [x] **LIVE VERIFIED** — Keyword search works.
- [x] **TESTED LOCALLY** — Keyword + category filtering is covered.
- [x] **DONE** — Status filtering is implemented.
- [x] **DONE** — Reset/clear is implemented.
- [x] **DONE** — Empty-result feedback is implemented.
- [x] **TESTED LOCALLY** — Escaping/XSS-oriented rendering checks exist.
- [x] **LIVE VERIFIED** — Search result cards update without full-page navigation.
- [x] **N/A** — Sorting/pagination is not currently implemented.

## Phase 3 submission package

- [x] **DONE** — Laravel project contains Blade templates, CSS, JavaScript, routes, API code, migrations and lockfiles.
- [x] **DONE** — README/setup documentation exists.
- [x] **DONE** — `.env.example` exists.
- [x] **DONE** — API integration source exists.
- [ ] **PARTIAL** — API notes exist but should be refreshed to explicitly document the Phase 3 Fetch/JSON contract.
- [ ] **PENDING** — Capture desktop screenshot.
- [ ] **PENDING** — Capture tablet screenshot.
- [ ] **PENDING** — Capture mobile screenshot.
- [ ] **PENDING** — Capture dynamic search screenshot.
- [ ] **PENDING** — Capture validation error screenshot.
- [ ] **PENDING** — Capture Fetch/XHR Network screenshot.
- [ ] **PENDING** — Capture API-generated live content screenshot.
- [ ] **PENDING** — Capture coordinator/admin screenshots for the demo set.
- [ ] **PENDING** — Record final PHP/database test output on the exact final submission commit.
- [ ] **PENDING** — Complete member contribution/rating records.
- [ ] **PENDING** — Rehearse the complete Phase 3 demonstration from a clean browser session.

### Phase 3 status summary

**Implementation status:** substantially complete.  
**Submission status:** not complete until screenshots, network evidence, final test output and contribution records are attached.

---

# Phase 4 — Security and Testing

Use only the authorized development/test environment and synthetic accounts.

## A. Input validation

- [ ] **PENDING** — Test blank and whitespace-only inputs.
- [ ] **PENDING** — Test malformed emails.
- [ ] **PENDING** — Test excessive field lengths.
- [ ] **PENDING** — Test unexpected characters.
- [ ] **PENDING** — Test zero/negative/fractional capacities.
- [ ] **PENDING** — Test invalid date combinations.
- [ ] **PENDING** — Test invalid status/rating values.
- [ ] **PENDING** — Test forged/nonexistent record IDs.
- [ ] **PENDING** — Record expected vs actual result for every case.

## B. SQL injection

- [ ] **PENDING** — Test login/search/writable fields using non-destructive SQL injection strings.
- [ ] **PENDING** — Test literal values such as:
  - `' OR '1'='1`
  - `' OR 1=1 --`
- [ ] **PENDING** — Verify there is no authentication bypass.
- [ ] **PENDING** — Verify there is no unintended data exposure.
- [ ] **PENDING** — Verify no raw database errors are disclosed.
- [ ] **PENDING** — Document parameterized/RPC-based query handling and its limits.

## C. Authentication

- [ ] **PENDING** — Valid login.
- [ ] **PENDING** — Wrong password.
- [ ] **PENDING** — Nonexistent account.
- [ ] **PENDING** — Empty login fields.
- [ ] **PENDING** — Logout followed by protected URL access.
- [x] **TESTED LOCALLY** — Invalid token rejection.
- [ ] **PENDING** — Expired token handling.
- [ ] **PENDING** — Disabled account handling.
- [ ] **PENDING** — Login rate-limit behavior.

## D. Authorization

- [x] **TESTED LOCALLY** — Senior cannot access staff/admin pages.
- [x] **TESTED LOCALLY** — Coordinator cannot access administration.
- [x] **TESTED LOCALLY** — Admin pages render under the admin role.
- [x] **TESTED LOCALLY** — Registration metadata cannot self-assign admin role.
- [ ] **PENDING** — Hosted Coordinator A versus Coordinator B ownership isolation test.
- [ ] **PENDING** — Direct-object/IDOR tests using modified record IDs.
- [ ] **PENDING** — Verify senior cannot access another senior's private participation data.
- [ ] **PENDING** — Verify coordinator cannot edit another coordinator's assigned activity.

## E. XSS and output encoding

- [x] **TESTED LOCALLY** — Stored announcement `<script>` marker is escaped.
- [x] **TESTED LOCALLY** — JSON activity result rendering uses escaped Blade output.
- [ ] **PENDING** — Test XSS payloads in all major writable text fields.
- [ ] **PENDING** — Test reflected search values.
- [ ] **PENDING** — Verify rendered pages do not execute injected HTML/JS.
- [ ] **PENDING** — Record before/after evidence.

## F. Functional / regression testing

- [ ] **PENDING** — Complete end-to-end senior workflow.
- [ ] **PENDING** — Complete end-to-end coordinator workflow.
- [ ] **PENDING** — Complete end-to-end administrator workflow.
- [ ] **PENDING** — Live enrollment persistence test.
- [ ] **PENDING** — Live withdrawal/waitlist promotion test.
- [ ] **PENDING** — Live attendance test.
- [ ] **PENDING** — Live feedback submission test.
- [ ] **PENDING** — Reports/count reconciliation.
- [ ] **PENDING** — Regression test after each confirmed bug fix.

## G. Usability testing

- [ ] **PENDING** — Recruit representative testers where required.
- [ ] **PENDING** — Record device/browser used.
- [ ] **PENDING** — Observe core task completion.
- [ ] **PENDING** — Record navigation/form difficulties.
- [ ] **PENDING** — Record accessibility/readability comments.
- [ ] **PENDING** — Apply reasonable fixes and retest.
- [ ] **PENDING** — Summarize usability results in the Phase 4 report.

## Phase 4 deliverables

- [ ] **PENDING** — Security & Testing Report.
- [ ] **PENDING** — Test-case table.
- [ ] **PENDING** — Security evidence/screenshots.
- [ ] **PENDING** — Bug log.
- [ ] **PENDING** — Retest evidence for fixed bugs.
- [ ] **PENDING** — Usability feedback summary.
- [ ] **PENDING** — Final testing summary.
- [ ] **PENDING** — Member contribution/rating records.

---

# Final Submission Cross-Check

- [ ] **PENDING** — Phase 1 final proposal is consistent with the implemented project.
- [ ] **PENDING** — Phase 2 database/backend evidence is complete.
- [ ] **PENDING** — Phase 3 screenshots and Network evidence are complete.
- [ ] **PENDING** — Phase 3 final test output is saved.
- [ ] **PENDING** — Phase 3 member contributions are recorded.
- [ ] **PENDING** — Phase 4 report contains all required test sections.
- [ ] **PENDING** — Bug log is complete and retested.
- [ ] **PENDING** — No unresolved critical defects remain.
- [ ] **PENDING** — Source, documentation, screenshots and presentation all refer to the same final version.
- [ ] **PENDING** — Final submission filename/format/channel match instructor requirements.
- [ ] **PENDING** — Final presentation/demo has been rehearsed.

---

# Recommended Next Order

1. Finish the **Phase 3 evidence package**.
2. Refresh the API notes to document the Fetch/JSON contract.
3. Rerun PHP/database tests on the exact final Phase 3 commit.
4. Capture desktop/tablet/mobile and Network evidence.
5. Complete contribution/rating records.
6. Move to **Phase 4 security and usability testing**.
7. Fix confirmed defects and retest.
8. Build the final report, presentation and submission package.

---

# Test Case Template

| ID | Requirement | Role / Preconditions | Procedure / Input | Expected Result | Actual Result | Status | Evidence |
|---|---|---|---|---|---|---|---|
| AUTH-01 | Valid login | Active test user | Enter valid credentials | Authorized page loads | | NOT RUN | |
| AUTHZ-01 | Senior blocked from admin | Senior | Open `/administration` | 403/denied | | NOT RUN | |
| AJAX-01 | Dynamic search | Senior | Search `digital` | Results update without document reload | Observed | PASS | |
| WAIT-01 | Waitlist display | Senior B | Open My activities | Waitlisted status appears | Observed | PASS | |

---

# Bug Log Template

| Bug ID | Description | Severity | Reproduction | Action Taken | Status | Retest Evidence |
|---|---|---|---|---|---|---|
| BUG-001 | | | | | Open | |

---

# Evidence Register Template

| Evidence ID | Phase / Requirement | File / Screenshot | Date / Version | What It Demonstrates |
|---|---|---|---|---|
| E001 | Phase 3 dynamic search | | | Search updates through Fetch without full-page reload |
| E002 | Phase 3 responsive desktop | | | Desktop layout |
| E003 | Phase 3 mobile | | | Mobile layout |
| E004 | Phase 3 validation | | | Backend validation shown dynamically |
| E005 | Phase 3 API/Fetch | | | Browser Network request/response |

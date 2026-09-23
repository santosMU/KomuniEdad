# KomuniEdad — Master Requirements and Submission Checklist: Phases 1–4

Prepared: 23 September 2026. Scope: Laravel, Bootstrap, JavaScript, Supabase Auth and PostgreSQL, aligned with the supplied Phase 1 proposal and Phase 3/4 instructor requirements.

## How to use this checklist

Every unchecked item needs implementation, verification, or submission evidence; it does not necessarily mean the code is missing. Mark an item complete only when its acceptance condition is demonstrated. Assign an owner, due date, and evidence link in your team tracker. Do not mark unexecuted tests PASS.

Phase 2 items below are derived from the proposal: the instructor's separate Phase 2 submission rubric has not been provided. Reconcile that rubric when available. Phase 3 and Phase 4 submission items below follow the instructions supplied in this conversation.

## Current progress dashboard

Reviewed 23 September 2026 against application commit `6be2852`, supplied proposal and Supabase screenshots. Git inspection found documentation additions but no tracked application changes since the previously tested commit. No tests or hosted operations were run during this documentation update.

| Status | Meaning |
|---|---|
| DONE | The specific document/code requirement is supported by available evidence. |
| TESTED LOCALLY | The stated automated check passed; does not prove hosted behavior. |
| PARTIAL | Supporting implementation exists, but acceptance, completeness or evidence is outstanding. |
| VERIFY | The current local/hosted state cannot be established from supplied evidence. |
| PENDING | Work or submission evidence has not been demonstrated. This does not assert nobody has done it outside this review. |

Unchecked boxes stay unchecked until the full item is verified. Completion percentages are intentionally omitted: these tasks differ in scope and implementation is not equivalent to submission acceptance.

| Phase | What exists now | What prevents completion |
|---|---|---|
| 1 — Proposal | Revised PDF; requirements mapping; ERD; architecture/setup notes | Client/policy confirmation, final consistency and approval/submission evidence |
| 2 — Database/backend | Laravel workflows for three roles, three SQL migrations, RLS/RPC code, feature/database test sources | Hosted integration, accounts, persistence, permissions, concurrency and agreed client policies |
| 3 — Frontend/API | 13 Blade templates, Bootstrap/custom CSS, server-rendered search, server-side Supabase API integration | Application JavaScript/Fetch, no-reload behavior, device testing, complete API notes and screenshots |
| 4 — Security/testing | Earlier local PHP run: 17 tests / 61 assertions; selected role/validation/XSS coverage | Live security tests, SQLi, broader XSS, usability, report, bug log and evidence |
| Presentation/completion | Requirements identified in this checklist | Slides, rehearsal, final package, ratings, submission and instructor acceptance evidence |

### Confirmed completed building blocks
- [x] **DONE** — Revised Phase 1 proposal supplied; approval is not implied.
- [x] **DONE** — Laravel application routes/controllers/services/session middleware implemented.
- [x] **DONE** — Supabase Auth integration and three database migrations implemented in source.
- [x] **DONE** — Senior profile/discovery/enrollment/withdrawal/history/feedback workflow code implemented.
- [x] **DONE** — Coordinator activity/roster/attendance/announcement/report workflow code implemented.
- [x] **DONE** — Administrator user/role/category/verification/audit/oversight code implemented.
- [x] **DONE** — Blade templates, bundled Bootstrap and custom stylesheet created.
- [x] **DONE** — Server-rendered search/filter and Laravel-to-Supabase REST/RPC integration implemented.
- [x] **DONE** — Setup guide, initial requirements mapping, ERD and route/RPC notes written.
- [x] **TESTED LOCALLY** — Earlier PHP suite passed 17 tests / 61 assertions on this application commit.
- [x] **TESTED LOCALLY** — Selected demo search/enrollment/duplicate/withdrawal/waitlist cases passed.
- [x] **TESTED LOCALLY** — Guest/role restrictions, role-escalation rejection, registration metadata allowlist and mocked token/RPC checks passed.
- [x] **TESTED LOCALLY** — One demo stored announcement XSS marker was escaped; ineligible feedback was rejected.

### Evidence and scope

| Evidence | Supports | Does not establish |
|---|---|---|
| Supplied Phase 1 PDF | Agreed proposal content and intended architecture | Client approval or final instructor acceptance |
| Supplied Supabase screenshots | Public table names are present | Schema correctness, populated records, successful app integration or current Auth state |
| [README](../README.md), [setup](setup.md), [requirements](requirements.md), [ERD](database/ERD.md), [API notes](api/endpoints.md) | Existing technical documentation | Complete Phase 3 API examples or final submission package |
| `routes/web.php`, `app/Http/Controllers/`, `app/Services/`, `resources/views/`, `supabase/migrations/` | Implemented application and database structures | End-to-end hosted correctness |
| [Verification notes](CHECKLIST-VERIFICATION.md), `tests/Feature/` | Earlier successful local test run and limited coverage | Live Supabase, browser/device, penetration or usability verification |

The user last confirmed no Auth accounts existed before account-setup instructions were supplied. No subsequent creation/execution evidence was supplied; account setup therefore remains VERIFY. Prepared sample scripts outside this repository are not counted as repository deliverables. The repository currently contains its original `supabase/seed.sql`.

The latest attempted server restart returned HTTP 500 due to runtime writes, then the execution environment blocked restart. Current working-site status remains VERIFY. No browser startup was performed in this checklist update.

### Immediate next steps
1. Restore/verify a working local website and live Supabase configuration.
2. Confirm migrations, create role test accounts and verify sample data in hosted Supabase.
3. Verify role workflows; resolve recorded policy questions and review observations.
4. Implement application JavaScript, JSON responses and Fetch/AJAX search/submission.
5. Test responsive behavior, finish API notes and capture Phase 3 evidence.
6. Execute/document Phase 4 testing, correct defects, collect usability feedback and package the final submission.

## Course schedule and submission gates

Dates and week labels below are reproduced from the instructor instructions. Confirm exact submission times and channels with the instructor; none were supplied.

| Course milestone | Instructor schedule | Required outcome |
|---|---|---|
| Combined Phases 1 and 2 | September 7 — Week 5 | Final revised proposal together with database/backend work |
| Phase 3 | September 14 — Week 6 | Frontend + API and all Phase 3 submission artifacts |
| Revision and Phase 4 | September 21–October 4 — Weeks 7–9 | Final revisions of Phases 1–3 plus security/testing, fixes and evidence |
| Final presentation | October 5–10 — Week 10 | Demonstrate the final working system and explain the implementation/testing |
| Completion and verification | Week 11; exact dates not supplied | Departmental/summative S2, completion, any required re-presentation and grade verification |

This is the comprehensive list of required work, not a declaration that everything is missing or complete. Track progress inline below; supporting test observations are in [CHECKLIST-VERIFICATION.md](CHECKLIST-VERIFICATION.md). The Phase 3/4 requirements come from the instructor instructions; backend features come from the proposal. Suggested implementation choices, example viewport sizes and additional checks are supporting tasks, not extra instructor mandates.

### Combined Phase 1 + Phase 2 submission
- [ ] **PENDING** — Combine the final revised proposal and database/backend deliverables in the instructor's requested submission.
- [ ] **PENDING** — Include the correct proposal version, backend source, schema/migrations, setup instructions and required supporting diagrams.
- [ ] **PENDING** — Confirm which version was submitted and record receipt/confirmation.
- [ ] **PENDING** — Resolve instructor feedback and carry corrections into the final revision of Phases 1–3.

## Reference baseline — not completion sign-off

| Area | Current evidence | Still required |
|---|---|---|
| Proposal | Phase 1 PDF exists and requirements were extracted | Confirm client, policy decisions, approval, and consistency with final implementation |
| Backend | Laravel routes, services, three SQL migrations and role workflows exist | Verify hosted migrations, Auth accounts, RLS and full live workflows |
| Database content | Dashboard screenshots show tables; seed scripts prepared and locally tested | Execute selected scripts in hosted Supabase and verify results; hosted execution is not confirmed |
| Automated tests | Earlier baseline: 17 PHP tests / 61 assertions passed; sample seed tests passed locally | Record current commit-specific results and live integration evidence; these are not a complete security assessment |
| Frontend | Blade pages, Bootstrap and project CSS exist | Verify responsive behavior and accessibility on actual rendered pages |
| API | Laravel service calls Supabase REST/RPC | Demonstrate live data and document requests, responses and errors |
| JavaScript / AJAX | No fetch(), XMLHttpRequest, script tags or event listeners found in inspected application resources | Build and demonstrate meaningful no-reload behavior with JavaScript files |
| Phase 4 | Some automated coverage exists | Execute and document all required security, functional and usability tests |

## Phase 1 — Proposal and agreed scope

### Documents and planning
- [ ] **PARTIAL** — Final proposal has correct project title, team members, course, version and date.
- [ ] **PENDING** — Intended client organization is identified and validated; retain communication letter and response if required.
- [ ] **PARTIAL** — Problem statement, general objective and specific objectives match the implemented system.
- [x] **DONE** — Stakeholders and the three authenticated roles are clearly distinguished: senior, coordinator and administrator.
- [ ] **PARTIAL** — Scope and exclusions are preserved: no payments, clinical records, government identity integration or native mobile app required.
- [ ] **PARTIAL** — Functional and non-functional requirements have stable identifiers copied from the proposal.
- [ ] **PARTIAL** — Requirements traceability table maps each requirement to database entities, routes/screens, tests and evidence.
- [ ] **PARTIAL** — ERD matches actual keys, relationships and constraints.
- [ ] **PARTIAL** — Sitemap, wireframes and architecture match Laravel + Bootstrap + Supabase.
- [x] **DONE** — Explain that XAMPP supplies the local PHP/Apache environment; Supabase PostgreSQL is the app database.
- [ ] **PENDING** — Client confirms categories, required participant fields, enrollment cutoff and withdrawal rules.
- [ ] **PENDING** — Client confirms optional senior verification, approval workflow and walk-in rules.
- [ ] **PENDING** — Client confirms waitlist promotion rules, including behavior after withdrawal or capacity changes. Do not describe capacity-increase promotion as an explicit proposal requirement unless agreed.
- [ ] **PENDING** — Retain a decisions/change log when implementation differs from the proposal.
- [ ] **PENDING** — Submit final proposal and any required approval/communication evidence.

## Phase 2 — Backend and database

### Reproducible setup
- [ ] **PENDING** — Verify the intended Git branch and preserve existing uncommitted work.
- [x] **DONE** — Document PHP version/extensions, Composer installation and supported local launch method.
- [ ] **VERIFY** — A fresh checkout installs dependencies and runs using the README.
- [x] **DONE** — `.env.example` contains required variable names and safe placeholders.
- [ ] **VERIFY** — Local `.env` has APP_KEY, correct app URL and Supabase connection settings.
- [ ] **VERIFY** — Live demonstration uses `KOMUNIEDAD_DEMO=false`; clearly label any offline demo mode.
- [ ] **VERIFY** — Current implementation receives the legacy anon key it expects; never use a service-role key in browser code.
- [ ] **PENDING** — `.env`, credentials, session files and sensitive logs are excluded from commits and submission copies.
- [ ] **PENDING** — Configure safe error handling; deployed demonstrations do not expose debug stack traces or secrets.

### Supabase schema and identity
- [ ] **VERIFY** — Apply/verify all three repository migrations in order; do not rebuild existing tables blindly.
- [ ] **PARTIAL** — Verify profiles, senior_profiles, categories, activities, enrollments, attendance, announcements, feedback and audit_logs.
- [ ] **PARTIAL** — Verify supporting settings and their intended values.
- [ ] **PARTIAL** — Verify primary/foreign keys, required fields, allowed statuses and useful indexes.
- [ ] **PARTIAL** — Verify one active enrollment per senior/activity, attendance uniqueness and feedback uniqueness.
- [ ] **PARTIAL** — Verify capacity cannot be exceeded, including simultaneous registrations.
- [ ] **PARTIAL** — Verify date and capacity constraints; consistently display Philippine time.
- [ ] **PARTIAL** — Enable and test appropriate RLS and grants for application tables and functions.
- [ ] **VERIFY** — Create Auth test users through Supabase Auth; passwords remain managed by Auth.
- [ ] **PARTIAL** — Confirm new accounts receive senior profiles and cannot self-assign staff roles.
- [ ] **VERIFY** — Bootstrap the intended administrator; confirm the audit entry.
- [ ] **VERIFY** — Create coordinator A and coordinator B for assigned-activity isolation tests.
- [ ] **VERIFY** — Create senior A and senior B for ownership, duplicate and waitlist tests.
- [ ] **VERIFY** — Load clearly fictional sample activities and participation; verify actual row counts.
- [ ] **PENDING** — Confirm seed reruns preserve records without duplicates.
- [ ] **VERIFY** — Confirm records persist after logout/login and application restart.

### Authentication and roles
- [ ] **PARTIAL** — Registration validates fields and handles duplicate email and confirmation state clearly.
- [ ] **PARTIAL** — Login/logout operate against real Supabase Auth.
- [ ] **PARTIAL** — Invalid, expired and revoked tokens do not grant protected access.
- [ ] **PARTIAL** — Disabled accounts cannot use protected workflows.
- [ ] **PARTIAL** — Session IDs/tokens are handled safely; logout clears local access.
- [ ] **PARTIAL** — Senior users access only their own private enrollment, profile and history data.
- [ ] **PARTIAL** — Coordinators manage only assigned activities and their authorized participants.
- [ ] **PARTIAL** — Administrators can perform intended organization-wide operations.
- [ ] **PARTIAL** — Roles and ownership are enforced on the server and database, not just by hidden buttons.

### Domain workflows
- [ ] **PARTIAL** — Activities support intended create/read/update/archive or deletion behavior without orphaning historical records.
- [ ] **PARTIAL** — Activity fields include category, coordinator, title, description, venue, start/end, cutoff, capacity, requirements and status.
- [ ] **PARTIAL** — Lifecycle statuses and transitions behave consistently; closed/cancelled activities reject enrollment.
- [ ] **PARTIAL** — Activity list/detail show accurate availability and coordinator information.
- [ ] **PARTIAL** — Enrollment enforces eligibility, cutoff, duplicates and capacity.
- [ ] **PARTIAL** — Full activities produce the intended waitlist result.
- [ ] **PARTIAL** — Withdrawals honor cutoff and client policy; subsequent promotion follows the agreed rule.
- [ ] **PARTIAL** — Approval/rejection and walk-in operations follow configured client rules.
- [ ] **PARTIAL** — Attendance links to valid enrollment and permits only authorized staff edits.
- [ ] **PARTIAL** — Senior history reflects actual enrollment and attendance.
- [ ] **PARTIAL** — Eligible completed, attended enrollments accept feedback once.
- [ ] **PARTIAL** — Announcements follow activity-level and organization-wide permissions.
- [ ] **PARTIAL** — Profile maintenance validates required and optional fields.
- [ ] **PARTIAL** — Administrators manage users, coordinator roles, verification and categories.
- [ ] **PARTIAL** — Authorized corrections create accurate actor/time/target audit entries.
- [ ] **PARTIAL** — Coordinators see activity-level summaries; administrators see consolidated reports.
- [ ] **PARTIAL** — Summary counts reconcile with database records.
- [ ] **PARTIAL** — API/network failures produce understandable messages without falsely claiming success.

### Backend deliverables
- [ ] **PARTIAL** — Versioned backend source, migration files and repeatable seed/setup instructions.
- [ ] **PARTIAL** — ERD/data dictionary and role/permission matrix.
- [ ] **PARTIAL** — Backend setup notes and test results tied to a commit.
- [ ] **PENDING** — Check these deliverables against the instructor's Phase 2 rubric when supplied.

## Phase 3 — Frontend + API

### 1. Responsive interface
- [ ] **PARTIAL** — Login, registration, activity browse/detail, profile, history and announcements work at desktop/tablet/mobile widths.
- [ ] **PARTIAL** — Staff workspace, activity forms, roster, attendance, administration and reports work at all three sizes.
- [ ] **PENDING** — Test representative widths: 375px mobile, 768px tablet and 1366px desktop; also test a real available phone.
- [ ] **PENDING** — No clipped controls, overlapping text or unintended page-wide horizontal scrolling.
- [ ] **PENDING** — Wide tables have a usable small-screen layout or contained scrolling.
- [ ] **PARTIAL** — Text, labels, contrast, focus and touch controls suit older users.
- [ ] **PENDING** — Keyboard navigation and zoom remain usable; focus order and form labels are meaningful.
- [ ] **PENDING** — Capture desktop, tablet and mobile evidence for key workflows.

### 2. JavaScript functionality
- [ ] **PENDING** — Include readable application JavaScript files in the submission.
- [ ] **PENDING** — Implement meaningful interactions, such as live filters, result counts, enrollment feedback or confirmation dialogs.
- [ ] **PENDING** — Load scripts correctly with no missing files or browser console errors.
- [ ] **PENDING** — Interactive controls work by keyboard and expose status/error changes accessibly.
- [ ] **PENDING** — Prevent duplicate submissions and restore controls after success or failure.
- [ ] **PENDING** — Confirm a staff library/plugin alone is not the only evidence of student-written JavaScript.

### 3. API integration
- [ ] **VERIFY** — Use live Supabase data rather than only hardcoded/session demo records.
- [ ] **PENDING** — Recommended flow: browser JavaScript → authenticated Laravel JSON endpoint → Supabase REST/RPC → JSON response → updated interface.
- [ ] **PENDING** — Define the Laravel JSON contract: method, path, permitted role, inputs, response and errors.
- [ ] **PENDING** — Document the actual Supabase endpoints used, including relevant Auth, REST and RPC calls.
- [ ] **PENDING** — Demonstrate one successful read and one authorized write reaching persistent storage.
- [ ] **PENDING** — Show request, response and rendered result; redact tokens and personal details from evidence.
- [ ] **PENDING** — Handle unauthenticated, forbidden, invalid input, rate-limit and service-failure responses.
- [ ] **PENDING** — Keep privileged credentials out of JavaScript and screenshots.

### 4. AJAX / Fetch
- [ ] **PENDING** — Implement `fetch()` or AJAX for activity search/filter without full-page navigation.
- [ ] **PENDING** — Implement at least one no-reload submission, such as enrollment/withdrawal, as a strong demonstration of both reads and writes.
- [ ] **PENDING** — Send CSRF protection with session-authenticated Laravel mutations.
- [ ] **PENDING** — Show loading, success, empty and failure states.
- [ ] **PENDING** — Check HTTP status before treating a response as success; handle non-JSON errors safely.
- [ ] **PENDING** — Refresh relevant availability/status from the server after a mutation.
- [ ] **PENDING** — Prevent stale search responses from replacing newer results.
- [ ] **PENDING** — Capture browser Network evidence showing Fetch/XHR traffic with no document reload.

### 5. Form validation
- [ ] **PARTIAL** — Validate required fields and email format on registration/login.
- [ ] **PARTIAL** — Validate text lengths and allowed values for profile and announcements.
- [ ] **PARTIAL** — Validate activity dates, cutoff, positive whole-number capacity and permitted status.
- [ ] **PARTIAL** — Validate IDs, role/ownership and feedback rating on the server.
- [ ] **PARTIAL** — Show field-level errors and retain safe input after failure; do not echo passwords.
- [ ] **PENDING** — Dynamic forms display backend validation errors without reloading.
- [ ] **PARTIAL** — Bypassing browser validation still fails safely on the server.

### 6. Dynamic search/filter
- [ ] **PARTIAL** — Activity keyword search produces correct matches.
- [ ] **PARTIAL** — Category and status filters combine correctly where offered.
- [ ] **PARTIAL** — Reset clears filters and restores the expected list.
- [ ] **PARTIAL** — Empty results show an understandable message.
- [ ] **PENDING** — Special characters are handled safely.
- [ ] **PENDING** — Search/filter results update from API data without reloading.
- [ ] **PENDING** — Sorting/pagination, if implemented, preserve filter criteria.

### Phase 3 submission package
- [ ] **PARTIAL** — Complete Laravel project includes Blade HTML templates, CSS, JS, assets, API code, routes, migrations and dependency manifests/lockfiles.
- [ ] **PENDING** — Explain that Laravel renders HTML from Blade templates; verify the instructor accepts this project structure if standalone HTML files are specifically required.
- [ ] **PARTIAL** — Include README install/run steps and `.env.example`; provide dependencies according to submission rules.
- [ ] **VERIFY** — All pages, navigation, images and assets work from the submitted copy.
- [ ] **PARTIAL** — API notes identify API, purpose, endpoints, fields retrieved/submitted and integration flow.
- [ ] **PENDING** — Include redacted example requests/responses and error behavior in API notes.
- [ ] **PENDING** — Include desktop/mobile screenshots, API-generated content, validation errors and search/filter results.
- [ ] **PENDING** — Include tablet evidence and Fetch/XHR evidence to support demonstration.
- [ ] **PENDING** — Leader/assistant leader completes honest member ratings and contributions.
- [ ] **PENDING** — Rehearse a full demonstration from a clean browser session.

## Phase 4 — Security and testing

Use an isolated local/test deployment and synthetic accounts. Only test systems within your authorization. A scanner is optional under the supplied instructions; Nessus or another suitable tool can supplement manual tests. Scanning does not replace role, workflow or usability testing. Do not scan shared Supabase infrastructure as if it were your own server.

### A. Input validation
- [ ] **PENDING** — Test blanks, whitespace-only values, malformed email, excessive lengths and unexpected characters.
- [ ] **PENDING** — Test zero/negative/fractional capacity, invalid dates and invalid rating/status values.
- [ ] **PENDING** — Test forged or nonexistent record IDs and unexpected input fields.
- [ ] **PENDING** — Repeat selected tests with client validation bypassed.
- [ ] **PENDING** — Record expected/actual outcome, status and screenshot for each case.

### B. SQL injection
- [ ] **PENDING** — Test login, search/filter and relevant writable text/ID fields with non-destructive common SQL injection strings.
- [ ] **PENDING** — Include cases such as `' OR '1'='1` and `' OR 1=1 --` as literal test inputs.
- [ ] **PENDING** — Verify no authentication bypass, unintended records, data modification or raw database error disclosure.
- [ ] **PENDING** — Review query construction/RPC parameters and confirm input is not concatenated into executable SQL.
- [ ] **PENDING** — Compare relevant data before/after and retain sanitized request/response evidence.
- [ ] **PENDING** — Describe the tested coverage and limits; passing a few payloads does not prove every possible injection impossible.

### C. Authentication
- [ ] **PENDING** — Valid login, wrong password, nonexistent account and empty fields.
- [ ] **PENDING** — Password requirements and email confirmation behavior match configuration.
- [ ] **PENDING** — Logout followed by protected URL access is denied.
- [ ] **PENDING** — Protected requests without session, with invalid/expired token and with disabled account are denied.
- [ ] **PENDING** — Session behavior after login/logout and browser refresh is correct.
- [ ] **PENDING** — Rate limiting works without revealing passwords or sensitive account information.
- [ ] **PENDING** — Record password reset behavior if the project offers it.

### D. Authorization
- [ ] **PENDING** — Guest cannot access protected pages or mutation routes.
- [ ] **PENDING** — Senior cannot manage activities, attendance, users, categories or organization reports.
- [ ] **PENDING** — Senior A cannot read/edit senior B's profile, enrollment, attendance or feedback by changing IDs.
- [ ] **PENDING** — Coordinator A cannot manage coordinator B's activities or rosters.
- [ ] **PENDING** — Coordinator cannot grant roles or perform administrator-only operations.
- [ ] **PENDING** — Administrator can perform intended privileged workflows; sensitive changes are audited.
- [ ] **PENDING** — Test direct URLs and HTTP requests, not only visible navigation.
- [ ] **PENDING** — Test Supabase access with anonymous and authenticated user credentials to verify RLS and function permissions.
- [x] **TESTED LOCALLY** — Demo role-switch endpoint is unavailable when live mode is enabled.

### E. XSS
- [ ] **PENDING** — Test search, profile names, activity text, announcements, feedback and other rendered input.
- [ ] **PENDING** — Use harmless marker payloads, e.g. `<script>alert('xss-test')</script>` in the isolated test environment.
- [ ] **PENDING** — Verify reflected, stored and JavaScript-rendered content stays inert or is rejected according to validation.
- [ ] **PENDING** — Inspect the same stored content as another authorized role, not only its author.
- [ ] **PARTIAL** — Review Blade escaping and JavaScript insertion points; do not inject untrusted strings as HTML.
- [ ] **PENDING** — Capture rendered output and evidence that the marker did not execute.

### F. Functional and regression tests
- [ ] **PENDING** — Test every major Phase 2 workflow and every Phase 3 interaction.
- [ ] **PENDING** — Cover happy paths, invalid inputs, ownership failures and missing records.
- [ ] **PENDING** — Test last-seat concurrency, duplicate requests, full activity waitlisting and cutoff boundaries.
- [ ] **PENDING** — Test withdrawal/promotion and agreed capacity-change behavior.
- [ ] **PENDING** — Verify completed attendance, feedback eligibility/uniqueness and report totals.
- [ ] **PENDING** — Test slow/offline/unavailable API states with no false success messages.
- [ ] **PARTIAL** — Run PHP/database automated tests and retain commit/date/output.
- [ ] **PENDING** — Retest every fix and relevant neighboring workflows.

### G. Usability
- [ ] **PENDING** — Recruit a few testers and record the actual count; include target-age users where practical.
- [ ] **PENDING** — Use synthetic accounts and avoid unnecessary personal/medical data.
- [ ] **PENDING** — Ask testers to find an activity, enroll, identify status, withdraw when allowed and find history.
- [ ] **PENDING** — Ask staff testers to create an activity, inspect a roster and record attendance.
- [ ] **PENDING** — Collect feedback on navigation, readability, interface, control placement, errors and mobile use.
- [ ] **PENDING** — Record task success, difficulties, comments and suggested improvements.
- [ ] **PENDING** — Summarize findings, implement priority improvements and retest them.
- [ ] **PENDING** — Report actual tester responses; do not fabricate ratings or feedback.

### Additional project-relevant security checks
- [ ] **PARTIAL** — Verify CSRF protection for state-changing session requests.
- [ ] **PENDING** — Verify secrets are absent from source, browser bundles, screenshots and reports.
- [ ] **PENDING** — Check dependencies for known issues and document relevant findings/fixes.
- [ ] **PENDING** — Review sensitive error logging, safe session-cookie settings and HTTPS on deployed instances.
- [ ] **PENDING** — If a scanner is used, record tool/version, scope, date, authentication level and findings; review false positives.

### Known review observations to resolve or explicitly disposition
- [ ] **PENDING** — Decide capacity-increase/waitlist behavior with client; test and document the selected rule.
- [ ] **PENDING** — If retaining demo mode, fix/verify enrollment count synchronization and withdrawal promotion consistency.
- [ ] **PENDING** — Improve/verify generic Supabase errors and temporary service failures that may sign users out.
- [ ] **PENDING** — Hide or disable withdrawal actions when cutoff/status forbids them while retaining backend enforcement.

### Phase 4 deliverables and release gate
- [ ] **PENDING** — Security & Testing Report contains sections A–G above.
- [ ] **PENDING** — Every test records input/procedure, expected result, actual result, PASS/FAIL/NOT RUN, evidence and date.
- [ ] **PENDING** — Authorization evidence includes role, target feature and expected/actual access.
- [ ] **PENDING** — Screenshots cover validation, authentication, access denial, XSS/SQLi results, functionality and usability.
- [ ] **PENDING** — Bug log includes severity, reproduction, action, status and retest evidence.
- [ ] **PENDING** — All identified critical issues are resolved and retested; document remaining lower-priority limitations honestly.
- [ ] **PENDING** — Final source includes tested fixes; verify the submission copy starts cleanly.
- [ ] **PENDING** — Final summary reports total cases, passed, failed, fixed and remaining issues.
- [ ] **PENDING** — Explain that fixed defects are counted separately from test cases; totals reconcile without double-counting retests.
- [ ] **PENDING** — Leader/assistant leader completes Phase 4 member ratings and contributions.
- [ ] **PENDING** — Tag or record final Git commit and include a presentation/demo guide.

## Final revision of Phases 1–3 — September 21 to October 4

- [ ] **PENDING** — Collect instructor feedback on the proposal, database/backend and frontend/API submissions.
- [ ] **PENDING** — List each requested correction, responsible member, affected files and acceptance evidence.
- [ ] **PENDING** — Reconcile the proposal with the final implemented scope; document approved deviations.
- [ ] **PENDING** — Reconcile ERD/data dictionary with actual migrations, keys, statuses and constraints.
- [ ] **PENDING** — Reconcile API documentation with the endpoints and response fields actually used.
- [ ] **PENDING** — Remove obsolete setup instructions and clearly distinguish live mode from local demo mode.
- [ ] **PENDING** — Verify the final app implements all agreed senior, coordinator and administrator workflows.
- [ ] **PENDING** — Recheck navigation, assets, validation, dynamic search and AJAX after revisions.
- [ ] **PENDING** — Recheck desktop/tablet/mobile behavior after interface changes.
- [ ] **PENDING** — Update screenshots so they show the final corrected version.
- [ ] **PENDING** — Link each corrected issue to its retest evidence.
- [ ] **PENDING** — Record the exact commit/version used for final reports and presentation.

## Final presentation — October 5–10

The instructor supplied a presentation period but no separate presentation rubric. The following preparation tasks support a reliable demonstration; confirm format, allotted time and required slide contents with the instructor.

### Presentation materials and readiness
- [ ] **PENDING** — Confirm presentation date/time, venue/platform, duration and member attendance.
- [ ] **PENDING** — Prepare slides covering problem/client, objectives, scope, roles, architecture, ERD, key workflows, API integration, testing and limitations.
- [ ] **PENDING** — Explain the responsibilities of Laravel, Bootstrap/JavaScript, Supabase Auth and PostgreSQL.
- [ ] **PENDING** — Prepare an accurate contribution summary; each member can explain their own work.
- [ ] **PENDING** — Start the submitted version on the presentation machine using the README.
- [ ] **PENDING** — Verify internet access, Supabase connectivity, login accounts and synthetic demonstration records.
- [ ] **PENDING** — Verify projector/browser scaling and a mobile demonstration method.
- [ ] **PENDING** — Keep a local copy of source, documents and evidence for reference; label any backup video/screenshots as recorded evidence.
- [ ] **PENDING** — Keep passwords, API tokens and private participant data out of slides and projected terminals.

### Demonstration sequence
- [ ] **PENDING** — Show desktop and mobile/tablet layouts.
- [ ] **PENDING** — Show login and explain the three roles.
- [ ] **PENDING** — As a senior, search/filter activities dynamically and view activity details.
- [ ] **PENDING** — Show API request/response and how returned data updates the interface without a full reload.
- [ ] **PENDING** — Show required-field or invalid-format errors, then submit valid input.
- [ ] **PENDING** — Demonstrate enrollment, available-slot update, full-activity waitlisting and permitted withdrawal.
- [ ] **PENDING** — Show participation history and eligible completed-attendance feedback.
- [ ] **PENDING** — As a coordinator, create/edit an assigned activity, inspect participants and record attendance.
- [ ] **PENDING** — Show announcements and activity-level summaries.
- [ ] **PENDING** — As an administrator, demonstrate user/category management, oversight and audit entries.
- [ ] **PENDING** — Demonstrate a denied restricted request and explain server/database authorization.
- [ ] **PENDING** — Present security test cases, actual results, corrected bugs and usability findings.
- [ ] **PENDING** — Explain any remaining limitations without presenting untested features as verified.
- [ ] **PENDING** — Rehearse within the allotted time and prepare for questions about database design, API flow, security and contributions.

## Week 11 — completion, re-presentation and grade verification

- [ ] **PENDING** — Confirm departmental/summative S2 requirements and schedule separately from the project deliverables.
- [ ] **PENDING** — Check instructor feedback and outstanding completion requirements.
- [ ] **PENDING** — Make requested corrections and retain the final updated submission version.
- [ ] **PENDING** — Prepare and attend project re-presentation if required.
- [ ] **PENDING** — Confirm that project files, reports, evidence and contribution ratings were received.
- [ ] **PENDING** — Verify recorded grades through the official process and raise discrepancies with supporting submission evidence.
- [ ] **PENDING** — Archive final source, proposal, reports and submission receipts for the group.

## Final artifact inventory

Suggested organization below is a filing plan, not a claim that these folders/files already exist or an additional instructor formatting requirement.

| Artifact | Required contents | Suggested location |
|---|---|---|
| Revised proposal | Final Phase 1 PDF and revisions | `docs/proposal/` |
| Database/backend | Laravel source, migrations, seed/setup instructions, ERD | Repository source, `supabase/`, `docs/database/` |
| Frontend | Blade/HTML templates, CSS, JavaScript and assets | `resources/`, `public/` |
| API notes | API used, purpose, endpoints, returned data and website integration | `docs/api/` |
| Phase 3 screenshots | Desktop/mobile, API content, validation, search/filter | `docs/evidence/phase3/` |
| Security & Testing Report | Sections A–G, evidence references and testing summary | `docs/testing/` |
| Phase 4 evidence | Validation/auth/authorization/security/functional/usability results and bug fixes | `docs/evidence/phase4/` |
| Bug log | Description, severity, action taken, status and retest | `docs/testing/` |
| Member ratings/contributions | Leader/assistant-leader records for Phases 3 and 4 | `docs/contributions/` |
| Presentation | Slides, demo sequence and confirmed final version | `docs/presentation/` |
| Run instructions | Installation, environment variables, accounts/setup, launch and test commands | `README.md`, `.env.example` |

### Final hand-in cross-check
- [ ] **PENDING** — Revised Phase 1 and Phase 2 work are combined as instructed.
- [ ] **PENDING** — All six Phase 3 requirements have working demonstrations.
- [ ] **PENDING** — Phase 3 includes complete folder, working website, API notes, screenshots and member ratings/contributions.
- [ ] **PENDING** — Phase 4 report includes all seven sections A–G and real results.
- [ ] **PENDING** — Phase 4 includes evidence, bug log, corrected project, testing summary and member ratings/contributions.
- [ ] **PENDING** — No known unresolved critical bugs remain; other limitations are disclosed.
- [ ] **PENDING** — Submitted source, screenshots, reports and presentation refer to the same final version.
- [ ] **PENDING** — Submission filenames, format, channel and deadline match the instructor's directions.

## Tracking templates

### Requirement traceability
| Requirement ID | Requirement | Code/page/API | Test IDs | Evidence | Owner | Status |
|---|---|---|---|---|---|---|
| Copy from proposal/rubric | | | | | | Not verified |

### Test case
| ID | Requirement | Role/preconditions | Input/procedure | Expected result | Actual result | Status | Evidence | Date/commit |
|---|---|---|---|---|---|---|---|---|
| AUTH-01 | Valid login | Confirmed active test user | Enter correct credentials | Authorized page loads | Not executed | NOT RUN | | |

### Bug log
| Bug ID | Description/reproduction | Severity | Owner | Action taken | Status | Retest/evidence |
|---|---|---|---|---|---|---|
| BUG-001 | | | | | Open | |

### Member contribution log
| Member | Assigned work | Delivered artifact/commit | Testing/documentation contribution | Leader rating/comments |
|---|---|---|---|---|
| | | | | |

### Testing summary to include at the end of the Phase 4 report

```text
Tested version / commit: ___
Test dates: ___
Environment and tools: ___
Total Test Cases: ___
Passed: ___
Failed: ___
Not Run / Blocked: ___
Fixed (defects): ___
Remaining Issues: ___
```

Count each test case once at its final recorded outcome. Keep retest history separately. Fixed defects are not an additional category of test cases. Include issue IDs for remaining problems and distinguish critical issues from lower-severity limitations.

### Usability feedback record

| Tester code | Device | Task | Completed? | Difficulty/comment | Improvement | Retest outcome |
|---|---|---|---|---|---|---|
| T01 | | | Not tested | | | |

### Evidence register

| Evidence ID | Phase / requirement / test | File or screenshot | Date / version | What it demonstrates |
|---|---|---|---|---|
| E001 | | | | |

## Recommended execution order

1. Confirm client rules and map proposal requirements; obtain the separate Phase 2 rubric.
2. Finish hosted Supabase setup, test accounts and live Laravel connection.
3. Verify end-to-end backend role workflows and correct confirmed defects.
4. Add Laravel JSON responses, student-written JavaScript and no-reload search/submission.
5. Finish responsive/accessibility checks, validation and error states.
6. Capture Phase 3 API notes, screenshots, contribution record and runnable package.
7. Execute Phase 4 security/functional/usability tests, fix issues and retest.
8. Complete reports, evidence, ratings and final clean-copy presentation rehearsal.

Security checks should start during implementation; Phase 4 consolidates the evidence and final fixes.


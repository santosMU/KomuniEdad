# Functional regression matrix

Executed 2026-09-29. Evidence: [PHP](evidence/automated/final-php-tests.txt), [database](evidence/automated/final-database-tests.txt). Each row maps to executed assertions, not necessarily full end-to-end or hosted coverage. See limitations in actual results.

| ID / requirement | Feature | Procedure / expected behavior | Actual result | Status | Test reference |
|---|---|---|---|---|---|
| FUNC-01 / FR-01 | Authentication | Valid/invalid login/logout | Mocked login and logout assertions pass | PASS LOCAL, bounded scope | AUTH-01..08 |
| FUNC-02 / FR-02 | Authorization | Access pages and modify foreign IDs | Roles and owners enforced in local tests | PASS LOCAL, bounded scope | AUTHZ-01..30; DB-03..13 |
| FUNC-03 / FR-03 | Account security | Password rules, disabled user, throttles | Local checks pass; shared hosted cache pending | PASS LOCAL, bounded scope | AUTH-12..25; DB-20..23 |
| FUNC-04 / FR-04 | Profile management | Update name/contact; attempt role injection | Profile saved and role unchanged | PASS LOCAL, bounded scope | FUNC-04; SQLI-04/05; AUTHZ-28 |
| FUNC-05 / FR-05 | Senior verification | Admin verifies; pending senior attempts enrollment | Verification saved; rule blocks unverified enrollment when enabled | PASS LOCAL, bounded scope | AUTHZ-22; domain.mjs verification scenario |
| FUNC-06 / FR-06 | Categories | Admin creates; coordinator attempts mutation | Admin saved; coordinator denied | PASS LOCAL, bounded scope | AUTHZ-23; DB-11 |
| FUNC-07 / FR-07 | Coordinator activities | Create/edit own; try foreign activity | Own create/render pass; foreign denied | PASS LOCAL, bounded scope | FUNC-07; AUTHZ-10/11; DB-06 |
| FUNC-08 / FR-08 | Admin activity governance | Admin opens other coordinator edit | 200 for administrator; DB admin workflow also executes | PASS LOCAL, bounded scope | AUTHZ-24 |
| FUNC-09 / FR-09 | Activity lifecycle | Complete activity; reject published-to-draft | Completion flow passes; invalid reversal rejected | PASS LOCAL, bounded scope | FUNC-09; ConsistencyTest |
| FUNC-10 / FR-10 | Discovery | Search and filter with escaped cards | Expected matching count; controls present | PASS LOCAL, bounded scope | CommunityTest; InteractiveTest; FUNC-10 |
| FUNC-11 / FR-11 | Details | Open created detail with omitted requirements | Detail 200 and fallback text | PASS LOCAL, bounded scope | FUNC-07 |
| FUNC-12 / FR-12 | Enrollment | Enroll eligible senior | Confirmed enrollment created | PASS LOCAL, bounded scope | CommunityTest; domain.mjs |
| FUNC-13 / FR-13 | Duplicate prevention | Enroll same senior twice | Second active enrollment rejected | PASS LOCAL, bounded scope | CommunityTest; domain.mjs |
| FUNC-14 / FR-14 | Capacity | Fill activity; next senior waitlists | Waitlisted at capacity; no sequential overbooking | PASS LOCAL, bounded scope | CommunityTest; domain.mjs |
| FUNC-15 / FR-15 | Withdrawal | Withdraw; promote next; test cutoff | Withdrawal/promotion assertions pass; closed registration rejected. Dedicated hosted withdrawal-cutoff evidence pending | PASS LOCAL, bounded scope | CommunityTest; InteractiveTest; domain.mjs |
| FUNC-16 / FR-16 | Enrollment management | Staff walk-in/cancel/confirm with reason | Capacity, reason and owner rules asserted | PASS LOCAL, bounded scope | domain.mjs; DB-10/14 |
| FUNC-17 / FR-17 | Attendance | Mark allowed participant; reject unpaid/foreign | Attendance stored; invalid operations rejected | PASS LOCAL, bounded scope | FUNC-09; domain.mjs; DB-08/15/16 |
| FUNC-18 / FR-18 | History | Own completed attendance shown; foreign hidden | Own feedback score shown; other senior title absent | PASS LOCAL, bounded scope | FUNC-09; AUTHZ-17 |
| FUNC-19 / FR-19 | Announcements | Create/global notice; reject retargeting | Allowed notice shown; foreign change rejected | PASS LOCAL, bounded scope | FUNC-09; AUTHZ-16; DB-12/13 |
| FUNC-20 / FR-20 | Feedback | Completed attended enrollment; second feedback | Eligibility and uniqueness asserted; literal stored | PASS LOCAL, bounded scope | PortalTest; domain.mjs; DB-18 |
| FUNC-21 / FR-21 | Coordinator summary | Open own reports/workspace | Pages render with expected activity; full hosted count reconciliation pending | PASS LOCAL, bounded scope | PortalTest; FUNC-09 |
| FUNC-22 / FR-22 | Admin reports | Open report as admin | Report renders; hosted aggregate reconciliation pending | PASS LOCAL, bounded scope | PortalTest; FUNC-09 |
| FUNC-23 / FR-23 | Administrative correction | Admin corrects attendance with reason | Corrected attendance and audit entry | PASS LOCAL, bounded scope | DB-17 |
| FUNC-24 / FR-24 | Audit logging | Inspect actor/action/reason and cash method | Expected audit entries found | PASS LOCAL, bounded scope | DB-14/17; domain.mjs |
| FUNC-25 / FR-25 | Database persistence | RPC write then SQL read under roles | Data and constraints persisted in test DB lifetime; not restart/hosted proof | PASS LOCAL, bounded scope | domain.mjs; DB-01..26 |
| FUNC-26 / FR-26 | Token expiration | Mock invalid/expired Auth response | 401 and session/cookie clearing | PASS LOCAL, bounded scope | AUTH-10/11 |
| FUNC-27 / FR-27 | Cash payment | Unpaid attendance denied; cash recorded; attendance allowed | Payment gating and cash audit assertions pass | PASS LOCAL, bounded scope | PortalTest; domain.mjs; DB-15/16 |

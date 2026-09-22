# Phase 1 traceability

Source: Group 4_KomuniEdad_Phase1_Documentation.pdf, revised 11 September 2026, sections IV-IX. The proposal is treated as project requirements, not as instructions authorizing external actions. This implementation follows PHP/Laravel + HTML/CSS/JavaScript/Bootstrap + Supabase PostgreSQL/Auth.

| Requirements | Implementation | Qualification |
|---|---|---|
| FR-01–03 Authentication and authorization | Supabase signup/password login, remote token verification, role checks, signout, SQL RLS | Hosted credentials/email flow not exercised here; no refresh-token flow |
| FR-04 Profile | Own name/contact/birthdate/address form and allowlisted RPC | Email changes remain with Auth administration |
| FR-05 Verification | Admin status form; optional database policy setting | Client policy must be confirmed; no document uploads |
| FR-06 Categories | Admin create/edit/activate/deactivate + audit | Deactivation preserves existing activity relationships |
| FR-07–09 Activities | Assigned coordinator CRUD via create/read/update/cancel/archive, admin oversight and lifecycle states | Historical records are retained rather than hard deleted |
| FR-10–11 Discovery/details | Search, categories, times, venue, coordinator, capacity, cutoff, requirements/status | List view rather than calendar; status filter included |
| FR-12–15 Enrollment | Senior registration, duplicate/capacity checks, waitlist and withdrawal | Row-locking implemented; multi-client stress test pending |
| FR-16 Staff participants | Confirm, waitlist, cancel/reject, manual encoding with existing senior UUID and required reason | Baseline auto-confirms available seats; no configurable approval-required mode |
| FR-17 Attendance | Assigned coordinator/admin attendance and remarks + audit | Admin corrections require a reason |
| FR-18 History | Own enrollments/attendance incl. archived activity details | All enrollment states retained |
| FR-19 Announcements | Activity notices and admin global notices; edit/archive; automatic schedule/venue notice | No email/SMS/push integration |
| FR-20 Feedback | One rating/comment after completed attendance | Validated in database |
| FR-21–22 Reports | Counts, category demand, coordinator workload; scope restricted | Summary tables only, no time-series charts/export; academic data volume |
| FR-23 Audit | Staff changes write actor/time/target/details; admin reads latest 100 | Trusted SQL owner bootstrap is outside app audit |

## Non-functional progress

Semantic labels, keyboard focus, large controls, responsive layout, Blade escaping, CSRF, request validation, login/signup throttling, encrypted server sessions, safe error messages, private credentials, RLS and mutation RPCs. These measures are not a WCAG certification or penetration-test result. HTTPS, hosted backups, production monitoring, client policy and recovery testing depend on deployment.

## Explicit remaining validation

Configure a development Supabase project; execute migrations and live role/permission smoke tests; test concurrent seat allocation; assess keyboard/mobile accessibility with real senior users; validate client policies, expected report periods and production deployment. Demo state is not proof of live persistence. Public registration is a senior account flow only.

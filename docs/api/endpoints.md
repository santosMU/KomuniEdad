# Laravel routes and Supabase functions

All state-changing web routes use POST and Laravel CSRF tokens. Protected routes use CommunitySession, which verifies the access token with Supabase Auth and reads the active profile. Controller role checks and SQL permissions both apply. Credentials stay in encrypted server-side sessions.

| Route | Methods | Scope |
|---|---|---|
| /login, /register | GET, POST | Public Auth forms; throttled |
| /logout | POST | Clears local session and requests Auth signout |
| / | GET | Senior activity discovery; staff redirect |
| /activities/{id} | GET | Authorized activity details |
| /activities/{id}/enroll | POST | Senior; enroll_in_activity |
| /enrollments/{id}/withdraw | POST | Owner senior; withdraw_enrollment |
| /profile | GET, POST | Own profile; update_own_profile |
| /history | GET | Own enrollment/attendance history |
| /history/{id}/feedback | POST | Eligible owner; submit_feedback |
| /workspace | GET | Coordinator/admin |
| /workspace/create | GET, POST | Coordinator/admin; save_activity |
| /workspace/{id}/edit | GET, POST | Assigned coordinator/admin; save_activity |
| /workspace/{id}/participants | GET | Assigned coordinator/admin; participant_list |
| /workspace/{id}/enrollment | POST | Assigned coordinator/admin; manage_enrollment |
| /workspace/{id}/attendance | POST | Assigned coordinator/admin; record_attendance |
| /announcements | GET, POST | Authenticated reads; staff save_announcement |
| /reports | GET | Assigned or organization-wide summaries |
| /administration | GET | Admin accounts/categories/audit |
| /administration/users/{id} | POST | Admin; manage_user with reason |
| /administration/categories | POST | Admin; save_category |
| /health | GET | Local process health; not database connectivity |
| /demo/role | POST | Local/testing demo only; 404 in live mode |

RPCs are in supabase/migrations. Authenticated users receive read grants filtered by RLS; direct table writes are revoked. Security-definer mutations validate identity/role/ownership and use a fixed empty search_path. Helper functions exposing private operations are not executable by API roles. Never expose a service-role key to bypass this model.

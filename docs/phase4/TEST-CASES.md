# Executed security test cases

Date: 2026-09-29. Evidence: [175-test PHP run](evidence/automated/final-php-tests.txt) and [26 additional DB checks plus existing suite](evidence/automated/final-database-tests.txt).

PASS means the stated assertions passed in the stated local scope. PHP tests use demo state or Http::fake for Supabase edge cases. Database checks run PGlite with auth.uid shim. No row below claims a hosted test. IDs describe requirements and can overlap executed methods; do not add table rows to obtain the suite count.

## A. Input validation

Source: tests/Feature/Phase4ValidationTest.php. Expected and actual status are equal in each executed row. Blank and whitespace inputs are normalized by Laravel.

| ID | Field / feature | Input | Expected | Actual | Status |
|---|---|---|---|---|---|
| VAL-01 | Registration full name | blank | 422 validation rejection | Same status asserted; named field error asserted | PASS LOCAL |
| VAL-02 | Registration email | blank | 422 validation rejection | Same status asserted; named field error asserted | PASS LOCAL |
| VAL-03 | Registration email | abc | 422 validation rejection | Same status asserted; named field error asserted | PASS LOCAL |
| VAL-04 | Password | short | 422 validation rejection | Same status asserted; named field error asserted | PASS LOCAL |
| VAL-05 | Confirmation | different | 422 validation rejection | Same status asserted; named field error asserted | PASS LOCAL |
| VAL-06 | Required full name | spaces only | 422 validation rejection | Same status asserted; named field error asserted | PASS LOCAL |
| VAL-07 | Full name | 121 characters | 422 validation rejection | Same status asserted; named field error asserted | PASS LOCAL |
| VAL-08 | Contact | 31 characters | 422 validation rejection | Same status asserted; named field error asserted | PASS LOCAL |
| VAL-09 | Birthdate | 2999-01-01 | 422 validation rejection | Same status asserted; named field error asserted | PASS LOCAL |
| VAL-10 | Address | 501 characters | 422 validation rejection | Same status asserted; named field error asserted | PASS LOCAL |
| VAL-11 | Activity title | 161 characters | 422 validation rejection | Same status asserted; named field error asserted | PASS LOCAL |
| VAL-12 | Description | 5001 characters | 422 validation rejection | Same status asserted; named field error asserted | PASS LOCAL |
| VAL-13 | Venue | 201 characters | 422 validation rejection | Same status asserted; named field error asserted | PASS LOCAL |
| VAL-14 | Capacity | 0 | 422 validation rejection | Same status asserted; named field error asserted | PASS LOCAL |
| VAL-15 | Capacity | -1 | 422 validation rejection | Same status asserted; named field error asserted | PASS LOCAL |
| VAL-16 | Capacity | 1.5 | 422 validation rejection | Same status asserted; named field error asserted | PASS LOCAL |
| VAL-17 | Capacity | 10001 | 422 validation rejection | Same status asserted; named field error asserted | PASS LOCAL |
| VAL-18 | End date | 2000-01-01, before future start | 422 validation rejection | Same status asserted; named field error asserted | PASS LOCAL |
| VAL-19 | Cutoff | 2999-01-01, after start | 422 validation rejection | Same status asserted; named field error asserted | PASS LOCAL |
| VAL-20 | Activity status | invalid | 422 validation rejection | Same status asserted; named field error asserted | PASS LOCAL |
| VAL-21 | Fee | -1 | 422 validation rejection | Same status asserted; named field error asserted | PASS LOCAL |
| VAL-22 | Paid activity fee | 0 | 422 validation rejection | Same status asserted; named field error asserted | PASS LOCAL |
| VAL-23 | Rating | 0 | 422 validation rejection | Same status asserted; named field error asserted | PASS LOCAL |
| VAL-24 | Rating | 6 | 422 validation rejection | Same status asserted; named field error asserted | PASS LOCAL |
| VAL-25 | Feedback | 2001 characters | 422 validation rejection | Same status asserted; named field error asserted | PASS LOCAL |
| VAL-26 | Attendance remarks | 1001 characters | 422 validation rejection | Same status asserted; named field error asserted | PASS LOCAL |
| VAL-27 | Enrollment reason | ab | 422 validation rejection | Same status asserted; named field error asserted | PASS LOCAL |
| VAL-28 | Activity ID | missing | 404 not found | Same status asserted | PASS LOCAL |
| VAL-29 | Enrollment ID | missing | 404 not found | Same status asserted | PASS LOCAL |
| VAL-30 | Live UUID | not-a-uuid | 422 validation rejection | Same status asserted | PASS LOCAL |
| VAL-31 | Announcement title | 161 characters | 422 validation rejection | Same status asserted; named field error asserted | PASS LOCAL |
| VAL-32 | Announcement message | 5001 characters | 422 validation rejection | Same status asserted; named field error asserted | PASS LOCAL |
| VAL-33 | Role | owner | 422 validation rejection | Same status asserted; named field error asserted | PASS LOCAL |
| VAL-34 | Account status | unknown | 422 validation rejection | Same status asserted; named field error asserted | PASS LOCAL |
| VAL-35 | Verification status | unknown | 422 validation rejection | Same status asserted; named field error asserted | PASS LOCAL |

## B. SQL injection

Source: tests/Feature/Phase4InjectionTest.php; DB-01/02/12/14/16/18/19 supplement writable fields and UUID rejection. No destructive payloads.

| ID | Entry point | Input | Expected / actual asserted | Status |
|---|---|---|---|---|
| SQLI-01 | Login email | `' OR '1'='1` | 422, no auth cookie | PASS LOCAL |
| SQLI-02 | Search q | `' OR 1=1 --` | 200, zero matches | PASS LOCAL |
| SQLI-03 | Category filter | `' OR '1'='1` | 200, zero matches | PASS LOCAL |
| SQLI-04 | Profile full_name | `' OR '1'='1` | Text stored literally; intended record only | PASS LOCAL |
| SQLI-05 | Profile address | `' OR '1'='1` | Text stored literally; intended record only | PASS LOCAL |
| SQLI-06 | Activity title | `' OR '1'='1` | Text stored literally; intended record only | PASS LOCAL |
| SQLI-07 | Activity description | `' OR '1'='1` | Text stored literally; intended record only | PASS LOCAL |
| SQLI-08 | Activity venue | `' OR '1'='1` | Text stored literally; intended record only | PASS LOCAL |
| SQLI-09 | Announcement title | `' OR '1'='1` | Text stored literally; intended record only | PASS LOCAL |
| SQLI-10 | Announcement message | `' OR '1'='1` | Text stored literally; intended record only | PASS LOCAL |
| SQLI-11 | Feedback comments | `' OR '1'='1` | Text stored literally; intended record only | PASS LOCAL |
| SQLI-12 | Enrollment reason | `' OR '1'='1` | Single expected enrollment; DB reason stored literally in audit | PASS LOCAL |
| SQLI-13 | Attendance remarks | `' OR '1'='1` | Text stored literally; intended record only | PASS LOCAL |
| SQLI-14 | Record UUID | `' OR '1'='1` | 422, no SQLSTATE disclosure; DB typed UUID rejects input | PASS LOCAL |

## C. Authentication

Source: tests/Feature/Phase4AuthenticationTest.php. Invalid/expired identities and upstream errors are mocked. Rate limits AUTH-15/16 are within one test application; AUTH-25 below explicitly rebuilds it.

| ID | Scenario and expected result | Actual result | Status |
|---|---|---|---|
| AUTH-01 | Valid mocked Auth reply sets cookie and redirects / | Expected assertion passed | PASS LOCAL |
| AUTH-02 | Wrong credentials rejected | Expected assertion passed | PASS LOCAL |
| AUTH-03 | Nonexistent account rejected | Expected assertion passed | PASS LOCAL |
| AUTH-04 | Blank email rejected | Expected assertion passed | PASS LOCAL |
| AUTH-05 | Blank password rejected | Expected assertion passed | PASS LOCAL |
| AUTH-06 | Malformed email rejected | Expected assertion passed | PASS LOCAL |
| AUTH-07 | Logout clears token/cookie | Expected assertion passed | PASS LOCAL |
| AUTH-08 | Protected page after logout redirects login | Expected assertion passed | PASS LOCAL |
| AUTH-09 | Missing token JSON returns 401 | Expected assertion passed | PASS LOCAL |
| AUTH-10 | Invalid token clears session, 401 | Expected assertion passed | PASS LOCAL |
| AUTH-11 | Expired token clears session, 401 | Expected assertion passed | PASS LOCAL |
| AUTH-12 | Disabled profile returns 403 | Expected assertion passed | PASS LOCAL |
| AUTH-13 | Login rotates session ID | Expected assertion passed | PASS LOCAL |
| AUTH-14 | Logout rotates CSRF token | Expected assertion passed | PASS LOCAL |
| AUTH-15 | Seventh login request returns 429 | Expected assertion passed | PASS LOCAL |
| AUTH-16 | Sixth registration request returns 429 | Expected assertion passed | PASS LOCAL |
| AUTH-17 | Short password rejected | Expected assertion passed | PASS LOCAL |
| AUTH-18 | Mismatched confirmation rejected | Expected assertion passed | PASS LOCAL |
| AUTH-19 | Signup sends full_name metadata only | Expected assertion passed | PASS LOCAL |
| AUTH-20 | Raw upstream error marker withheld | Expected assertion passed | PASS LOCAL |
| AUTH-21 | Access cookie HttpOnly | Expected assertion passed | PASS LOCAL |
| AUTH-22 | Access cookie SameSite lax | Expected assertion passed | PASS LOCAL |
| AUTH-23 | Access cookie expiry within expires_in | Expected assertion passed | PASS LOCAL |
| AUTH-24 | Access cookie Secure when configured | Expected assertion passed | PASS LOCAL |
| AUTH-25 | Six rejected logins then seventh 429 across fresh applications with file limiter and array default | All seven responses matched | PASS LOCAL, Phase4IntegrityTest |

## D. Authorization

Source: tests/Feature/Phase4AuthorizationTest.php. Database checks DB-03..13, DB-17 and DB-20..23 independently exercise RLS/RPC ownership. HTTP 404 deliberately conceals foreign object existence; DB denials are exceptions, not HTTP responses.

| ID | User / action / expected access | Actual result | Status |
|---|---|---|---|
| AUTHZ-01 | Senior GET workspace denied 403 | Expected assertion passed | PASS LOCAL |
| AUTHZ-02 | Senior GET create denied 403 | Expected assertion passed | PASS LOCAL |
| AUTHZ-03 | Senior GET reports denied 403 | Expected assertion passed | PASS LOCAL |
| AUTHZ-04 | Senior GET administration denied 403 | Expected assertion passed | PASS LOCAL |
| AUTHZ-05 | Senior admin mutation denied 403 | Expected assertion passed | PASS LOCAL |
| AUTHZ-06 | Coordinator workspace allowed 200 | Expected assertion passed | PASS LOCAL |
| AUTHZ-07 | Coordinator administration denied 403 | Expected assertion passed | PASS LOCAL |
| AUTHZ-08 | Coordinator user mutation denied 403 | Expected assertion passed | PASS LOCAL |
| AUTHZ-09 | Coordinator category mutation denied 403 | Expected assertion passed | PASS LOCAL |
| AUTHZ-10 | Coordinator own edit allowed 200 | Expected assertion passed | PASS LOCAL |
| AUTHZ-11 | Other coordinator edit denied 404 | Expected assertion passed | PASS LOCAL |
| AUTHZ-12 | Other coordinator roster denied 404 | Expected assertion passed | PASS LOCAL |
| AUTHZ-13 | Other coordinator attendance denied 404 | Expected assertion passed | PASS LOCAL |
| AUTHZ-14 | Other coordinator payment denied 404 | Expected assertion passed | PASS LOCAL |
| AUTHZ-15 | Other coordinator enrollment denied 404 | Expected assertion passed | PASS LOCAL |
| AUTHZ-16 | Other coordinator announcement retarget denied | Expected assertion passed | PASS LOCAL |
| AUTHZ-17 | Other senior history hidden | Expected assertion passed | PASS LOCAL |
| AUTHZ-18 | Other senior withdrawal denied | Expected assertion passed | PASS LOCAL |
| AUTHZ-19 | Other senior feedback denied | Expected assertion passed | PASS LOCAL |
| AUTHZ-20 | Senior draft details hidden | Expected assertion passed | PASS LOCAL |
| AUTHZ-21 | Admin administration allowed | Expected assertion passed | PASS LOCAL |
| AUTHZ-22 | Admin user verification saved | Expected assertion passed | PASS LOCAL |
| AUTHZ-23 | Admin category saved | Expected assertion passed | PASS LOCAL |
| AUTHZ-24 | Admin other activity edit allowed | Expected assertion passed | PASS LOCAL |
| AUTHZ-25 | Admin correction persisted audit | DB-17 verifies audit row for admin correction | PASS LOCAL |
| AUTHZ-26 | Nonexistent coordinator activity denied 404 | Expected assertion passed | PASS LOCAL |
| AUTHZ-27 | Registration cannot assign admin | Expected assertion passed | PASS LOCAL |
| AUTHZ-28 | Profile cannot promote role | Expected assertion passed | PASS LOCAL |
| AUTHZ-29 | Disabled admin denied 403 | Expected assertion passed | PASS LOCAL |
| AUTHZ-30 | Unauthenticated pages/mutation denied 401 | Expected assertion passed | PASS LOCAL |

## E. XSS

Source: tests/Feature/Phase4XssTest.php. PHP checks inspect encoded markup; browser evidence separately verifies stored and reflected input without an alert. Feedback comments are stored but not rendered in current history, which displays only the score.

| ID | Location | Payload | Expected / actual asserted | Status |
|---|---|---|---|---|
| XSS-01 | Activity title | `<img src=x onerror=alert(1)>` | Encoded output present; raw executable markup absent | PASS LOCAL |
| XSS-02 | Activity description | `<img src=x onerror=alert(1)>` | Encoded output present; raw executable markup absent | PASS LOCAL |
| XSS-03 | Venue | `<img src=x onerror=alert(1)>` | Encoded output present; raw executable markup absent | PASS LOCAL |
| XSS-04 | Requirements | `<img src=x onerror=alert(1)>` | Encoded output present; raw executable markup absent | PASS LOCAL |
| XSS-05 | Announcement title | `<script>alert(1)</script>` | Encoded output present; raw executable markup absent | PASS LOCAL |
| XSS-06 | Announcement message | `<script>alert(1)</script>` | Encoded output present; raw executable markup absent | PASS LOCAL |
| XSS-07 | Profile name | `"><svg/onload=alert(1)>` | Encoded output present; raw executable markup absent | PASS LOCAL |
| XSS-08 | Profile address | `"><svg/onload=alert(1)>` | Encoded output present; raw executable markup absent | PASS LOCAL |
| XSS-09 | Feedback comment | `<img src=x onerror=alert(1)>` | Stored literal; executable marker absent from history | PASS LOCAL |
| XSS-10 | Reflected search | `<img src=x onerror=alert(1)>` | Unescaped marker absent in HTML | PASS LOCAL |
| XSS-11 | JSON result HTML | `<img src=x onerror=alert(1)>` | Encoded output present; raw executable markup absent | PASS LOCAL |
| XSS-12 | Validation errors | `<img src=x onerror=alert(1)>` | Encoded output present; raw executable markup absent | PASS LOCAL |
| XSS-13 | Flash/status | `<img src=x onerror=alert(1)>` | Encoded output present; raw executable markup absent | PASS LOCAL |

## Supporting integrity tests

| ID | Procedure | Expected / actual | Status |
|---|---|---|---|
| CSRF-01 | Missing and invalid tokens on seven POST routes with actual CSRF middleware enabled | 419 for each | PASS LOCAL |
| CSRF-02 | Correct X-CSRF-TOKEN on profile POST | 200 | PASS LOCAL |
| ERROR-01..03 | Upstream 500, 503, 400 with private markers | 503/503/422; no markers | PASS MOCKED |
| ERROR-04 | Connection exception | 503, safe body, session preserved | PASS MOCKED |
| ERROR-05 | Referer path beginning double slash | Redirect becomes /external.example/path | PASS LOCAL |
| ERROR-06 | Malformed live activity UUID | 422; no RPC sent | PASS MOCKED |
| ERROR-07 | Forbidden admin and invalid profile | Safe 403 and 422 without trace/exception | PASS LOCAL |
| HYP-02 | Inspect profile form nesting | Balanced non-nested forms | PASS MOCKED |
| HYP-04 | Public response headers and auth controls | CSP local scripts, headers, external password handler, relative logo | PASS LOCAL |

HYP-01 maps to AUTH-21..24. HYP-03 production values are a configuration review and deployment task, not proof of hosted configuration.


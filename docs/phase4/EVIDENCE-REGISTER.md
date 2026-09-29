# Evidence register

Date: 2026-09-29. Final local files cover working-tree changes after 4cca23f, pending a final commit.

| ID | Requirement / scope | File | What it proves |
|---|---|---|---|
| E01 | AUTOMATED PHP | evidence/automated/final-php-tests.txt | 175 cases, 603 assertions passed |
| E02 | AUTOMATED PostgreSQL | evidence/automated/final-database-tests.txt | Existing scenarios plus DB-01..26 passed |
| E03 | AUTOMATED JS | evidence/automated/final-js-check.txt | Syntax command exit 0 |
| E04 | DEPENDENCIES | evidence/automated/final-composer-audit.txt | No Composer advisories at run time |
| E05 | DEPENDENCIES | evidence/automated/final-npm-audit.txt | No production npm vulnerabilities at run time |
| E06 | SCANNER hosted initial | evidence/scanner/zap-hosted-sanitized.json | Public-page passive findings before verified deployment |
| E07 | SCANNER local before | evidence/scanner/zap-local-before-sanitized.json | Pre-hardening passive alerts |
| E08 | SCANNER local retest | evidence/scanner/zap-local-sanitized.json | Remaining style/CSRF-cookie/session alerts |
| E09 | LOCAL DEMO validation | evidence/screenshots/P4-VAL-01-invalid-registration.png | Password errors visible, password fields empty |
| E10 | LOCAL DEMO search | evidence/screenshots/P4-FUNC-10-search.png | Gardening result and updated status |
| E11 | LOCAL DEMO mobile | evidence/screenshots/P4-USAB-375.png | Discovery at 375x812 |
| E12 | LOCAL DEMO tablet | evidence/screenshots/P4-USAB-768.png | Discovery at 768x1024 |
| E13 | LOCAL DEMO desktop | evidence/screenshots/P4-USAB-1366.png | Discovery at 1366x768 |
| E14 | LOCAL DEMO reflected XSS | evidence/screenshots/P4-XSS-02-reflected-escaped.png | Literal search marker, zero results, no alert observed |
| E15 | LOCAL DEMO stored XSS | evidence/screenshots/P4-XSS-01-stored-escaped.png | Literal profile marker after save, no alert observed |

Earlier baseline and before-fix logs are retained under evidence/automated. They include intentionally failing regressions and must not be presented as final failures. Screenshots support observed UI behavior; they are not substitutes for RLS/Auth or human usability results. No HUMAN USABILITY or final LIVE SYNTHETIC evidence is included.


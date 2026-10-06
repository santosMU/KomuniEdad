# Human usability test plan

Recruit at least three actual testers. Use IDs T01, T02, T03 instead of personal identities. Never enter invented feedback.

## Setup

Use synthetic accounts on an authorized test deployment. Record device, OS, browser/version and viewport. Tell testers this is a class prototype and avoid collecting personal or medical details.

## Tasks

1. Sign in.
2. Find a named activity using search/category.
3. Open details and identify venue, date, fee and cutoff.
4. Enroll and locate My Activities.
5. Withdraw if permitted.
6. Update a synthetic profile field.
7. Find Help and announcements.
8. Sign out and try the protected page.
9. Coordinator: manage own participants and record a synthetic cash payment.
10. Administrator: verify a synthetic senior and inspect an audit entry.

Observe completion without prompting. Record completion, time if measured, assistance, confusion, error recovery and suggested improvement.

## Ratings

Use 1 (very difficult/poor) to 5 (very easy/clear). Rate navigation, readability, interface, button placement, error messages, mobile responsiveness and overall ease. Permit N/A with a reason.

## Engineering checks

Check 375x812, 768x1024 and 1366x768 for overflow and controls. Test keyboard focus, Escape/drawer behavior, zoom, labels, errors and table scrolling. Browser automation supplements real human feedback; it cannot replace it.

After fixes, repeat the failed task with a tester and link the bug/retest evidence.


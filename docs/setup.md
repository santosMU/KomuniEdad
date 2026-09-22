# Development Supabase setup

Apply schema.sql, all migrations in order, and seed.sql only to a development database. The migrations create new tables and are not idempotent. Do not replay them against an existing installation.

## Existing Auth accounts

The profile trigger covers future Auth registrations. For accounts created before the migration, a trusted project owner can backfill minimum-privilege profiles:

```sql
insert into public.profiles(user_id,full_name)
select id,left(coalesce(nullif(trim(raw_user_meta_data->>'full_name'),''),'New member'),120)
from auth.users on conflict(user_id) do nothing;
insert into public.senior_profiles(user_id)
select user_id from public.profiles where role='senior' on conflict(user_id) do nothing;
```

Inspect the exact Auth account you control before assigning the initial administrator. Do not derive roles from user metadata or run bulk role promotions. In trusted SQL Editor, substitute only the verified account UUID:

```sql
update public.profiles set role='admin' where user_id='YOUR_VERIFIED_ACCOUNT_UUID';
```

Subsequent changes should use the administrator UI so the actor and reason are audited. Coordinators must be active before activity assignment. Create activities from the workspace; dates are entered/displayed in Asia/Manila and stored as timezone-aware timestamps.

## Initial live smoke test

1. Register two senior test accounts and confirm their email if required.
2. As a coordinator, create an Open activity with capacity one and a future cutoff.
3. Enroll the first senior: Confirmed. Enroll the second: Waitlisted.
4. Withdraw the first senior before cutoff: the eligible waitlisted senior is promoted.
5. Verify another coordinator cannot edit or read the private roster of that activity.
6. After the activity starts, record attendance. Complete after its end time. Verify feedback is accepted once for an attended completed enrollment.
7. Check admin audit events for privileged mutations and denial of private data to anonymous users.

Demo staff screens use isolated session data and are intended for presentation. SQL is the authority for live lifecycle rules, capacity, verification and audit. Demo fixture seat counts include illustrative participants who are not in the demo roster.

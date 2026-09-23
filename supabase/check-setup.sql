-- Read-only indicators. Run in Supabase SQL Editor before choosing migrations.
-- These checks detect key objects; they do not prove every policy/function is correct.
select '01 domain' as migration,
 to_regclass('public.profiles') is not null and
 to_regprocedure('public.enroll_in_activity(uuid)') is not null as key_objects_present
union all
select '02 workflows', to_regclass('public.settings') is not null and
 to_regprocedure('public.save_activity(jsonb,uuid)') is not null
union all
select '03 participants', to_regprocedure('public.manage_enrollment(uuid,uuid,text,text)') is not null and
 to_regprocedure('public.promote_waitlist(uuid,uuid)') is not null
union all
select '04 capacity waitlist', to_regprocedure('public.promote_after_capacity_increase()') is not null;

select c.relname as table_name,c.relrowsecurity as rls_enabled
from pg_class c join pg_namespace n on n.oid=c.relnamespace
where n.nspname='public' and c.relname in
 ('profiles','senior_profiles','categories','activities','enrollments','attendance','announcements','feedback','audit_logs','settings')
order by c.relname;

select count(*) as authentication_accounts from auth.users;

-- First administrator setup. Run once in Supabase SQL Editor as project owner.
-- First create YOUR account in Authentication > Users > Add user.
-- Replace NULL below with its quoted UUID, e.g. '12345678-1234-1234-1234-123456789012'.
-- Do not enter a password, API key, or an unrelated person's account ID here.
begin;
do $setup$
declare
 account_uuid uuid := null; -- REPLACE NULL with your own account UUID in single quotes
 previous_role text;
begin
 if account_uuid is null then raise exception 'Replace account_uuid NULL with your own Authentication user UUID first.'; end if;
 if not exists(select 1 from auth.users where id=account_uuid) then raise exception 'That UUID does not exist in Supabase Authentication.'; end if;
 insert into public.profiles(user_id,full_name)
 select id,left(coalesce(nullif(trim(raw_user_meta_data->>'full_name'),''),'Project administrator'),120)
 from auth.users where id=account_uuid on conflict(user_id) do nothing;
 select role into previous_role from public.profiles where user_id=account_uuid and account_status='active' for update;
 if not found then raise exception 'This profile is disabled. Review its status before provisioning staff access.'; end if;
 if previous_role<>'admin' then
  update public.profiles set role='admin' where user_id=account_uuid;
  insert into public.audit_logs(actor_id,action_type,target_type,target_id,details)
  values(null,'setup.admin_provisioned','profile',account_uuid,jsonb_build_object('method','Trusted SQL Editor setup by project owner','previous_role',previous_role));
 end if;
end $setup$;
commit;

select user_id,full_name,role,account_status from public.profiles where role='admin';

begin;

-- Repair and harden the Auth -> application profile provisioning path.
-- Safe to apply after the previous Phase 3 migrations.
create or replace function public.provision_profile()
returns trigger
language plpgsql
security definer
set search_path = ''
as $$
declare
  safe_name text;
begin
  safe_name := left(
    coalesce(
      nullif(btrim(coalesce(new.raw_user_meta_data->>'full_name', '')), ''),
      nullif(split_part(coalesce(to_jsonb(new)->>'email', ''), '@', 1), ''),
      'New member'
    ),
    120
  );

  insert into public.profiles(user_id, full_name, role, account_status)
  values(new.id, safe_name, 'senior', 'active')
  on conflict(user_id) do update
    set full_name = excluded.full_name,
        role = 'senior',
        account_status = 'active';

  insert into public.senior_profiles(user_id, verification_status)
  values(new.id, 'pending')
  on conflict(user_id) do nothing;

  return new;
end
$$;

drop trigger if exists provision_profile on auth.users;
create trigger provision_profile
after insert on auth.users
for each row execute function public.provision_profile();

-- Repair Auth users created before the profile trigger was installed.
insert into public.profiles(user_id, full_name, role, account_status)
select
  u.id,
  left(
    coalesce(
      nullif(btrim(coalesce(u.raw_user_meta_data->>'full_name', '')), ''),
      nullif(split_part(coalesce(to_jsonb(u)->>'email', ''), '@', 1), ''),
      'New member'
    ),
    120
  ),
  'senior',
  'active'
from auth.users u
where not exists (
  select 1 from public.profiles p where p.user_id = u.id
)
on conflict(user_id) do nothing;

insert into public.senior_profiles(user_id, verification_status)
select p.user_id, 'pending'
from public.profiles p
where p.role = 'senior'
on conflict(user_id) do nothing;

revoke execute on function public.provision_profile() from public, anon, authenticated;

commit;

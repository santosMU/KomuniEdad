begin;

alter table public.profiles
  add column if not exists avatar_path text;

alter table public.profiles
  drop constraint if exists profile_avatar_path_valid;

alter table public.profiles
  add constraint profile_avatar_path_valid
  check (
    avatar_path is null
    or (
      length(avatar_path) <= 100
      and avatar_path = user_id::text || '/avatar'
    )
  );

create or replace function public.update_own_profile(payload jsonb)
returns void
language plpgsql
security definer
set search_path='' as $$
declare
  requested_avatar text;
begin
  if public.current_app_role() is null then raise exception 'Not authorized'; end if;

  if length(trim(payload->>'full_name')) not between 1 and 120
     or length(coalesce(payload->>'contact_number','')) > 30
     or length(coalesce(payload->>'address','')) > 500 then
    raise exception 'Invalid profile';
  end if;

  if nullif(payload->>'birthdate','')::date > current_date then
    raise exception 'Birthdate cannot be in the future';
  end if;

  if payload ? 'avatar_path' then
    requested_avatar := nullif(payload->>'avatar_path','');
    if requested_avatar is not null
       and requested_avatar <> auth.uid()::text || '/avatar' then
      raise exception 'Invalid profile photo path';
    end if;
  end if;

  update public.profiles
  set full_name=trim(payload->>'full_name'),
      contact_number=payload->>'contact_number',
      avatar_path=case
        when payload ? 'avatar_path' then requested_avatar
        else avatar_path
      end
  where user_id=auth.uid();

  if public.current_app_role()='senior' then
    update public.senior_profiles
    set birthdate=nullif(payload->>'birthdate','')::date,
        address=payload->>'address'
    where user_id=auth.uid();
  end if;
end $$;

drop function if exists public.activity_directory();
create function public.activity_directory()
returns table(
  activity_id uuid,
  category_id uuid,
  coordinator_id uuid,
  title text,
  description text,
  venue text,
  start_at timestamptz,
  end_at timestamptz,
  cutoff_at timestamptz,
  capacity integer,
  requirements text,
  status text,
  is_free boolean,
  fee numeric,
  image text,
  image_url text,
  categories jsonb,
  coordinator_name text,
  coordinator_avatar_path text,
  confirmed bigint,
  waitlisted bigint
)
language sql
stable
security definer
set search_path = ''
as $$
  select
    a.activity_id,
    a.category_id,
    a.coordinator_id,
    a.title,
    a.description,
    a.venue,
    a.start_at,
    a.end_at,
    a.cutoff_at,
    a.capacity,
    a.requirements,
    a.status,
    a.is_free,
    a.fee,
    a.image,
    a.image_url,
    jsonb_build_object('name', c.name) as categories,
    coalesce(p.full_name, 'Community coordinator') as coordinator_name,
    p.avatar_path as coordinator_avatar_path,
    count(e.enrollment_id) filter (where e.status in ('confirmed','completed')) as confirmed,
    count(e.enrollment_id) filter (where e.status = 'waitlisted') as waitlisted
  from public.activities a
  left join public.categories c on c.category_id = a.category_id
  left join public.profiles p on p.user_id = a.coordinator_id
  left join public.enrollments e on e.activity_id = a.activity_id
  where public.current_app_role() is not null
    and (
      a.status not in ('draft','archived')
      or public.manages_activity(a.activity_id)
      or (
        public.current_app_role() = 'senior'
        and exists (
          select 1
          from public.enrollments own
          where own.activity_id = a.activity_id
            and own.senior_id = auth.uid()
        )
      )
    )
  group by
    a.activity_id, a.category_id, a.coordinator_id, a.title, a.description,
    a.venue, a.start_at, a.end_at, a.cutoff_at, a.capacity, a.requirements,
    a.status, a.is_free, a.fee, a.image, a.image_url, c.name, p.full_name,
    p.avatar_path
  order by a.start_at asc;
$$;

revoke execute on function public.activity_directory() from public,anon;
grant execute on function public.activity_directory() to authenticated;

-- Supabase Storage is present in hosted projects but not in the lightweight
-- PGlite test harness. Create the bucket and policies only when Storage exists.
do $$
begin
  if to_regclass('storage.buckets') is not null
     and to_regclass('storage.objects') is not null then

    insert into storage.buckets(id,name,public,file_size_limit,allowed_mime_types)
    values(
      'profile-photos',
      'profile-photos',
      true,
      2097152,
      array['image/jpeg','image/png','image/webp']
    )
    on conflict(id) do update
      set public=true,
          file_size_limit=2097152,
          allowed_mime_types=array['image/jpeg','image/png','image/webp'];

    execute 'drop policy if exists "Profile photos own select" on storage.objects';
    execute 'drop policy if exists "Profile photos own insert" on storage.objects';
    execute 'drop policy if exists "Profile photos own update" on storage.objects';
    execute 'drop policy if exists "Profile photos own delete" on storage.objects';

    execute $policy$
      create policy "Profile photos own select"
      on storage.objects for select
      to authenticated
      using (
        bucket_id = 'profile-photos'
        and (storage.foldername(name))[1] = (select auth.uid()::text)
      )
    $policy$;

    execute $policy$
      create policy "Profile photos own insert"
      on storage.objects for insert
      to authenticated
      with check (
        bucket_id = 'profile-photos'
        and (storage.foldername(name))[1] = (select auth.uid()::text)
        and name = (select auth.uid()::text) || '/avatar'
      )
    $policy$;

    execute $policy$
      create policy "Profile photos own update"
      on storage.objects for update
      to authenticated
      using (
        bucket_id = 'profile-photos'
        and (storage.foldername(name))[1] = (select auth.uid()::text)
      )
      with check (
        bucket_id = 'profile-photos'
        and (storage.foldername(name))[1] = (select auth.uid()::text)
        and name = (select auth.uid()::text) || '/avatar'
      )
    $policy$;

    execute $policy$
      create policy "Profile photos own delete"
      on storage.objects for delete
      to authenticated
      using (
        bucket_id = 'profile-photos'
        and (storage.foldername(name))[1] = (select auth.uid()::text)
      )
    $policy$;
  end if;
end $$;

commit;

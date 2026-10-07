begin;

alter table public.profiles
  drop constraint if exists profile_avatar_path_valid;

alter table public.profiles
  add constraint profile_avatar_path_valid
  check (
    avatar_path is null
    or (
      length(avatar_path) <= 180
      and (
        avatar_path = user_id::text || '/avatar'
        or (
          avatar_path like user_id::text || '/avatar-%'
          and avatar_path ~ '/avatar-[0-9]{17}-[0-9a-f]{8}\.(jpg|png|webp)
    )
  );

do $$
begin
  if to_regclass('storage.objects') is not null then
    execute 'drop policy if exists "Profile photos own insert" on storage.objects';
    execute 'drop policy if exists "Profile photos own update" on storage.objects';

    execute $policy$
      create policy "Profile photos own insert"
      on storage.objects for insert
      to authenticated
      with check (
        bucket_id = 'profile-photos'
        and (storage.foldername(name))[1] = (select auth.uid()::text)
        and name like (select auth.uid()::text) || '/avatar-%'
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
        and name like (select auth.uid()::text) || '/avatar-%'
      )
    $policy$;
  end if;
end $$;

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
       and (
         length(requested_avatar) > 180
         or (
           requested_avatar <> auth.uid()::text || '/avatar'
           and (
             requested_avatar not like auth.uid()::text || '/avatar-%'
             or requested_avatar !~ '/avatar-[0-9]{17}-[0-9a-f]{8}\.(jpg|png|webp)
       ) then
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

commit;
        )
      )
    )
  );

do $$
begin
  if to_regclass('storage.objects') is not null then
    execute 'drop policy if exists "Profile photos own insert" on storage.objects';
    execute 'drop policy if exists "Profile photos own update" on storage.objects';

    execute $policy$
      create policy "Profile photos own insert"
      on storage.objects for insert
      to authenticated
      with check (
        bucket_id = 'profile-photos'
        and (storage.foldername(name))[1] = (select auth.uid()::text)
        and name like (select auth.uid()::text) || '/avatar-%'
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
        and name like (select auth.uid()::text) || '/avatar-%'
      )
    $policy$;
  end if;
end $$;

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
       and (
         length(requested_avatar) > 180
         or requested_avatar not like auth.uid()::text || '/avatar-%'
         or requested_avatar !~ '/avatar-[0-9]{17}-[0-9a-f]{8}\.(jpg|png|webp)$'
       ) then
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

commit;
           )
         )
       ) then
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

commit;
        )
      )
    )
  );

do $$
begin
  if to_regclass('storage.objects') is not null then
    execute 'drop policy if exists "Profile photos own insert" on storage.objects';
    execute 'drop policy if exists "Profile photos own update" on storage.objects';

    execute $policy$
      create policy "Profile photos own insert"
      on storage.objects for insert
      to authenticated
      with check (
        bucket_id = 'profile-photos'
        and (storage.foldername(name))[1] = (select auth.uid()::text)
        and name like (select auth.uid()::text) || '/avatar-%'
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
        and name like (select auth.uid()::text) || '/avatar-%'
      )
    $policy$;
  end if;
end $$;

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
       and (
         length(requested_avatar) > 180
         or requested_avatar not like auth.uid()::text || '/avatar-%'
         or requested_avatar !~ '/avatar-[0-9]{17}-[0-9a-f]{8}\.(jpg|png|webp)$'
       ) then
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

commit;
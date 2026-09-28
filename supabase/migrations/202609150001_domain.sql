-- Apply after schema.sql in a development Supabase project.
begin;
create table public.profiles (
 user_id uuid primary key references auth.users(id) on delete cascade,
 full_name text not null check (length(trim(full_name)) between 1 and 120),
 role text not null default 'senior' check(role in ('senior','coordinator','admin')),
 account_status text not null default 'active' check(account_status in ('active','disabled')),
 created_at timestamptz not null default now()
);
create table public.senior_profiles (
 user_id uuid primary key references public.profiles(user_id) on delete cascade,
 birthdate date, address text,
 verification_status text not null default 'pending' check(verification_status in ('pending','verified','rejected'))
);
create table public.categories (
 category_id uuid primary key default gen_random_uuid(), name text unique not null,
 description text, is_active boolean not null default true
);
create table public.activities (
 activity_id uuid primary key default gen_random_uuid(),
 category_id uuid not null references public.categories(category_id),
 coordinator_id uuid not null references public.profiles(user_id),
 title text not null check(length(trim(title)) between 1 and 160), description text not null,
 venue text not null, start_at timestamptz not null, end_at timestamptz not null,
 cutoff_at timestamptz not null, capacity integer not null check(capacity > 0),
 requirements text not null default '',
 status text not null default 'draft' check(status in ('draft','open','full','ongoing','completed','cancelled','archived')),
 check(end_at > start_at), check(cutoff_at <= start_at)
);
create table public.enrollments (
 enrollment_id uuid primary key default gen_random_uuid(),
 activity_id uuid not null references public.activities(activity_id),
 senior_id uuid not null references public.profiles(user_id),
 status text not null check(status in ('pending','confirmed','waitlisted','cancelled','completed')),
 enrolled_at timestamptz not null default now(), cancelled_at timestamptz
);
create unique index one_active_enrollment on public.enrollments(activity_id,senior_id) where status <> 'cancelled';
create index enrollment_queue on public.enrollments(activity_id,status,enrolled_at,enrollment_id);
create table public.attendance (
 attendance_id uuid primary key default gen_random_uuid(),
 enrollment_id uuid unique not null references public.enrollments(enrollment_id),
 attended boolean not null, remarks text, recorded_by uuid not null references public.profiles(user_id),
 recorded_at timestamptz not null default now()
);
create table public.announcements (
 announcement_id uuid primary key default gen_random_uuid(), activity_id uuid references public.activities(activity_id),
 posted_by uuid not null references public.profiles(user_id), title text not null, message text not null,
 posted_at timestamptz not null default now(), archived_at timestamptz
);
create table public.feedback (
 feedback_id uuid primary key default gen_random_uuid(), enrollment_id uuid unique not null references public.enrollments(enrollment_id),
 rating integer not null check(rating between 1 and 5), comments text, submitted_at timestamptz not null default now()
);
create table public.audit_logs (
 log_id uuid primary key default gen_random_uuid(), actor_id uuid references public.profiles(user_id),
 action_type text not null, target_type text not null, target_id uuid not null,
 details jsonb not null default '{}', created_at timestamptz not null default now()
);
create function public.current_app_role() returns text language sql stable security definer set search_path = '' as $$
 select role from public.profiles where user_id = auth.uid() and account_status = 'active'
$$;
create function public.manages_activity(target uuid) returns boolean language sql stable security definer set search_path = '' as $$
 select coalesce(public.current_app_role() = 'admin' or (public.current_app_role() = 'coordinator' and exists(select 1 from public.activities where activity_id = target and coordinator_id = auth.uid())), false)
$$;
-- Provision only the lowest privilege role; client metadata never assigns staff access.
create function public.provision_profile() returns trigger language plpgsql security definer set search_path = '' as $$
begin
 insert into public.profiles(user_id,full_name) values(new.id,left(coalesce(nullif(trim(new.raw_user_meta_data->>'full_name'),''),'New member'),120));
 insert into public.senior_profiles(user_id) values(new.id);
 return new;
end $$;
create trigger provision_profile after insert on auth.users for each row execute function public.provision_profile();

alter table public.profiles enable row level security;
alter table public.senior_profiles enable row level security;
alter table public.categories enable row level security;
alter table public.activities enable row level security;
alter table public.enrollments enable row level security;
alter table public.attendance enable row level security;
alter table public.announcements enable row level security;
alter table public.feedback enable row level security;
alter table public.audit_logs enable row level security;
create policy own_profile on public.profiles for select to authenticated using(user_id = auth.uid() or public.current_app_role() = 'admin');
create policy own_senior_profile on public.senior_profiles for select to authenticated using(user_id = auth.uid() or public.current_app_role() = 'admin');
create policy category_read on public.categories for select to authenticated using(public.current_app_role() is not null and is_active);
create policy activity_read on public.activities for select to authenticated using((public.current_app_role() is not null and status not in ('draft','archived')) or public.manages_activity(activity_id));
create policy enrollment_read on public.enrollments for select to authenticated using((senior_id = auth.uid() and public.current_app_role() = 'senior') or public.manages_activity(activity_id));
create policy attendance_read on public.attendance for select to authenticated using(exists(select 1 from public.enrollments e where e.enrollment_id = attendance.enrollment_id));
create policy announcement_read on public.announcements for select to authenticated using(public.current_app_role() is not null and archived_at is null);
create policy feedback_read on public.feedback for select to authenticated using(exists(select 1 from public.enrollments e where e.enrollment_id = feedback.enrollment_id));
create policy audit_read on public.audit_logs for select to authenticated using(public.current_app_role() = 'admin');
-- No direct client writes: transactional functions own mutations.
revoke all on public.profiles,public.senior_profiles,public.categories,public.activities,public.enrollments,public.attendance,public.announcements,public.feedback,public.audit_logs from anon,authenticated;
grant select on public.profiles,public.senior_profiles,public.categories,public.activities,public.enrollments,public.attendance,public.announcements,public.feedback,public.audit_logs to authenticated;

create function public.enroll_in_activity(target uuid) returns public.enrollments language plpgsql security definer set search_path = '' as $$
declare a public.activities; result public.enrollments; seats integer;
begin
 if public.current_app_role() is distinct from 'senior' then raise exception 'An active senior account is required'; end if;
 select * into a from public.activities where activity_id = target for update;
 if not found then raise exception 'Activity not found'; end if;
 if a.status not in ('open','full') or a.cutoff_at <= now() then raise exception 'Registration is closed'; end if;
 if exists(select 1 from public.enrollments where activity_id = target and senior_id = auth.uid() and status <> 'cancelled') then raise exception 'You already joined this activity'; end if;
 select count(*) into seats from public.enrollments where activity_id = target and status = 'confirmed';
 insert into public.enrollments(activity_id,senior_id,status) values(target,auth.uid(),case when seats < a.capacity then 'confirmed' else 'waitlisted' end) returning * into result;
 return result;
end $$;
create function public.withdraw_enrollment(target uuid) returns void language plpgsql security definer set search_path = '' as $$
declare e public.enrollments; a public.activities;
begin
 if public.current_app_role() is distinct from 'senior' then raise exception 'An active senior account is required'; end if;
 select * into e from public.enrollments where enrollment_id = target and senior_id = auth.uid();
 if not found then raise exception 'Enrollment not found'; end if;
 select * into a from public.activities where activity_id = e.activity_id for update;
 select * into e from public.enrollments where enrollment_id = target for update;
 if a.cutoff_at <= now() or a.status not in ('open','full') then raise exception 'Withdrawal is closed'; end if;
 if e.status not in ('confirmed','pending','waitlisted') then raise exception 'Enrollment is not active'; end if;
 update public.enrollments set status = 'cancelled',cancelled_at = now() where enrollment_id = target;
 if e.status = 'confirmed' then
  update public.enrollments set status = 'confirmed' where enrollment_id = (
   select q.enrollment_id from public.enrollments q join public.profiles p on p.user_id = q.senior_id
   where q.activity_id = e.activity_id and q.status = 'waitlisted' and p.account_status = 'active' and p.role = 'senior'
   order by q.enrolled_at,q.enrollment_id limit 1
  );
 end if;
end $$;
create function public.record_attendance(target uuid, present boolean, note text default '') returns void language plpgsql security definer set search_path = '' as $$
declare e public.enrollments; a public.activities;
begin
 select * into e from public.enrollments where enrollment_id = target;
 if not found or not public.manages_activity(e.activity_id) then raise exception 'Not authorized'; end if;
 select * into a from public.activities where activity_id = e.activity_id for update;
 select * into e from public.enrollments where enrollment_id = target for update;
 if a.status not in ('ongoing','completed') or e.status not in ('confirmed','completed') then raise exception 'Attendance is not available for this enrollment'; end if;
 if present is null or length(note) > 1000 then raise exception 'Invalid attendance input'; end if;
 if public.current_app_role()='admin' and exists(select 1 from public.attendance where enrollment_id=target) and (note is null or length(trim(note))<3) then raise exception 'A correction reason is required'; end if;
 insert into public.attendance(enrollment_id,attended,remarks,recorded_by) values(target,present,note,auth.uid())
 on conflict(enrollment_id) do update set attended=excluded.attended,remarks=excluded.remarks,recorded_by=excluded.recorded_by,recorded_at=now();
 insert into public.audit_logs(actor_id,action_type,target_type,target_id,details) values(auth.uid(),'attendance.recorded','enrollment',target,jsonb_build_object('attended',present,'remarks',note));
end $$;
revoke execute on function public.provision_profile(),public.current_app_role(),public.manages_activity(uuid),public.enroll_in_activity(uuid),public.withdraw_enrollment(uuid),public.record_attendance(uuid,boolean,text) from public,anon;
grant execute on function public.current_app_role(),public.manages_activity(uuid),public.enroll_in_activity(uuid),public.withdraw_enrollment(uuid),public.record_attendance(uuid,boolean,text) to authenticated;
commit;

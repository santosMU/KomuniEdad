begin;
alter table public.profiles add column contact_number text;
create table public.settings (id boolean primary key default true check(id), require_verification boolean not null default false);
insert into public.settings values(true,false);
alter table public.settings enable row level security;
revoke all on public.settings from anon,authenticated;
grant select on public.settings to authenticated;
create policy settings_read on public.settings for select to authenticated using(public.current_app_role() is not null);

-- Return only names needed by assigned coordinators, not entire private profiles.
create function public.participant_list(target uuid) returns table(enrollment_id uuid,senior_id uuid,full_name text,status text,enrolled_at timestamptz,attended boolean,remarks text)
language plpgsql security definer set search_path='' as $$
begin
 if not public.manages_activity(target) then raise exception 'Not authorized'; end if;
 return query select e.enrollment_id,e.senior_id,p.full_name,e.status,e.enrolled_at,a.attended,a.remarks
 from public.enrollments e join public.profiles p on p.user_id=e.senior_id left join public.attendance a on a.enrollment_id=e.enrollment_id
 where e.activity_id=target order by e.enrolled_at,e.enrollment_id;
end $$;
create function public.activity_counts() returns table(activity_id uuid,confirmed bigint,waitlisted bigint)
language sql stable security definer set search_path='' as $$
 select a.activity_id,count(e.enrollment_id) filter(where e.status in ('confirmed','completed')),count(e.enrollment_id) filter(where e.status='waitlisted')
 from public.activities a left join public.enrollments e on e.activity_id=a.activity_id
 where public.current_app_role() is not null and (a.status not in ('draft','archived') or public.manages_activity(a.activity_id)) group by a.activity_id
$$;
create function public.coordinator_directory() returns table(user_id uuid,full_name text)
language sql stable security definer set search_path='' as $$
 select p.user_id,p.full_name from public.profiles p where public.current_app_role() is not null and p.role in ('coordinator','admin') and p.account_status='active'
$$;
create function public.save_activity(payload jsonb,target uuid default null) returns uuid
language plpgsql security definer set search_path='' as $$
declare result uuid; old_row public.activities; coordinator uuid; category uuid; seats int; new_status text;
begin
 if public.current_app_role() not in ('coordinator','admin') or public.current_app_role() is null then raise exception 'Not authorized'; end if;
 coordinator:=case when public.current_app_role()='admin' then (payload->>'coordinator_id')::uuid else auth.uid() end;
 category:=(payload->>'category_id')::uuid;
 if not exists(select 1 from public.profiles where user_id=coordinator and role in ('coordinator','admin') and account_status='active') then raise exception 'Invalid coordinator'; end if;
 if not exists(select 1 from public.categories where category_id=category and is_active) then raise exception 'Invalid category'; end if;
 if length(trim(payload->>'title')) not between 1 and 160 or length(trim(payload->>'description')) not between 1 and 5000 or length(trim(payload->>'venue')) not between 1 and 200 then raise exception 'Invalid activity'; end if;
 new_status:=payload->>'status';
 if target is not null then
  select * into old_row from public.activities where activities.activity_id=target for update;
  if not found or not public.manages_activity(target) then raise exception 'Not authorized'; end if;
  -- Finalized records are preserved. Archive only after closing the activity.
  if old_row.status in ('completed','cancelled','archived') and new_status not in (old_row.status,'archived') then raise exception 'A finalized activity cannot be reopened'; end if;
  if new_status='archived' and old_row.status not in ('completed','cancelled','archived') then raise exception 'Complete or cancel the activity before archiving'; end if;
  if new_status='draft' and old_row.status<>'draft' then raise exception 'Published activities cannot return to draft'; end if;
  if new_status in ('ongoing','completed') and (payload->>'start_at')::timestamptz>now() then raise exception 'The activity has not started'; end if;
  if new_status='completed' and (payload->>'end_at')::timestamptz>now() then raise exception 'The activity has not ended'; end if;
  select count(*) into seats from public.enrollments where enrollments.activity_id=target and status in ('confirmed','completed');
  if (payload->>'capacity')::int<seats then raise exception 'Capacity cannot be lower than allocated seats'; end if;
  update public.activities set category_id=category,coordinator_id=coordinator,title=trim(payload->>'title'),description=payload->>'description',venue=payload->>'venue',start_at=(payload->>'start_at')::timestamptz,end_at=(payload->>'end_at')::timestamptz,cutoff_at=(payload->>'cutoff_at')::timestamptz,capacity=(payload->>'capacity')::int,requirements=coalesce(payload->>'requirements',''),status=new_status where activities.activity_id=target returning activities.activity_id into result;
  if new_status='cancelled' then update public.enrollments set status='cancelled',cancelled_at=now() where enrollments.activity_id=target and status in ('pending','confirmed','waitlisted'); end if;
  if new_status='completed' then
   update public.enrollments set status='completed' where enrollments.activity_id=target and status='confirmed';
   update public.enrollments set status='cancelled',cancelled_at=now() where enrollments.activity_id=target and status in ('pending','waitlisted');
  end if;
  if old_row.start_at<>(payload->>'start_at')::timestamptz or old_row.venue<>(payload->>'venue') then
   insert into public.announcements(activity_id,posted_by,title,message) values(target,auth.uid(),'Activity schedule updated','The schedule or venue for '||(payload->>'title')||' has changed. Please review its activity details.');
  end if;
 else
  if new_status not in ('draft','open') then raise exception 'New activities must be draft or open'; end if;
  insert into public.activities(category_id,coordinator_id,title,description,venue,start_at,end_at,cutoff_at,capacity,requirements,status)
  values(category,coordinator,trim(payload->>'title'),payload->>'description',payload->>'venue',(payload->>'start_at')::timestamptz,(payload->>'end_at')::timestamptz,(payload->>'cutoff_at')::timestamptz,(payload->>'capacity')::int,coalesce(payload->>'requirements',''),new_status) returning activities.activity_id into result;
 end if;
 insert into public.audit_logs(actor_id,action_type,target_type,target_id,details) values(auth.uid(),'activity.saved','activity',result,jsonb_build_object('status',new_status));
 return result;
end $$;

create function public.update_own_profile(payload jsonb) returns void language plpgsql security definer set search_path='' as $$
begin
 if public.current_app_role() is null then raise exception 'Not authorized'; end if;
 if length(trim(payload->>'full_name')) not between 1 and 120 or length(coalesce(payload->>'contact_number',''))>30 or length(coalesce(payload->>'address',''))>500 then raise exception 'Invalid profile'; end if;
 if nullif(payload->>'birthdate','')::date>current_date then raise exception 'Birthdate cannot be in the future'; end if;
 update public.profiles set full_name=trim(payload->>'full_name'),contact_number=payload->>'contact_number' where user_id=auth.uid();
 if public.current_app_role()='senior' then update public.senior_profiles set birthdate=nullif(payload->>'birthdate','')::date,address=payload->>'address' where user_id=auth.uid(); end if;
end $$;
create function public.manage_user(target uuid,payload jsonb,reason text) returns void language plpgsql security definer set search_path='' as $$
begin
 if public.current_app_role() is distinct from 'admin' then raise exception 'Not authorized'; end if;
 if length(trim(reason)) not between 3 and 500 then raise exception 'A reason is required'; end if;
 if target=auth.uid() then raise exception 'Ask another administrator to change your account'; end if;
 perform 1 from public.profiles where user_id=target for update;
 if not found then raise exception 'User not found'; end if;
 if (payload->>'role'<>'coordinator' or payload->>'account_status'='disabled') and exists(select 1 from public.activities where coordinator_id=target and status not in ('completed','cancelled','archived')) then raise exception 'Reassign active activities before changing this coordinator'; end if;
 update public.profiles set role=payload->>'role',account_status=payload->>'account_status' where user_id=target;
 if payload->>'role'='senior' then
  insert into public.senior_profiles(user_id,verification_status) values(target,payload->>'verification_status') on conflict(user_id) do update set verification_status=excluded.verification_status;
 end if;
 insert into public.audit_logs(actor_id,action_type,target_type,target_id,details) values(auth.uid(),'user.updated','profile',target,jsonb_build_object('reason',reason,'role',payload->>'role','status',payload->>'account_status','verification',payload->>'verification_status'));
end $$;
create function public.save_category(payload jsonb,target uuid default null) returns uuid language plpgsql security definer set search_path='' as $$
declare result uuid;
begin
 if public.current_app_role() is distinct from 'admin' then raise exception 'Not authorized'; end if;
 if length(trim(payload->>'name')) not between 1 and 80 or length(coalesce(payload->>'description',''))>1000 then raise exception 'Invalid category'; end if;
 if target is null then insert into public.categories(name,description,is_active) values(trim(payload->>'name'),payload->>'description',(payload->>'is_active')::boolean) returning category_id into result;
 else update public.categories set name=trim(payload->>'name'),description=payload->>'description',is_active=(payload->>'is_active')::boolean where category_id=target returning category_id into result; end if;
 if result is null then raise exception 'Category not found'; end if;
 insert into public.audit_logs(actor_id,action_type,target_type,target_id) values(auth.uid(),'category.saved','category',result); return result;
end $$;
drop policy category_read on public.categories;
create policy category_read on public.categories for select to authenticated using(public.current_app_role() is not null and (is_active or public.current_app_role()='admin'));

create function public.save_announcement(payload jsonb,target uuid default null) returns uuid language plpgsql security definer set search_path='' as $$
declare result uuid; activity uuid; old_activity uuid;
begin
 activity:=nullif(payload->>'activity_id','')::uuid;
 if not (public.current_app_role()='admin' or (activity is not null and public.manages_activity(activity))) or public.current_app_role() is null then raise exception 'Not authorized'; end if;
 if length(trim(payload->>'title')) not between 1 and 160 or length(trim(payload->>'message')) not between 1 and 5000 then raise exception 'Invalid announcement'; end if;
 if target is null then insert into public.announcements(activity_id,posted_by,title,message) values(activity,auth.uid(),payload->>'title',payload->>'message') returning announcement_id into result;
 else
  select activity_id into old_activity from public.announcements where announcement_id=target for update;
  if not found or not (public.current_app_role()='admin' or (old_activity is not null and public.manages_activity(old_activity))) then raise exception 'Not authorized'; end if;
  update public.announcements set title=payload->>'title',message=payload->>'message',archived_at=case when (payload->>'archived')::boolean then now() else null end where announcement_id=target returning announcement_id into result;
 end if;
 insert into public.audit_logs(actor_id,action_type,target_type,target_id) values(auth.uid(),'announcement.saved','announcement',result); return result;
end $$;
drop policy announcement_read on public.announcements;
create policy announcement_read on public.announcements for select to authenticated using(public.current_app_role() is not null and ((archived_at is null and (activity_id is null or exists(select 1 from public.activities a where a.activity_id=announcements.activity_id))) or public.current_app_role()='admin' or public.manages_activity(activity_id)));

create function public.submit_feedback(target uuid,score integer,comment text default '') returns void language plpgsql security definer set search_path='' as $$
begin
 if public.current_app_role() is distinct from 'senior' then raise exception 'Not authorized'; end if;
 if length(comment)>2000 or score not between 1 and 5 then raise exception 'Invalid feedback'; end if;
 if not exists(select 1 from public.enrollments e join public.activities a on a.activity_id=e.activity_id join public.attendance t on t.enrollment_id=e.enrollment_id where e.enrollment_id=target and e.senior_id=auth.uid() and e.status='completed' and a.status in ('completed','archived') and t.attended) then raise exception 'Feedback requires a completed, attended enrollment'; end if;
 insert into public.feedback(enrollment_id,rating,comments) values(target,score,comment);
end $$;
-- Verification policy is optional and controlled only by trusted deployment configuration.
create function public.check_enrollment_eligibility() returns trigger language plpgsql security definer set search_path='' as $$
begin
 if new.status in ('confirmed','waitlisted','pending') then
  if not exists(select 1 from public.profiles where user_id=new.senior_id and role='senior' and account_status='active') then raise exception 'An active senior account is required'; end if;
  if (select require_verification from public.settings where id) and not exists(select 1 from public.senior_profiles where user_id=new.senior_id and verification_status='verified') then raise exception 'Account verification is required'; end if;
 end if;
 return new;
end $$;
create trigger check_enrollment_eligibility before insert or update on public.enrollments for each row execute function public.check_enrollment_eligibility();

revoke execute on function public.participant_list(uuid),public.activity_counts(),public.coordinator_directory(),public.save_activity(jsonb,uuid),public.update_own_profile(jsonb),public.manage_user(uuid,jsonb,text),public.save_category(jsonb,uuid),public.save_announcement(jsonb,uuid),public.submit_feedback(uuid,integer,text),public.check_enrollment_eligibility() from public,anon;
grant execute on function public.participant_list(uuid),public.activity_counts(),public.coordinator_directory(),public.save_activity(jsonb,uuid),public.update_own_profile(jsonb),public.manage_user(uuid,jsonb,text),public.save_category(jsonb,uuid),public.save_announcement(jsonb,uuid),public.submit_feedback(uuid,integer,text) to authenticated;
commit;

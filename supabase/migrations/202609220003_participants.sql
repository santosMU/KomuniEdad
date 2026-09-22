begin;
-- Caller must hold the activity row lock. Not exposed to API roles.
create function public.promote_waitlist(target uuid,excluded uuid default null) returns void language plpgsql security definer set search_path='' as $$
declare free_seats int; candidate uuid;
begin
 select a.capacity-(select count(*) from public.enrollments e where e.activity_id=target and e.status in ('confirmed','completed')) into free_seats from public.activities a where a.activity_id=target;
 while free_seats>0 loop
  select e.enrollment_id into candidate from public.enrollments e join public.profiles p on p.user_id=e.senior_id left join public.senior_profiles s on s.user_id=p.user_id
  where e.activity_id=target and e.status='waitlisted' and (excluded is null or e.enrollment_id<>excluded) and p.role='senior' and p.account_status='active'
  and (not (select require_verification from public.settings where id) or s.verification_status='verified') order by e.enrolled_at,e.enrollment_id limit 1;
  exit when candidate is null;
  update public.enrollments set status='confirmed' where enrollment_id=candidate;
  free_seats:=free_seats-1;
 end loop;
end $$;
create or replace function public.withdraw_enrollment(target uuid) returns void language plpgsql security definer set search_path='' as $$
declare e public.enrollments; a public.activities;
begin
 if public.current_app_role() is distinct from 'senior' then raise exception 'An active senior account is required'; end if;
 select * into e from public.enrollments where enrollment_id=target and senior_id=auth.uid();
 if not found then raise exception 'Enrollment not found'; end if;
 select * into a from public.activities where activity_id=e.activity_id for update;
 select * into e from public.enrollments where enrollment_id=target for update;
 if a.cutoff_at<=now() or a.status not in ('open','full') then raise exception 'Withdrawal is closed'; end if;
 if e.status not in ('confirmed','pending','waitlisted') then raise exception 'Enrollment is not active'; end if;
 update public.enrollments set status='cancelled',cancelled_at=now() where enrollment_id=target;
 if e.status='confirmed' then perform public.promote_waitlist(a.activity_id); end if;
end $$;
create function public.manage_enrollment(activity uuid,senior uuid,new_status text,reason text) returns uuid language plpgsql security definer set search_path='' as $$
declare a public.activities; e public.enrollments; result uuid; seats int;
begin
 if not public.manages_activity(activity) then raise exception 'Not authorized'; end if;
 if new_status not in ('confirmed','waitlisted','cancelled') or new_status is null or length(trim(reason)) not between 3 and 500 or reason is null then raise exception 'Invalid action or reason'; end if;
 select * into a from public.activities where activity_id=activity for update;
 if a.status not in ('open','full','ongoing') then raise exception 'Activity is closed'; end if;
 select * into e from public.enrollments where activity_id=activity and senior_id=senior and status<>'cancelled' for update;
 if new_status='cancelled' and e.enrollment_id is null then raise exception 'Active enrollment not found'; end if;
 if new_status='confirmed' then
  select count(*) into seats from public.enrollments where activity_id=activity and status in ('confirmed','completed') and (e.enrollment_id is null or enrollment_id<>e.enrollment_id);
  if seats>=a.capacity then raise exception 'Activity is full'; end if;
 end if;
 if e.enrollment_id is null then
  insert into public.enrollments(activity_id,senior_id,status) values(activity,senior,new_status) returning enrollment_id into result;
 else
  update public.enrollments set status=new_status,cancelled_at=case when new_status='cancelled' then now() else null end where enrollment_id=e.enrollment_id returning enrollment_id into result;
 end if;
 if e.status='confirmed' and new_status<>'confirmed' then perform public.promote_waitlist(activity,result); end if;
 insert into public.audit_logs(actor_id,action_type,target_type,target_id,details) values(auth.uid(),'enrollment.managed','enrollment',result,jsonb_build_object('status',new_status,'reason',reason));
 return result;
end $$;
-- Seniors retain the details of archived activities they participated in.
drop policy activity_read on public.activities;
create policy activity_read on public.activities for select to authenticated using((public.current_app_role() is not null and status not in ('draft','archived')) or public.manages_activity(activity_id) or (public.current_app_role()='senior' and exists(select 1 from public.enrollments e where e.activity_id=activities.activity_id and e.senior_id=auth.uid())));
revoke execute on function public.promote_waitlist(uuid,uuid),public.manage_enrollment(uuid,uuid,text,text) from public,anon,authenticated;
grant execute on function public.manage_enrollment(uuid,uuid,text,text) to authenticated;
commit;

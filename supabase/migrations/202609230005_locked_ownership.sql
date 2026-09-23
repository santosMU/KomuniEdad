begin;
create or replace function public.record_attendance(target uuid, present boolean, note text default '') returns void language plpgsql security definer set search_path = '' as $$
declare e public.enrollments; a public.activities;
begin
 select * into e from public.enrollments where enrollment_id = target;
 if not found or not public.manages_activity(e.activity_id) then raise exception 'Not authorized'; end if;
 select * into a from public.activities where activity_id = e.activity_id for update;
 if not public.manages_activity(a.activity_id) then raise exception 'Not authorized'; end if;
 select * into e from public.enrollments where enrollment_id = target for update;
 if a.status not in ('ongoing','completed') or e.status not in ('confirmed','completed') then raise exception 'Attendance is not available for this enrollment'; end if;
 if present is null or length(note) > 1000 then raise exception 'Invalid attendance input'; end if;
 if public.current_app_role()='admin' and exists(select 1 from public.attendance where enrollment_id=target) and (note is null or length(trim(note))<3) then raise exception 'A correction reason is required'; end if;
 insert into public.attendance(enrollment_id,attended,remarks,recorded_by) values(target,present,note,auth.uid())
 on conflict(enrollment_id) do update set attended=excluded.attended,remarks=excluded.remarks,recorded_by=excluded.recorded_by,recorded_at=now();
 insert into public.audit_logs(actor_id,action_type,target_type,target_id,details) values(auth.uid(),'attendance.recorded','enrollment',target,jsonb_build_object('attended',present,'remarks',note));
end $$;
create or replace function public.manage_enrollment(activity uuid,senior uuid,new_status text,reason text) returns uuid language plpgsql security definer set search_path='' as $$
declare a public.activities; e public.enrollments; result uuid; seats int;
begin
 if not public.manages_activity(activity) then raise exception 'Not authorized'; end if;
 if new_status not in ('confirmed','waitlisted','cancelled') or new_status is null or length(trim(reason)) not between 3 and 500 or reason is null then raise exception 'Invalid action or reason'; end if;
 select * into a from public.activities where activity_id=activity for update;
 if not public.manages_activity(activity) then raise exception 'Not authorized'; end if;
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
revoke execute on function public.record_attendance(uuid,boolean,text), public.manage_enrollment(uuid,uuid,text,text) from public,anon;
grant execute on function public.record_attendance(uuid,boolean,text), public.manage_enrollment(uuid,uuid,text,text) to authenticated;
commit;

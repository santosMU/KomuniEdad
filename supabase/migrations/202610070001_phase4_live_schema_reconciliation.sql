begin;

-- Reconcile live payment and RPC behavior with the Phase 4 application.

alter table public.enrollments drop constraint if exists enrollments_payment_status_check;
alter table public.enrollments drop constraint if exists enrollment_payment_status_valid;

update public.enrollments e
set payment_status = case
    when a.is_free then 'not_required'
    when e.payment_status = 'paid' then 'paid'
    else 'unpaid'
end
from public.activities a
where a.activity_id = e.activity_id
  and e.payment_status is distinct from case
      when a.is_free then 'not_required'
      when e.payment_status = 'paid' then 'paid'
      else 'unpaid'
  end;

alter table public.enrollments alter column payment_status set default 'not_required';
alter table public.enrollments
  add constraint enrollment_payment_status_valid
  check (payment_status in ('not_required','unpaid','paid'));

do $$
begin
  if not exists (
    select 1 from pg_constraint
    where conrelid = 'public.activities'::regclass
      and conname = 'activity_payment_valid'
  ) then
    alter table public.activities
      add constraint activity_payment_valid
      check ((is_free and fee = 0) or (not is_free and fee > 0));
  end if;
end $$;

drop function if exists public.participant_list(uuid);
create function public.participant_list(target uuid)
returns table(
  enrollment_id uuid,
  senior_id uuid,
  full_name text,
  status text,
  payment_status text,
  enrolled_at timestamptz,
  attended boolean,
  remarks text
)
language plpgsql security definer set search_path='' as $$
begin
  if not public.manages_activity(target) then raise exception 'Not authorized'; end if;

  return query
  select e.enrollment_id,e.senior_id,p.full_name,e.status,e.payment_status,e.enrolled_at,a.attended,a.remarks
  from public.enrollments e
  join public.profiles p on p.user_id=e.senior_id
  left join public.attendance a on a.enrollment_id=e.enrollment_id
  where e.activity_id=target
  order by e.enrolled_at,e.enrollment_id;
end $$;

create or replace function public.save_activity(payload jsonb,target uuid default null)
returns uuid
language plpgsql security definer set search_path='' as $$
declare
  result uuid;
  old_row public.activities;
  coordinator uuid;
  category uuid;
  seats int;
  new_status text;
  new_is_free boolean;
  new_fee numeric(10,2);
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

    new_is_free:=coalesce((payload->>'is_free')::boolean,old_row.is_free);
    new_fee:=case when new_is_free then 0 else coalesce((payload->>'fee')::numeric,old_row.fee) end;
    if not new_is_free and new_fee<=0 then raise exception 'Invalid cash fee'; end if;

    if old_row.status in ('completed','cancelled','archived') and new_status not in (old_row.status,'archived') then raise exception 'A finalized activity cannot be reopened'; end if;
    if new_status='archived' and old_row.status not in ('completed','cancelled','archived') then raise exception 'Complete or cancel the activity before archiving'; end if;
    if new_status='draft' and old_row.status<>'draft' then raise exception 'Published activities cannot return to draft'; end if;
    if new_status in ('ongoing','completed') and (payload->>'start_at')::timestamptz>now() then raise exception 'The activity has not started'; end if;
    if new_status='completed' and (payload->>'end_at')::timestamptz>now() then raise exception 'The activity has not ended'; end if;

    select count(*) into seats from public.enrollments where enrollments.activity_id=target and status in ('confirmed','completed');
    if (payload->>'capacity')::int<seats then raise exception 'Capacity cannot be lower than allocated seats'; end if;

    update public.activities
    set category_id=category,
        coordinator_id=coordinator,
        title=trim(payload->>'title'),
        description=payload->>'description',
        venue=payload->>'venue',
        start_at=(payload->>'start_at')::timestamptz,
        end_at=(payload->>'end_at')::timestamptz,
        cutoff_at=(payload->>'cutoff_at')::timestamptz,
        capacity=(payload->>'capacity')::int,
        requirements=coalesce(payload->>'requirements',''),
        status=new_status,
        is_free=new_is_free,
        fee=new_fee
    where activities.activity_id=target
    returning activities.activity_id into result;

    if new_is_free then
      update public.enrollments set payment_status='not_required'
      where activity_id=target and status<>'cancelled';
    elsif old_row.is_free and not new_is_free then
      update public.enrollments set payment_status='unpaid'
      where activity_id=target and status<>'cancelled';
    end if;

    if new_status='cancelled' then
      update public.enrollments set status='cancelled',cancelled_at=now()
      where enrollments.activity_id=target and status in ('pending','confirmed','waitlisted');
    end if;

    if new_status='completed' then
      update public.enrollments set status='completed'
      where enrollments.activity_id=target and status='confirmed';
      update public.enrollments set status='cancelled',cancelled_at=now()
      where enrollments.activity_id=target and status in ('pending','waitlisted');
    end if;

    if old_row.start_at<>(payload->>'start_at')::timestamptz or old_row.venue<>(payload->>'venue') then
      insert into public.announcements(activity_id,posted_by,title,message)
      values(target,auth.uid(),'Activity schedule updated','The schedule or venue for '||(payload->>'title')||' has changed. Please review its activity details.');
    end if;
  else
    new_is_free:=coalesce((payload->>'is_free')::boolean,true);
    new_fee:=case when new_is_free then 0 else coalesce((payload->>'fee')::numeric,0) end;
    if not new_is_free and new_fee<=0 then raise exception 'Invalid cash fee'; end if;
    if new_status not in ('draft','open') then raise exception 'New activities must be draft or open'; end if;

    insert into public.activities(
      category_id,coordinator_id,title,description,venue,start_at,end_at,cutoff_at,
      capacity,requirements,status,is_free,fee
    )
    values(
      category,coordinator,trim(payload->>'title'),payload->>'description',payload->>'venue',
      (payload->>'start_at')::timestamptz,(payload->>'end_at')::timestamptz,(payload->>'cutoff_at')::timestamptz,
      (payload->>'capacity')::int,coalesce(payload->>'requirements',''),new_status,new_is_free,new_fee
    )
    returning activities.activity_id into result;
  end if;

  insert into public.audit_logs(actor_id,action_type,target_type,target_id,details)
  values(auth.uid(),'activity.saved','activity',result,jsonb_build_object('status',new_status,'is_free',new_is_free,'fee',new_fee));

  return result;
end $$;

drop function if exists public.enroll_in_activity(uuid);
create function public.enroll_in_activity(target uuid)
returns public.enrollments
language plpgsql security definer set search_path='' as $$
declare a public.activities; result public.enrollments; seats integer;
begin
  if public.current_app_role() is distinct from 'senior' then raise exception 'An active senior account is required'; end if;

  select * into a from public.activities where activity_id=target for update;
  if not found then raise exception 'Activity not found'; end if;
  if a.status not in ('open','full') or a.cutoff_at<=now() then raise exception 'Registration is closed'; end if;
  if exists(select 1 from public.enrollments where activity_id=target and senior_id=auth.uid() and status<>'cancelled') then raise exception 'You already joined this activity'; end if;

  select count(*) into seats from public.enrollments where activity_id=target and status='confirmed';

  insert into public.enrollments(activity_id,senior_id,status,payment_status)
  values(
    target,
    auth.uid(),
    case when seats<a.capacity then 'confirmed' else 'waitlisted' end,
    case when a.is_free then 'not_required' else 'unpaid' end
  )
  returning * into result;

  return result;
end $$;

create or replace function public.withdraw_enrollment(target uuid)
returns void
language plpgsql security definer set search_path='' as $$
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

create or replace function public.manage_enrollment(activity uuid,senior uuid,new_status text,reason text)
returns uuid
language plpgsql security definer set search_path='' as $$
declare a public.activities; e public.enrollments; result uuid; seats int;
begin
  if not public.manages_activity(activity) then raise exception 'Not authorized'; end if;
  if new_status not in ('confirmed','waitlisted','cancelled') or new_status is null or length(trim(reason)) not between 3 and 500 or reason is null then raise exception 'Invalid action or reason'; end if;

  select * into a from public.activities where activity_id=activity for update;
  if not public.manages_activity(activity) then raise exception 'Not authorized'; end if;
  if a.status not in ('open','full','ongoing') then raise exception 'Activity is closed'; end if;

  select * into e from public.enrollments
  where activity_id=activity and senior_id=senior and status<>'cancelled'
  for update;

  if new_status='cancelled' and e.enrollment_id is null then raise exception 'Active enrollment not found'; end if;

  if new_status='confirmed' then
    select count(*) into seats from public.enrollments
    where activity_id=activity and status in ('confirmed','completed')
      and (e.enrollment_id is null or enrollment_id<>e.enrollment_id);
    if seats>=a.capacity then raise exception 'Activity is full'; end if;
  end if;

  if e.enrollment_id is null then
    insert into public.enrollments(activity_id,senior_id,status,payment_status)
    values(activity,senior,new_status,case when a.is_free then 'not_required' else 'unpaid' end)
    returning enrollment_id into result;
  else
    update public.enrollments
    set status=new_status,
        cancelled_at=case when new_status='cancelled' then now() else null end
    where enrollment_id=e.enrollment_id
    returning enrollment_id into result;
  end if;

  if e.status='confirmed' and new_status<>'confirmed' then
    perform public.promote_waitlist(activity,result);
  end if;

  insert into public.audit_logs(actor_id,action_type,target_type,target_id,details)
  values(auth.uid(),'enrollment.managed','enrollment',result,jsonb_build_object('status',new_status,'reason',reason));

  return result;
end $$;

create or replace function public.record_attendance(target uuid,present boolean,note text default '')
returns void
language plpgsql security definer set search_path='' as $$
declare e public.enrollments; a public.activities;
begin
  select * into e from public.enrollments where enrollment_id=target;
  if not found or not public.manages_activity(e.activity_id) then raise exception 'Not authorized'; end if;

  select * into a from public.activities where activity_id=e.activity_id for update;
  if not public.manages_activity(a.activity_id) then raise exception 'Not authorized'; end if;

  select * into e from public.enrollments where enrollment_id=target for update;

  if a.status not in ('ongoing','completed') or e.status not in ('confirmed','completed') then raise exception 'Attendance is not available for this enrollment'; end if;
  if not a.is_free and e.payment_status<>'paid' then raise exception 'Cash payment must be recorded before attendance'; end if;
  if present is null or length(note)>1000 then raise exception 'Invalid attendance input'; end if;
  if public.current_app_role()='admin' and exists(select 1 from public.attendance where enrollment_id=target) and (note is null or length(trim(note))<3) then raise exception 'A correction reason is required'; end if;

  insert into public.attendance(enrollment_id,attended,remarks,recorded_by)
  values(target,present,note,auth.uid())
  on conflict(enrollment_id) do update
  set attended=excluded.attended,
      remarks=excluded.remarks,
      recorded_by=excluded.recorded_by,
      recorded_at=now();

  insert into public.audit_logs(actor_id,action_type,target_type,target_id,details)
  values(auth.uid(),'attendance.recorded','enrollment',target,jsonb_build_object('attended',present,'remarks',note));
end $$;

create or replace function public.record_cash_payment(target uuid,paid boolean,reason text)
returns void
language plpgsql security definer set search_path='' as $$
declare e public.enrollments; a public.activities; new_status text;
begin
  select * into e from public.enrollments where enrollment_id=target;
  if not found or not public.manages_activity(e.activity_id) then raise exception 'Not authorized'; end if;

  select * into a from public.activities where activity_id=e.activity_id for update;
  if not public.manages_activity(a.activity_id) then raise exception 'Not authorized'; end if;

  select * into e from public.enrollments where enrollment_id=target for update;

  if a.is_free then raise exception 'No cash payment is required for this activity'; end if;
  if e.status not in ('confirmed','completed') then raise exception 'Cash payment can only be recorded for a confirmed participant'; end if;
  if paid is null or reason is null or length(trim(reason)) not between 3 and 500 then raise exception 'Invalid cash payment record'; end if;

  new_status:=case when paid then 'paid' else 'unpaid' end;

  update public.enrollments
  set payment_status=new_status
  where enrollment_id=target;

  insert into public.audit_logs(actor_id,action_type,target_type,target_id,details)
  values(
    auth.uid(),
    'payment.cash_recorded',
    'enrollment',
    target,
    jsonb_build_object('method','cash','payment_status',new_status,'amount',a.fee,'reason',reason)
  );
end $$;

revoke execute on function public.activity_directory() from public,anon;
revoke execute on function public.participant_list(uuid) from public,anon;
revoke execute on function public.save_activity(jsonb,uuid) from public,anon;
revoke execute on function public.enroll_in_activity(uuid) from public,anon;
revoke execute on function public.withdraw_enrollment(uuid) from public,anon;
revoke execute on function public.manage_enrollment(uuid,uuid,text,text) from public,anon;
revoke execute on function public.record_attendance(uuid,boolean,text) from public,anon;
revoke execute on function public.record_cash_payment(uuid,boolean,text) from public,anon;

grant execute on function public.activity_directory() to authenticated;
grant execute on function public.participant_list(uuid) to authenticated;
grant execute on function public.save_activity(jsonb,uuid) to authenticated;
grant execute on function public.enroll_in_activity(uuid) to authenticated;
grant execute on function public.withdraw_enrollment(uuid) to authenticated;
grant execute on function public.manage_enrollment(uuid,uuid,text,text) to authenticated;
grant execute on function public.record_attendance(uuid,boolean,text) to authenticated;
grant execute on function public.record_cash_payment(uuid,boolean,text) to authenticated;

revoke execute on function public.check_enrollment_eligibility() from public,anon,authenticated;
revoke execute on function public.promote_waitlist(uuid,uuid) from public,anon,authenticated;
revoke execute on function public.promote_after_capacity_increase() from public,anon,authenticated;
revoke execute on function public.provision_profile() from public,anon,authenticated;

alter function public.health_check() set search_path='';

commit;

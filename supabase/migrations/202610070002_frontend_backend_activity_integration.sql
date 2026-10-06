begin;

-- Production catch-up for the merged frontend while preserving the optimized
-- single-RPC activity backend.

alter table public.activities
  add column if not exists image text,
  add column if not exists image_url text;

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
        fee=new_fee,
        image=payload->>'image',
        image_url=payload->>'image_url'
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
      capacity,requirements,status,is_free,fee,image,image_url
    )
    values(
      category,coordinator,trim(payload->>'title'),payload->>'description',payload->>'venue',
      (payload->>'start_at')::timestamptz,(payload->>'end_at')::timestamptz,(payload->>'cutoff_at')::timestamptz,
      (payload->>'capacity')::int,coalesce(payload->>'requirements',''),new_status,new_is_free,new_fee,
      payload->>'image',payload->>'image_url'
    )
    returning activities.activity_id into result;
  end if;

  insert into public.audit_logs(actor_id,action_type,target_type,target_id,details)
  values(auth.uid(),'activity.saved','activity',result,jsonb_build_object('status',new_status,'is_free',new_is_free,'fee',new_fee));

  return result;
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
    a.status, a.is_free, a.fee, a.image, a.image_url, c.name, p.full_name
  order by a.start_at asc;
$$;

revoke execute on function public.activity_directory() from public,anon;
grant execute on function public.activity_directory() to authenticated;

commit;

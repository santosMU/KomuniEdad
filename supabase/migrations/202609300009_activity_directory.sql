-- Phase 4 performance: collapse the activity list waterfall into one authorized RPC.

create or replace function public.activity_directory()
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
    jsonb_build_object('name', c.name) as categories,
    coalesce(p.full_name, 'Community coordinator') as coordinator_name,
    count(e.enrollment_id) filter (where e.status in ('confirmed','completed')) as confirmed,
    count(e.enrollment_id) filter (where e.status = 'waitlisted') as waitlisted
  from public.activities a
  left join public.categories c on c.category_id = a.category_id
  left join public.profiles p on p.user_id = a.coordinator_id
  left join public.enrollments e on e.activity_id = a.activity_id
  where public.current_app_role() is not null
    and (a.status not in ('draft','archived') or public.manages_activity(a.activity_id))
  group by
    a.activity_id, a.category_id, a.coordinator_id, a.title, a.description,
    a.venue, a.start_at, a.end_at, a.cutoff_at, a.capacity, a.requirements,
    a.status, a.is_free, a.fee, c.name, p.full_name
  order by a.start_at asc;
$$;

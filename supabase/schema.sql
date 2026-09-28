-- Optional health baseline. Apply migrations in filename order after this file.
create extension if not exists "pgcrypto";
create or replace function public.health_check() returns jsonb language sql stable as $$
 select jsonb_build_object('status','ok','checked_at',now());
$$;
grant execute on function public.health_check() to anon,authenticated;

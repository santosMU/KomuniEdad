begin;

create table if not exists public.activity_comments (
  comment_id uuid primary key default gen_random_uuid(),
  activity_id uuid not null references public.activities(activity_id) on delete cascade,
  author_id uuid not null default auth.uid() references public.profiles(user_id) on delete restrict,
  author_name text not null default '',
  author_role text not null default 'member',
  message text not null,
  created_at timestamptz not null default now(),
  constraint activity_comments_message_valid check (length(trim(message)) between 1 and 1000)
);

create index if not exists activity_comments_activity_created_idx
  on public.activity_comments(activity_id, created_at);

create or replace function public.prepare_activity_comment()
returns trigger
language plpgsql
security definer
set search_path = ''
as $$
declare
  profile_row public.profiles;
begin
  if auth.uid() is null then
    raise exception 'Not authorized';
  end if;

  new.author_id := auth.uid();
  new.message := trim(new.message);

  select * into profile_row
  from public.profiles
  where user_id = auth.uid()
    and account_status = 'active';

  if not found then
    raise exception 'Not authorized';
  end if;

  new.author_name := profile_row.full_name;
  new.author_role := profile_row.role;
  return new;
end
$$;

drop trigger if exists activity_comments_prepare on public.activity_comments;
create trigger activity_comments_prepare
before insert on public.activity_comments
for each row execute function public.prepare_activity_comment();

alter table public.activity_comments enable row level security;

drop policy if exists activity_comments_read on public.activity_comments;
create policy activity_comments_read
on public.activity_comments
for select
to authenticated
using (
  exists (
    select 1
    from public.activities a
    where a.activity_id = activity_comments.activity_id
  )
);

drop policy if exists activity_comments_insert on public.activity_comments;
create policy activity_comments_insert
on public.activity_comments
for insert
to authenticated
with check (
  author_id = auth.uid()
  and exists (
    select 1
    from public.activities a
    where a.activity_id = activity_comments.activity_id
  )
);

revoke all on table public.activity_comments from anon;
grant select, insert on table public.activity_comments to authenticated;

revoke execute on function public.prepare_activity_comment() from public, anon, authenticated;

commit;

begin;

create or replace function public.delete_announcement(target uuid)
returns void
language plpgsql
security definer
set search_path='' as $$
declare
  notice public.announcements;
begin
  select * into notice
  from public.announcements
  where announcement_id=target
  for update;

  if not found then
    raise exception 'Announcement not found';
  end if;

  if not (
    public.current_app_role()='admin'
    or (
      notice.activity_id is not null
      and public.manages_activity(notice.activity_id)
    )
  ) then
    raise exception 'Not authorized';
  end if;

  delete from public.announcements
  where announcement_id=target;

  insert into public.audit_logs(actor_id,action_type,target_type,target_id,details)
  values(
    auth.uid(),
    'announcement.deleted',
    'announcement',
    target,
    jsonb_build_object(
      'title', notice.title,
      'activity_id', notice.activity_id
    )
  );
end $$;

revoke execute on function public.delete_announcement(uuid) from public,anon;
grant execute on function public.delete_announcement(uuid) to authenticated;

commit;

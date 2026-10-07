begin;

create or replace function public.update_system_settings(new_require_verification boolean, reason text)
returns void
language plpgsql
security definer
set search_path='' as $$
declare
  old_value boolean;
begin
  if public.current_app_role() is distinct from 'admin' then
    raise exception 'Not authorized';
  end if;

  if new_require_verification is null then
    raise exception 'Verification policy is required';
  end if;

  if reason is null or length(trim(reason)) not between 3 and 500 then
    raise exception 'A reason is required';
  end if;

  select require_verification
  into old_value
  from public.settings
  where id
  for update;

  if not found then
    raise exception 'System settings are unavailable';
  end if;

  if old_value is distinct from new_require_verification then
    update public.settings
    set require_verification = new_require_verification
    where id;

    insert into public.audit_logs(actor_id,action_type,target_type,target_id,details)
    values(
      auth.uid(),
      'settings.updated',
      'settings',
      null,
      jsonb_build_object(
        'setting','require_verification',
        'previous',old_value,
        'current',new_require_verification,
        'reason',trim(reason)
      )
    );
  end if;
end $$;

revoke execute on function public.update_system_settings(boolean,text) from public,anon;
grant execute on function public.update_system_settings(boolean,text) to authenticated;

commit;

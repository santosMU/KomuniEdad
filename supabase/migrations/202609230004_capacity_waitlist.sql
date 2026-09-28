begin;
-- Existing eligible waitlisted seniors receive newly available seats first.
-- UPDATE already holds the activity row lock used by enrollment/withdrawal.
create function public.promote_after_capacity_increase() returns trigger
language plpgsql security definer set search_path='' as $$
begin
 if new.capacity>old.capacity and new.status in ('open','full') and new.cutoff_at>now() then
  perform public.promote_waitlist(new.activity_id);
 end if;
 return new;
end $$;
revoke execute on function public.promote_after_capacity_increase() from public,anon,authenticated;
create trigger activity_capacity_waitlist after update of capacity on public.activities
for each row execute function public.promote_after_capacity_increase();
commit;

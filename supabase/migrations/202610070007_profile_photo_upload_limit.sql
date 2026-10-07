begin;

do $$
begin
  if to_regclass('storage.buckets') is not null then
    update storage.buckets
    set file_size_limit = 10485760,
        allowed_mime_types = array['image/jpeg','image/png','image/webp']
    where id = 'profile-photos';
  end if;
end $$;

commit;

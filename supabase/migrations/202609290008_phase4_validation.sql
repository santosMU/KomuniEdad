begin;

-- Enforce the same bounds for direct authenticated RPC callers as Laravel.
-- Existing out-of-range data must be reviewed before applying this migration.
alter table public.activities
 add constraint activity_capacity_upper_bound check (capacity <= 10000),
 add constraint activity_requirements_length check (length(requirements) <= 2000),
 add constraint activity_fee_upper_bound check (fee <= 100000);

commit;

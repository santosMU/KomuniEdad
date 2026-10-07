begin;

update public.activities set tags='["stories","memories","social"]'::jsonb
where activity_id='d4000000-0000-4000-8000-000000000006';

update public.activities set tags='["wellness","healthy habits","learning"]'::jsonb
where activity_id='d4000000-0000-4000-8000-000000000005';

update public.activities set tags='["walking","outdoors","social"]'::jsonb
where activity_id='d4000000-0000-4000-8000-000000000008';

update public.activities set tags='["crafts","memories","creative"]'::jsonb
where activity_id='8cb9d6bb-6401-4b28-89ec-faf5e9aca836';

update public.activities set tags='["phones","beginner","learning"]'::jsonb
where activity_id='d4000000-0000-4000-8000-000000000004';

update public.activities set tags='["dance","beginner","paid"]'::jsonb
where activity_id='c1be4a8e-c4fd-4574-a3db-d16619a4188a';

update public.activities set tags='["gardening","hands-on","paid"]'::jsonb
where activity_id='d4000000-0000-4000-8000-000000000002';

update public.activities set tags='["coffee","social","games"]'::jsonb
where activity_id='d4000000-0000-4000-8000-000000000003';

update public.activities set tags='["gentle","morning","exercise"]'::jsonb
where activity_id='d4000000-0000-4000-8000-000000000001';

update public.activities set tags='["phones","one-to-one","beginner"]'::jsonb
where activity_id='d4000000-0000-4000-8000-000000000007';

update public.activities set tags='["dance","social","music"]'::jsonb
where activity_id='b7faf2d4-f825-4684-9f66-24e6e2e8bc04';

commit;

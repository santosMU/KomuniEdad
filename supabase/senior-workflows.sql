-- KomuniEdad senior workflow demonstrations.
-- Prerequisites: existing repository migrations, one active administrator,
-- and two dedicated Auth test users: senior1@example.com / senior2@example.com.
-- Create those users in Authentication > Users > Add user; confirm them for login.
-- This script finds their UUIDs automatically. It does not create passwords or Auth users.
-- Run the whole file in Supabase SQL Editor. Safe to repeat; existing rows are preserved.
-- If multiple staff exist, set coordinator_uuid below to the intended staff UUID.
-- Fictional attendance and feedback are ONLY for these two test accounts.
begin;
do $seed$
declare
 coordinator_uuid uuid := null; -- e.g. 'paste-your-staff-account-uuid-here'
 senior_one uuid := null;       -- optional test senior UUID
 senior_two uuid := null;       -- optional second test senior UUID
 staff_count integer;
 day_zero timestamptz := date_trunc('day', now() at time zone 'Asia/Manila') at time zone 'Asia/Manila';
 wellness uuid; learning uuid; social uuid; health uuid; community uuid;
begin

 -- These must be dedicated fictional demonstration accounts created in Auth.
 select id into senior_one from auth.users where lower(email)='senior1@example.com';
 select id into senior_two from auth.users where lower(email)='senior2@example.com';
 if senior_one is null or senior_two is null then
  raise exception 'Create senior1@example.com and senior2@example.com in Authentication > Users first, then rerun. No sample data was added.';
 end if;
 select count(*) into staff_count from public.profiles where role in ('coordinator','admin') and account_status='active';
 if coordinator_uuid is null and staff_count=1 then
  select user_id into coordinator_uuid from public.profiles where role in ('coordinator','admin') and account_status='active';
 end if;
 if coordinator_uuid is null then
  raise exception 'Set coordinator_uuid to your active coordinator/admin account UUID. Create an Auth account and provision its staff role first if none exists. No data was added.';
 end if;
 if not exists(select 1 from public.profiles where user_id=coordinator_uuid and role in ('coordinator','admin') and account_status='active') then
  raise exception 'The selected coordinator_uuid is not an active coordinator/admin profile. No data was added.';
 end if;
 if senior_one=senior_two then raise exception 'The two senior test account IDs must be different.'; end if;
 if senior_one is not null and not exists(select 1 from public.profiles where user_id=senior_one and role='senior' and account_status='active') then raise exception 'senior_one must be an active senior test account.'; end if;
 if senior_two is not null and not exists(select 1 from public.profiles where user_id=senior_two and role='senior' and account_status='active') then raise exception 'senior_two must be an active senior test account.'; end if;
 if exists(select 1 from public.settings where require_verification) and exists(
  select 1 from public.profiles p left join public.senior_profiles s on s.user_id=p.user_id
  where p.user_id in (senior_one,senior_two) and s.verification_status is distinct from 'verified'
 ) then raise exception 'Verification is enabled: use verified senior test accounts. This seed does not change verification policy.'; end if;

 insert into public.categories(name,description) values
 ('Health','Community health education; no clinical or medical records'),
 ('Wellness','Gentle movement and everyday well-being'),
 ('Social','Conversation, recreation, and shared interests'),
 ('Learning','Practical skills and lifelong learning'),
 ('Community','Local participation and volunteering') on conflict(name) do nothing;
 select category_id into wellness from public.categories where name='Wellness';
 select category_id into learning from public.categories where name='Learning';
 select category_id into social from public.categories where name='Social';
 select category_id into health from public.categories where name='Health';
 select category_id into community from public.categories where name='Community';

 insert into public.activities(activity_id,category_id,coordinator_id,title,description,venue,start_at,end_at,cutoff_at,capacity,requirements,status) values
 ('d4000000-0000-4000-8000-000000000001',wellness,coordinator_uuid,'Morning movement and gentle stretching','SAMPLE PROGRAM: Guided, low-impact movement and an opportunity to connect with neighbors. Participants can take breaks at their own pace.','Sample community garden',day_zero+interval '3 days 8 hours',day_zero+interval '3 days 9 hours',day_zero+interval '2 days 17 hours',24,'Comfortable clothing, water, and a small towel.','open'),
 ('d4000000-0000-4000-8000-000000000002',learning,coordinator_uuid,'Grow together: urban gardening','SAMPLE PROGRAM: Learn to grow herbs in small spaces. Share gardening tips and practice planting in a container.','Sample barangay learning center',day_zero+interval '4 days 9 hours',day_zero+interval '4 days 11 hours',day_zero+interval '3 days 17 hours',15,'Bring a reusable container if available. Sample materials are provided.','open'),
 ('d4000000-0000-4000-8000-000000000003',social,coordinator_uuid,'Kwentuhan and coffee afternoon','SAMPLE PROGRAM: A relaxed afternoon of conversation and board games with fellow community members.','Sample senior citizens hall',day_zero+interval '5 days 14 hours',day_zero+interval '5 days 16 hours',day_zero+interval '4 days 17 hours',30,'No special equipment required.','open'),
 ('d4000000-0000-4000-8000-000000000004',learning,coordinator_uuid,'Digital basics: stay connected','SAMPLE PROGRAM: Practice video calls, sending messages, and recognizing suspicious links using example scenarios.','Sample computer learning room',day_zero+interval '6 days 9 hours',day_zero+interval '6 days 11 hours',day_zero+interval '5 days 17 hours',12,'Bring your phone if you have one. Do not share passwords during the session.','open'),
 ('d4000000-0000-4000-8000-000000000005',health,coordinator_uuid,'Healthy everyday habits','SAMPLE PROGRAM: A general community education discussion about everyday routines. No examinations or medical information will be collected.','Sample multipurpose room',day_zero+interval '7 days 10 hours',day_zero+interval '7 days 11 hours',day_zero+interval '6 days 17 hours',25,'Notebook optional.','draft'),
 ('d4000000-0000-4000-8000-000000000006',community,coordinator_uuid,'Community storytelling circle','SAMPLE COMPLETED PROGRAM: A fictional past activity for testing attendance, history, and feedback screens.','Sample senior citizens hall',day_zero-interval '7 days'+interval '14 hours',day_zero-interval '7 days'+interval '16 hours',day_zero-interval '8 days'+interval '17 hours',20,'Sample historical record.','completed'),
 ('d4000000-0000-4000-8000-000000000007',learning,coordinator_uuid,'One-to-one phone help: waitlist test','SAMPLE TEST ACTIVITY: Capacity is deliberately one so two senior test accounts can demonstrate confirmation, waitlisting, and withdrawal.','Sample help desk',day_zero+interval '8 days 9 hours',day_zero+interval '8 days 10 hours',day_zero+interval '7 days 17 hours',1,'Bring a phone if available. This is a test registration.','open'),
 ('d4000000-0000-4000-8000-000000000008',community,coordinator_uuid,'Neighborhood walking group','SAMPLE CANCELLED PROGRAM: A fictional cancellation example. Participants cannot enroll.','Sample community meeting point',day_zero+interval '9 days 8 hours',day_zero+interval '9 days 9 hours',day_zero+interval '8 days 17 hours',20,'No attendance required; this sample activity is cancelled.','cancelled')
 on conflict(activity_id) do nothing;

 insert into public.announcements(announcement_id,activity_id,posted_by,title,message) values
 ('d4100000-0000-4000-8000-000000000001','d4000000-0000-4000-8000-000000000001',coordinator_uuid,'Preparing for morning movement','SAMPLE NOTICE: Bring water and wear comfortable clothing. Review the activity page for the current schedule.'),
 ('d4100000-0000-4000-8000-000000000002','d4000000-0000-4000-8000-000000000002',coordinator_uuid,'Gardening materials reminder','SAMPLE NOTICE: Bring a reusable container if available. You can still participate without one.'),
 ('d4100000-0000-4000-8000-000000000003','d4000000-0000-4000-8000-000000000007',coordinator_uuid,'Understanding the waitlist','SAMPLE NOTICE: This test session has one place. Additional eligible registrations are waitlisted. Check My activities for your status.'),
 ('d4100000-0000-4000-8000-000000000004','d4000000-0000-4000-8000-000000000008',coordinator_uuid,'Walking group cancelled','SAMPLE NOTICE: This fictional activity is cancelled. Please browse other available programs.')
 on conflict(announcement_id) do nothing;

 -- Optional synthetic participation for explicitly selected test accounts only.
 -- Restrict to pristine sample activities so a rerun cannot fill a real attendee's seat.
 if senior_one is not null and not exists(select 1 from public.enrollments where activity_id='d4000000-0000-4000-8000-000000000007') then
  insert into public.enrollments(enrollment_id,activity_id,senior_id,status,enrolled_at) values
  ('d4200000-0000-4000-8000-000000000001','d4000000-0000-4000-8000-000000000007',senior_one,'confirmed',now());
  if senior_two is not null then
   insert into public.enrollments(enrollment_id,activity_id,senior_id,status,enrolled_at) values
   ('d4200000-0000-4000-8000-000000000002','d4000000-0000-4000-8000-000000000007',senior_two,'waitlisted',now()+interval '1 second');
  end if;
 end if;
 if senior_one is not null and not exists(select 1 from public.enrollments where activity_id='d4000000-0000-4000-8000-000000000006') then
  insert into public.enrollments(enrollment_id,activity_id,senior_id,status,enrolled_at) values
  ('d4200000-0000-4000-8000-000000000003','d4000000-0000-4000-8000-000000000006',senior_one,'completed',day_zero-interval '9 days');
  insert into public.attendance(attendance_id,enrollment_id,attended,remarks,recorded_by,recorded_at) values
  ('d4300000-0000-4000-8000-000000000001','d4200000-0000-4000-8000-000000000003',true,'Synthetic attendance for demonstration only; not evidence of actual participation.',coordinator_uuid,day_zero-interval '7 days'+interval '16 hours');
  insert into public.feedback(feedback_id,enrollment_id,rating,comments,submitted_at) values
  ('d4400000-0000-4000-8000-000000000001','d4200000-0000-4000-8000-000000000003',5,'Synthetic example feedback, not a statement submitted by this account holder.',day_zero-interval '6 days');
 end if;
end $seed$;
commit;

-- Summary of the sample records, not private account data.
select title,status,capacity,start_at at time zone 'Asia/Manila' as starts_philippine_time
from public.activities where activity_id::text like 'd4000000-%' order by start_at;

-- Verify the sample participation in the SQL Editor Results panel.
select u.email, a.title, e.status, t.attended, f.rating
from public.enrollments e
join auth.users u on u.id=e.senior_id
join public.activities a on a.activity_id=e.activity_id
left join public.attendance t on t.enrollment_id=e.enrollment_id
left join public.feedback f on f.enrollment_id=e.enrollment_id
where e.enrollment_id::text like 'd4200000-%'
order by u.email,a.title;

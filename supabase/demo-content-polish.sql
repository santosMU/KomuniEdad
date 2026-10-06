begin;

-- Professionalize the existing synthetic/demo content without deleting
-- enrollments, attendance, feedback, or audit history.

update public.categories
set name='Health & Wellness',
    description='Gentle movement, healthy routines, and everyday well-being'
where trim(name)='Health';

insert into public.categories(name,description,is_active)
values
  ('Arts & Culture','Creative workshops, music, dance, and local culture',true),
  ('Community','Community participation, volunteering, and shared activities',true)
on conflict(name) do update
set description=excluded.description,
    is_active=true;

update public.activities
set title='Morning Movement & Gentle Stretching',
    description='A welcoming guided session of low-impact stretching, breathing, and gentle movement. Participants may rest or adjust exercises at any time.',
    venue='Barangay Community Garden',
    requirements='Comfortable clothing, drinking water, and a small towel.',
    image=null,
    image_url='https://images.pexels.com/photos/37786191/pexels-photo-37786191.png?auto=compress&cs=tinysrgb&w=1200'
where activity_id='d4000000-0000-4000-8000-000000000001';

update public.activities
set title='Grow Together: Urban Gardening',
    description='Learn practical container gardening with herbs and easy-to-grow vegetables. Materials, planting tips, and take-home notes are included.',
    venue='Barangay Learning Center',
    requirements='Bring a reusable container if available. Basic planting materials are provided.',
    is_free=false,
    fee=150.00,
    image=null,
    image_url='https://images.pexels.com/photos/20640188/pexels-photo-20640188.jpeg?auto=compress&cs=tinysrgb&w=1200'
where activity_id='d4000000-0000-4000-8000-000000000002';

update public.enrollments
set payment_status='unpaid'
where activity_id='d4000000-0000-4000-8000-000000000002'
  and status in ('pending','confirmed','waitlisted','completed');

update public.activities
set title='Kwentuhan & Coffee Afternoon',
    description='A relaxed afternoon for coffee, conversation, board games, and meeting neighbors in a friendly setting.',
    venue='Senior Citizens Hall',
    requirements='No special requirements.',
    image=null,
    image_url='https://images.pexels.com/photos/5637706/pexels-photo-5637706.jpeg?auto=compress&cs=tinysrgb&w=1200'
where activity_id='d4000000-0000-4000-8000-000000000003';

update public.activities
set title='Digital Basics: Stay Connected',
    description='Practice video calls, messaging, photo sharing, and simple ways to recognize suspicious links. Staff will guide participants step by step.',
    venue='Community Digital Learning Room',
    requirements='Bring your phone and charger if available. Never share your password during the session.',
    image=null,
    image_url='https://images.pexels.com/photos/35356186/pexels-photo-35356186.jpeg?auto=compress&cs=tinysrgb&w=1200'
where activity_id='d4000000-0000-4000-8000-000000000004';

update public.activities
set title='Healthy Everyday Habits',
    description='A practical group discussion on hydration, sleep, movement, meal planning, and everyday habits that support well-being.',
    venue='Barangay Multi-Purpose Hall',
    requirements='Notebook optional.',
    image=null,
    image_url='https://images.pexels.com/photos/37786191/pexels-photo-37786191.png?auto=compress&cs=tinysrgb&w=1200'
where activity_id='d4000000-0000-4000-8000-000000000005';

update public.activities
set title='Community Storytelling Circle',
    description='Share personal stories, local memories, music, and traditions in a relaxed community circle.',
    venue='Senior Citizens Hall',
    requirements='No special requirements.',
    image=null,
    image_url='https://images.pexels.com/photos/33688541/pexels-photo-33688541.jpeg?auto=compress&cs=tinysrgb&w=1200'
where activity_id='d4000000-0000-4000-8000-000000000006';

update public.activities
set title='One-to-One Smartphone Help',
    description='A small-group help desk for seniors who want assistance with phone settings, messaging, video calls, and common app questions.',
    venue='Digital Help Desk',
    requirements='Bring your phone and charger if available.',
    image=null,
    image_url='https://images.pexels.com/photos/8153902/pexels-photo-8153902.jpeg?auto=compress&cs=tinysrgb&w=1200'
where activity_id='d4000000-0000-4000-8000-000000000007';

update public.activities
set title='Neighborhood Walking Group',
    description='A light-paced neighborhood walk with rest stops and time for conversation. This session has been cancelled.',
    venue='Community Center Entrance',
    requirements='This activity is cancelled. Please check announcements for updates.',
    image=null,
    image_url='https://images.pexels.com/photos/20487017/pexels-photo-20487017.jpeg?auto=compress&cs=tinysrgb&w=1200'
where activity_id='d4000000-0000-4000-8000-000000000008';

update public.activities
set category_id=(select category_id from public.categories where name='Arts & Culture' limit 1),
    title='Creative Memory Scrapbook Workshop',
    description='Create a simple scrapbook page using photos, colored paper, and written memories. Materials and step-by-step guidance are provided.',
    venue='Barangay Multi-Purpose Hall',
    requirements='Bring one or two printed photos if you would like to include them.',
    image=null,
    image_url='https://images.pexels.com/photos/19524029/pexels-photo-19524029.jpeg?auto=compress&cs=tinysrgb&w=1200'
where activity_id='8cb9d6bb-6401-4b28-89ec-faf5e9aca836';

update public.activities
set category_id=(select category_id from public.categories where name='Arts & Culture' limit 1),
    title='Gentle Ballroom Dance Basics',
    description='Learn simple ballroom steps at a relaxed pace with plenty of breaks. No dance experience or partner is required.',
    venue='Barangay Multi-Purpose Hall',
    requirements='Wear comfortable shoes and bring drinking water.',
    is_free=false,
    fee=200.00,
    image=null,
    image_url='https://images.pexels.com/photos/5637706/pexels-photo-5637706.jpeg?auto=compress&cs=tinysrgb&w=1200'
where activity_id='c1be4a8e-c4fd-4574-a3db-d16619a4188a';

update public.enrollments
set payment_status='unpaid'
where activity_id='c1be4a8e-c4fd-4574-a3db-d16619a4188a'
  and status in ('pending','confirmed','waitlisted','completed');

update public.activities
set category_id=(select category_id from public.categories where name='Arts & Culture' limit 1),
    title='Community Dance Social',
    description='A planned social dance session with familiar music, beginner-friendly steps, seating, and regular rest breaks.',
    venue='Senior Citizens Hall',
    requirements='Comfortable shoes and drinking water.',
    image=null,
    image_url='https://images.pexels.com/photos/5637706/pexels-photo-5637706.jpeg?auto=compress&cs=tinysrgb&w=1200'
where activity_id='b7faf2d4-f825-4684-9f66-24e6e2e8bc04';

update public.announcements
set message='Bring water, wear comfortable clothing, and arrive about 10 minutes before the session.'
where announcement_id='d4100000-0000-4000-8000-000000000001';

update public.announcements
set message='Bring a reusable container if available. You can still participate if you do not have one.'
where announcement_id='d4100000-0000-4000-8000-000000000002';

update public.announcements
set title='Smartphone help registration update',
    message='This small-group session has limited places. If it is full, your registration will be placed on the waitlist.'
where announcement_id='d4100000-0000-4000-8000-000000000003';

update public.announcements
set message='This activity has been cancelled. Please browse the other available community programs.'
where announcement_id='d4100000-0000-4000-8000-000000000004';

commit;

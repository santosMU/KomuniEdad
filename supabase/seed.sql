-- Apply after all migrations. Categories only; no personal data or passwords.
insert into public.categories(name,description) values
('Health','Community health education'),('Wellness','Movement and everyday well-being'),
('Social','Conversation and shared interests'),('Learning','Skills and lifelong learning'),
('Community','Local participation and volunteering') on conflict(name) do nothing;

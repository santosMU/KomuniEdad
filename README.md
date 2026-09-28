# KomuniEdad

KomuniEdad is a Laravel-based senior citizen community activity management system.

It uses:

```text
PHP 8.2+
Laravel 12
Blade
Bootstrap 5.3.8
JavaScript
Supabase Auth
Supabase PostgreSQL
```

Current branch:

```text
feature/laravel-phase3
```

Current checked commit:

```text
7d69412064fad7a856a75a0975be3d2c21dab3de
```

Current verification:

```text
Laravel: 32 tests, 119 assertions, PASS
Database suite: PASS
JavaScript syntax: PASS
```

## Run locally

```powershell
cd 'D:\02_Projects\06_ProjectsDev\KomuniEdad'
& 'C:\xampp\php\php.exe' artisan serve --port=8010
```

Open:

```text
http://127.0.0.1:8010
```

For local demo mode:

```env
KOMUNIEDAD_DEMO=true
```

After changing `.env`:

```powershell
php artisan config:clear
```

Useful commands:

```powershell
php artisan route:list
php artisan test
php artisan view:cache
npm run test:db
```

## Responsive Interface

Main files:

```text
public/community.css
resources/views/layout.blade.php
public/community.js
```

The interface adapts to desktop, tablet, and mobile sizes.

Desktop:

```text
full sidebar
collapsible sidebar
multi-column activity cards
wide forms and tables
```

Tablet:

```text
slide-out sidebar
two-column activity cards
compact header
reflowed forms and filters
```

Mobile:

```text
drawer navigation
single-column activity cards
stacked forms
stacked filters
scrollable wide tables
```

Main CSS breakpoints:

```css
@media(max-width:1180px) { ... }
@media(max-width:820px) { ... }
@media(max-width:560px) { ... }
```

The sidebar changes behavior with:

```javascript
const sidebarBreakpoint = window.matchMedia('(max-width: 1180px)');
```

## JavaScript Functionality

Main file:

```text
public/community.js
```

JavaScript handles:

```text
responsive sidebar
keyboard navigation
Escape key closing
focus containment
live search
filter changes
clear filters
Fetch requests
AJAX form submission
validation messages
loading states
duplicate-submit prevention
unsafe-retry prevention
partial page refresh
```

Example sidebar logic:

```javascript
const setSidebarOpen = open => {
    if (sidebarBreakpoint.matches) {
        shell.classList.toggle('sidebar-open', open);
    } else {
        shell.classList.toggle('sidebar-desktop-collapsed', !open);
    }

    syncSidebar();
};
```

Keyboard close:

```javascript
if (
    event.key === 'Escape' &&
    sidebarBreakpoint.matches &&
    shell?.classList.contains('sidebar-open')
) {
    setSidebarOpen(false);
}
```

## API Integration

Main file:

```text
app/Services/Community.php
```

The Laravel application connects to Supabase through this service.

Request flow:

```text
Browser
Laravel route
Controller
Community service
Supabase API
PostgreSQL
Laravel response
Browser
```

Core API method:

```php
public function api(string $method, string $path, array $data = []): mixed
{
    $client = Http::baseUrl(rtrim(config('komuniedad.url'), '/'))
        ->timeout(15)
        ->withHeaders([
            'apikey' => config('komuniedad.key')
        ]);

    if (session('access_token')) {
        $client = $client->withToken(session('access_token'));
    }

    $r = $client->send(
        $method,
        $path,
        $method === 'GET'
            ? ['query' => $data]
            : ['json' => (object) $data]
    );

    return $r->json() ?? [];
}
```

Main Supabase endpoints used:

```text
/auth/v1/token?grant_type=password
/auth/v1/user
/rest/v1/activities
/rest/v1/rpc/enroll_in_activity
/rest/v1/rpc/withdraw_enrollment
/rest/v1/rpc/save_activity
/rest/v1/rpc/manage_enrollment
/rest/v1/rpc/record_attendance
/rest/v1/rpc/submit_feedback
```

Example enrollment request:

```php
$s->api(
    'POST',
    '/rest/v1/rpc/enroll_in_activity',
    ['target' => $id]
);
```

## AJAX and Fetch

Main files:

```text
public/community.js
app/Http/Middleware/JsonFormResponse.php
app/Http/Controllers/CommunityController.php
```

Fetch helper:

```javascript
const response = await fetch(url, {
    credentials: 'same-origin',
    ...options,
    headers: {
        Accept: 'application/json',
        ...options.headers
    },
});
```

Activity search uses Fetch:

```javascript
const data = await request(url, {
    signal: searchRequest.signal
});
```

Laravel returns JSON for dynamic search:

```php
if ($r->expectsJson()) {
    return response()->json([
        'data' => array_values($activities),
        'count' => count($activities),
        'html' => view(
            'activity-results',
            compact('activities', 'enrollments', 'mine')
        )->render(),
    ]);
}
```

The activity result area is updated without a normal page reload:

```javascript
results.innerHTML = data.html;
```

Protected POST forms also use Fetch:

```javascript
const data = await request(form.action, {
    method: 'POST',
    body,
    headers: {
        'X-CSRF-TOKEN':
            document.querySelector('meta[name="csrf-token"]').content
    },
});
```

Duplicate mutations are blocked with:

```javascript
if (form.dataset.busy || mutationBusy) return;
```

## Form Validation

Main files:

```text
app/Http/Controllers/CommunityController.php
app/Http/Controllers/PortalController.php
public/community.js
resources/views/
```

Validation exists at several levels:

```text
HTML
JavaScript
Laravel
Supabase database rules
```

Activity form validation:

```php
$d = $r->validate([
    'title' => 'required|string|max:160',
    'description' => 'required|string|max:5000',
    'venue' => 'required|string|max:200',
    'start_at' => 'required|date',
    'end_at' => 'required|date|after:start_at',
    'cutoff_at' => 'required|date|before_or_equal:start_at',
    'capacity' => 'required|integer|min:1|max:10000',
]);
```

Login validation:

```php
$d = $r->validate([
    'email' => 'required|email',
    'password' => 'required|string'
]);
```

Client-side date validation:

```javascript
if (start?.value && end?.value && end.value <= start.value) {
    end.setCustomValidity('The end must be after the start.');
}
```

AJAX validation errors are attached to the related field:

```javascript
field.setAttribute('aria-invalid', 'true');
field.after(feedback);
```

## Search and Filter

Main files:

```text
resources/views/community.blade.php
app/Http/Controllers/CommunityController.php
public/community.js
resources/views/activity-results.blade.php
```

Available filters:

```text
keyword
category
status
My Activities
clear filters
```

Server-side filtering:

```php
$activities = array_filter(
    $s->activities(),
    fn ($a) =>
        (! $category ||
            ($a['categories']['name'] ?? '') === $category)
        &&
        (! $query ||
            str_contains(
                strtolower($a['title'].' '.$a['venue']),
                strtolower($query)
            ))
);
```

Status filtering:

```php
$activities = array_filter(
    $activities,
    fn ($a) =>
        ! in_array($a['status'], ['draft', 'archived'])
        &&
        (! $status || $a['status'] === $status)
);
```

Search waits briefly before sending the request:

```javascript
searchTimer = setTimeout(() => search(form), 300);
```

Old requests are cancelled:

```javascript
searchRequest?.abort();
searchRequest = new AbortController();
```

## Main File Map

```text
Responsive Interface
public/community.css
resources/views/layout.blade.php
public/community.js

JavaScript Functionality
public/community.js

API Integration
app/Services/Community.php

AJAX and Fetch
public/community.js
app/Http/Middleware/JsonFormResponse.php
app/Http/Controllers/CommunityController.php

Form Validation
app/Http/Controllers/CommunityController.php
app/Http/Controllers/PortalController.php
public/community.js

Search and Filter
resources/views/community.blade.php
app/Http/Controllers/CommunityController.php
public/community.js
resources/views/activity-results.blade.php
```

## Project Structure

```text
app/
    Http/
        Controllers/
        Middleware/
    Services/

resources/
    views/

public/
    community.css
    community.js

routes/
    web.php

supabase/
    migrations/
    tests/

tests/
    Feature/
```

Main backend files:

```text
app/Http/Controllers/CommunityController.php
app/Http/Controllers/PortalController.php
app/Http/Middleware/CommunitySession.php
app/Http/Middleware/JsonFormResponse.php
app/Services/Community.php
routes/web.php
```

Main frontend files:

```text
resources/views/layout.blade.php
resources/views/community.blade.php
resources/views/activity-results.blade.php
resources/views/detail.blade.php
resources/views/workspace.blade.php
resources/views/activity-form.blade.php
resources/views/roster.blade.php
public/community.css
public/community.js
```

## Database

Supabase migrations:

```text
202609150001_domain.sql
202609220002_workflows.sql
202609220003_participants.sql
202609230004_capacity_waitlist.sql
202609230005_locked_ownership.sql
```

The latest migration strengthens coordinator ownership checks after database locks are acquired.

Core database behavior includes:

```text
Row Level Security
role checks
ownership checks
capacity protection
duplicate enrollment prevention
waitlist promotion
attendance restrictions
audit logging
transactional RPC functions
```

## Authentication

Authentication uses Supabase Auth.

Protected requests pass through:

```text
app/Http/Middleware/CommunitySession.php
```

The middleware:

```text
checks the access token
loads the current profile
blocks inactive accounts
rejects expired sessions
```

Public registration creates the lowest-privilege account.

Role changes are restricted to authorized administrator operations.

## Overall Flow

```text
User action
Browser JavaScript
Laravel route
Middleware
Controller
Community service
Supabase
Laravel HTML or JSON response
Browser update
```

Sensitive database operations use:

```text
Laravel
Supabase RPC
PostgreSQL transaction
RLS and ownership checks
Response
```

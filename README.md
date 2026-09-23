# KomuniEdad Phase 3 Presentation Notes

This is the version we should actually keep open during the presentation.

The point is not to explain every file. The point is to quickly show the professor where each Phase 3 requirement is implemented and how it works.

Current branch:

```text
feature/laravel-phase3
```

Current commit checked:

```text
7d69412064fad7a856a75a0975be3d2c21dab3de
```

Current checks:

```text
Laravel: 32 tests, 119 assertions, PASS
Database suite: PASS
JavaScript syntax: PASS
```

## Start the website

On the prepared Windows machine:

```powershell
cd 'D:\02_Projects\06_ProjectsDev\KomuniEdad'
& 'C:\xampp\php\php.exe' artisan serve --port=8010
```

Open:

```text
http://127.0.0.1:8010
```

For the local presentation:

```env
KOMUNIEDAD_DEMO=true
```

If `.env` was changed:

```powershell
& 'C:\xampp\php\php.exe' artisan config:clear
```

If PHP is already in PATH:

```powershell
php artisan serve --port=8010
```

The normal website does not need a frontend build command.

---

# Phase 3 Requirement 1

## Responsive Interface

Requirement:

The website must adapt properly to desktop, tablet, and mobile screen sizes.

We handle this mainly in:

```text
public/community.css
resources/views/layout.blade.php
public/community.js
```

### What we implemented

Desktop:

```text
Permanent sidebar
Sidebar can be collapsed
Multi-column activity cards
Wide forms and tables
Full header layout
```

Tablet:

```text
Sidebar becomes a slide-out drawer
Activity cards become two columns
Forms begin stacking
Filters reorganize
Header becomes more compact
```

Mobile:

```text
Slide-out navigation
Single-column activity cards
Stacked forms
Stacked filters
Responsive banner
Smaller spacing
Scrollable wide tables
```

The main responsive breakpoints are in:

```css
@media(max-width:1180px) {
    /* tablet and drawer navigation */
}

@media(max-width:820px) {
    /* smaller tablets */
}

@media(max-width:560px) {
    /* phones */
}
```

The sidebar also changes behavior depending on screen size.

From `public/community.js`:

```javascript
const sidebarBreakpoint = window.matchMedia('(max-width: 1180px)');
```

On smaller screens the sidebar opens as a drawer.

It can be closed using:

```text
Close button
Backdrop
Escape key
```

Keyboard focus is also kept inside the open mobile menu.

### What to show the professor

Start with the browser at normal desktop size.

Show:

```text
Sidebar
Activity cards
Search filters
Banner
```

Then resize the browser smaller.

Show that:

```text
Sidebar disappears into the Menu button
Menu slides in
Activity cards reduce to two columns
Then one column on phone size
Forms remain usable
```

### What to say

The site uses CSS media queries for the actual layout changes. JavaScript only handles the interactive sidebar behavior. We kept one website instead of building separate desktop and mobile pages.

---

# Phase 3 Requirement 2

## JavaScript Functionality

Requirement:

The project needs JavaScript that provides real interactive behavior.

Our main JavaScript file is:

```text
public/community.js
```

It is loaded by:

```text
resources/views/layout.blade.php
```

### What our JavaScript does

The file currently handles:

```text
Responsive sidebar
Open and close navigation
Escape key support
Keyboard focus containment
Live activity search
Automatic filter updates
Clear search button
Fetch requests
AJAX form submissions
Validation error display
Loading and busy states
Duplicate submission prevention
Unsafe retry prevention
Page section refresh after successful changes
```

One example is the responsive sidebar:

```javascript
const setSidebarOpen = open => {
    if (!shell || !sidebar) return;

    if (sidebarBreakpoint.matches) {
        shell.classList.toggle('sidebar-open', open);
    } else {
        shell.classList.toggle('sidebar-desktop-collapsed', !open);
    }

    syncSidebar();
};
```

Another example is keyboard behavior:

```javascript
if (
    event.key === 'Escape' &&
    sidebarBreakpoint.matches &&
    shell?.classList.contains('sidebar-open')
) {
    setSidebarOpen(false);
}
```

### What to show the professor

Use the mobile-sized layout.

Show:

```text
Open Menu
Tab through the menu
Press Escape
Menu closes
```

Then show activity search typing.

The page should update automatically after a short delay.

### What to say

Our JavaScript is not just visual effects. It controls navigation, live search, AJAX submissions, validation feedback, loading states, and duplicate request protection.

---

# Phase 3 Requirement 3

## API Integration

Requirement:

The website must request, receive, and use data from an API.

The important file is:

```text
app/Services/Community.php
```

This is the Laravel service that communicates with Supabase.

The browser does not directly receive the Supabase credentials.

The flow is:

```text
Browser
Laravel
Community service
Supabase API
PostgreSQL
Laravel
Browser
```

### Main API method

Inside `Community.php`:

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

This method is reused throughout the application.

### Examples of API calls

Login:

```text
/auth/v1/token?grant_type=password
```

Current authenticated user:

```text
/auth/v1/user
```

Activities:

```text
/rest/v1/activities
```

Enrollment:

```text
/rest/v1/rpc/enroll_in_activity
```

Withdrawal:

```text
/rest/v1/rpc/withdraw_enrollment
```

Staff activity saving:

```text
/rest/v1/rpc/save_activity
```

Attendance:

```text
/rest/v1/rpc/record_attendance
```

Participant management:

```text
/rest/v1/rpc/manage_enrollment
```

### Example from the Senior enrollment flow

Laravel calls:

```php
$s->api(
    'POST',
    '/rest/v1/rpc/enroll_in_activity',
    ['target' => $id]
);
```

Supabase receives the request.

The database checks:

```text
Current user
Senior role
Activity status
Registration cutoff
Duplicate enrollment
Capacity
```

It then returns the enrollment result.

### What to show the professor

The easiest API demonstration is:

```text
Open activities
Search activities
Join an activity
Open My Activities
```

If using live Supabase, the data comes from Supabase and is returned through Laravel.

### What to say

Laravel acts as our application layer. It sends authenticated requests to Supabase Auth, REST endpoints, and PostgreSQL RPC functions. The API response is then used to build the Blade page or JSON response shown by the browser.

---

# Phase 3 Requirement 4

## AJAX and Fetch

Requirement:

The website must retrieve or submit information without reloading the entire page.

This is implemented in:

```text
public/community.js
app/Http/Middleware/JsonFormResponse.php
app/Http/Controllers/CommunityController.php
```

## Live activity search

The search function uses Fetch:

```javascript
const data = await request(url, {
    signal: searchRequest.signal
});
```

The request helper itself uses:

```javascript
const response = await fetch(url, {
    credentials: 'same-origin',
    ...options,
    headers: {
        Accept: 'application/json',
        ...options.headers
    }
});
```

The Laravel controller notices that JSON was requested.

Inside `CommunityController@index`:

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

JavaScript then replaces only the results area:

```javascript
results.innerHTML = data.html;
```

It also updates the result count:

```javascript
document.querySelector('#result-count').textContent =
    `${data.count} ${data.count === 1 ? 'activity' : 'activities'}`;
```

No normal page reload is required.

## AJAX form submissions

Protected POST forms are also intercepted.

Example:

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

Laravel processes the normal controller action.

Then `JsonFormResponse.php` changes a redirect response into JSON:

```php
return response()->json([
    'message' => $request->session()->pull(
        'status',
        'Changes saved.'
    ),
    'redirect' => $response->getTargetUrl(),
]);
```

JavaScript refreshes the main content instead of doing a traditional form navigation.

### Safety behavior

We also prevent multiple mutations from running at the same time:

```javascript
if (form.dataset.busy || mutationBusy) return;
```

And we disable buttons while saving:

```javascript
buttons.forEach(button => button.disabled = true);
```

### What to show the professor

The easiest demonstration:

```text
Type in activity search
Do not press Enter
Results update
Change Category
Results update again
Clear filters
Results reset
```

Then demonstrate one POST action, for example:

```text
Enroll
Withdraw
Save profile
Save attendance
```

The action is submitted through Fetch.

### What to say

We use Fetch for both reading and submitting data. Search returns JSON plus server-rendered result HTML, and protected forms return JSON after Laravel finishes the action. This lets us update the current page without a normal full-page form submission.

---

# Phase 3 Requirement 5

## Form Validation

Requirement:

Forms need required fields, format checks, restrictions, and understandable error messages.

Validation exists at multiple levels.

```text
HTML form rules
JavaScript
Laravel validation
Supabase database rules
```

Laravel and Supabase remain the final authority.

## Example: activity form validation

Inside `PortalController.php`:

```php
$d = $r->validate([
    'title' => 'required|string|max:160',
    'description' => 'required|string|max:5000',
    'venue' => 'required|string|max:200',
    'category_id' => 'required|string',
    'start_at' => 'required|date',
    'end_at' => 'required|date|after:start_at',
    'cutoff_at' => 'required|date|before_or_equal:start_at',
    'capacity' => 'required|integer|min:1|max:10000',
    'status' => [
        'required',
        Rule::in([
            'draft',
            'open',
            'full',
            'ongoing',
            'completed',
            'cancelled',
            'archived'
        ])
    ],
]);
```

This prevents bad values even if someone bypasses the browser validation.

## Example: login validation

Inside `CommunityController.php`:

```php
$d = $r->validate([
    'email' => 'required|email',
    'password' => 'required|string'
]);
```

## Client-side date validation

Inside `community.js`:

```javascript
if (start?.value && end?.value && end.value <= start.value) {
    end.setCustomValidity('The end must be after the start.');
}

if (start?.value && cutoff?.value && cutoff.value > start.value) {
    cutoff.setCustomValidity(
        'Registration must close before or at the start.'
    );
}
```

## AJAX validation errors

Laravel validation errors are returned as JSON.

JavaScript attaches the errors to the correct field:

```javascript
field.setAttribute('aria-invalid', 'true');
field.after(feedback);
```

The user sees the error beside the field instead of receiving a generic failure message.

### What to show the professor

Open the Create Activity form.

Try:

```text
Leave Title empty
Set End before Start
Set Cutoff after Start
Use invalid capacity
```

Show that the form refuses invalid data.

You can also show registration or profile validation.

### What to say

We validate in the browser for immediate feedback, but we also validate again in Laravel. Important business rules are checked again in Supabase, so browser validation is not being trusted for security.

---

# Phase 3 Requirement 6

## Search and Filter

Requirement:

Users need a functional way to dynamically find or display specific information.

This is implemented on the Senior activity page.

Main files:

```text
resources/views/community.blade.php
app/Http/Controllers/CommunityController.php
public/community.js
resources/views/activity-results.blade.php
```

## Search fields

The page includes:

```text
Keyword search
Category filter
Status filter
My Activities filter
Clear filters
```

## Server-side filtering

Inside `CommunityController@index`:

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

Status is also checked:

```php
$activities = array_filter(
    $activities,
    fn ($a) =>
        ! in_array($a['status'], ['draft', 'archived'])
        &&
        (! $status || $a['status'] === $status)
);
```

Capacity is considered when displaying Open or Full:

```php
if (in_array($a['status'], ['open', 'full'])) {
    $a['status'] =
        $a['confirmed'] >= $a['capacity']
            ? 'full'
            : 'open';
}
```

## Search updates automatically

Typing in the search box starts a short timer:

```javascript
searchTimer = setTimeout(() => search(form), 300);
```

This avoids sending a request for every single keystroke.

Old requests are cancelled:

```javascript
searchRequest?.abort();
searchRequest = new AbortController();
```

This prevents a slower old search result from replacing a newer one.

Changing Category or Status also triggers search:

```javascript
document.addEventListener('change', event => {
    const form = event.target.closest('[data-activity-search]');
    if (form) search(form);
});
```

### What to show the professor

Use this exact order:

```text
Search "garden"
Choose Learning
Change status
Clear filters
```

Point out that:

```text
The result count changes
The activity cards change
The page itself does not fully reload
```

### What to say

The filtering is performed by Laravel using current activity data. JavaScript sends the selected search values with Fetch, then replaces only the activity results section.

---

# How the Six Requirements Connect

This is the easiest overall explanation.

```text
Responsive Interface
public/community.css
layout.blade.php
community.js

JavaScript Functionality
public/community.js

API Integration
app/Services/Community.php
Supabase Auth
Supabase REST
Supabase RPC

AJAX / Fetch
public/community.js
JsonFormResponse.php
CommunityController.php

Form Validation
Blade forms
community.js
Laravel controllers
Supabase SQL rules

Search / Filter
community.blade.php
CommunityController.php
community.js
activity-results.blade.php
```

---

# Main Request Flow

If the professor asks how all of this connects, use this:

```text
User interacts with the page
Browser JavaScript handles the interaction
Laravel route receives the request
Middleware checks the session
Controller validates the request
Community service talks to Supabase
Supabase returns data
Laravel returns HTML or JSON
JavaScript updates the page
```

For database-sensitive actions:

```text
Browser
Laravel
Supabase RPC
PostgreSQL transaction
RLS and ownership checks
Result returned to Laravel
Result returned to browser
```

---

# Files to Open During the Presentation

If we need to show actual source code, these are enough.

## Responsive Interface

```text
public/community.css
resources/views/layout.blade.php
```

Search in the CSS for:

```text
@media(max-width:1180px)
@media(max-width:820px)
@media(max-width:560px)
```

## JavaScript

```text
public/community.js
```

Search for:

```text
fetch(
addEventListener
setSidebarOpen
search(form)
mutationBusy
```

## API Integration

```text
app/Services/Community.php
```

Search for:

```text
public function api
public function rpc
public function activities
```

## AJAX

```text
public/community.js
app/Http/Middleware/JsonFormResponse.php
```

## Validation

```text
app/Http/Controllers/PortalController.php
app/Http/Controllers/CommunityController.php
```

Search for:

```text
$r->validate
```

## Search and Filter

```text
app/Http/Controllers/CommunityController.php
resources/views/community.blade.php
resources/views/activity-results.blade.php
public/community.js
```

---

# Simple Presentation Order

Do not jump around the code immediately.

First show that the actual website works.

## 1. Desktop

Show:

```text
Home page
Activity cards
Search
Filters
Sidebar
```

## 2. Tablet and Mobile

Resize the browser.

Show:

```text
Responsive activity cards
Slide-out menu
Mobile form layout
```

## 3. JavaScript

Show:

```text
Menu interaction
Live search
Clear filter button
```

## 4. API

Explain:

```text
Laravel Community service
Supabase Auth
Supabase REST
Supabase RPC
```

If live mode is available, show real data.

## 5. AJAX

Type a search without refreshing the page.

Then perform one POST action.

## 6. Validation

Submit one intentionally invalid form.

Show the field error.

## 7. Search and Filter

Finish with a quick combined keyword, category, and status search.

---

# If the Professor Asks "Where Is Phase 3?"

Answer with this:

Phase 3 is mainly visible in four places.

```text
public/community.css
public/community.js
app/Services/Community.php
app/Http/Controllers/
```

The CSS handles desktop, tablet, and mobile layouts.

The JavaScript handles the interactive interface and Fetch requests.

The Community service connects Laravel to the Supabase API.

The controllers validate requests, perform filtering, and return either pages or JSON.

---

# If the Professor Asks "Is Fetch Really Being Used?"

Yes.

The request helper in:

```text
public/community.js
```

calls:

```javascript
fetch(url, {
    credentials: 'same-origin',
    headers: {
        Accept: 'application/json'
    }
});
```

Search uses this to retrieve filtered activity results.

Protected forms also use Fetch with:

```javascript
method: 'POST'
```

and:

```javascript
'X-CSRF-TOKEN': document.querySelector(
    'meta[name="csrf-token"]'
).content
```

---

# If the Professor Asks "Where Does the API Data Come From?"

Supabase.

Laravel calls it through:

```text
app/Services/Community.php
```

For example:

```php
$this->api(
    'GET',
    '/rest/v1/activities',
    [
        'select' => '*,categories(name)',
        'order' => 'start_at.asc'
    ]
);
```

Sensitive actions use RPC functions such as:

```text
enroll_in_activity
withdraw_enrollment
save_activity
manage_enrollment
record_attendance
submit_feedback
```

---

# If the Professor Asks "Why Not Call Supabase Directly From JavaScript?"

Because we keep the application rules on the server.

The browser sends requests to Laravel.

Laravel checks:

```text
Session
Role
Validation
Ownership
CSRF
```

Then Laravel communicates with Supabase.

Supabase applies another layer through:

```text
Authentication
RLS
RPC authorization
Database constraints
```

---

# If the Professor Asks "What Happens When Search Is Used?"

Use this sequence:

```text
User types
community.js waits 300ms
Fetch request is sent to /
CommunityController filters activities
Laravel renders activity-results.blade.php
Laravel sends JSON
JavaScript replaces only the results section
Result count updates
```

---

# If the Professor Asks "What Validation Do You Have?"

Short answer:

```text
HTML validation for basic form rules
JavaScript for immediate feedback
Laravel validation for every submitted request
Database constraints and RPC rules for critical business rules
```

Example:

```php
'end_at' => 'required|date|after:start_at',
'cutoff_at' => 'required|date|before_or_equal:start_at',
'capacity' => 'required|integer|min:1|max:10000',
```

---

# Final Short Explanation

If time is almost done, say this:

KomuniEdad satisfies Phase 3 using one responsive Laravel interface. CSS media queries handle desktop, tablet, and mobile layouts. `community.js` provides the interactive behavior and Fetch requests. Laravel connects to Supabase through the `Community` service, which handles Auth, REST, and RPC calls. Search and filters update dynamically without a full page reload, protected forms can submit through AJAX, and validation is enforced in both Laravel and the database.

That is the main Phase 3 implementation.

# Google Classroom Clone Project Plan

## Phase 0 — Foundation

1. `config.php` — database connection, session start, helper functions
2. `db.sql` — schema for:
   - `users`
   - `classes`
   - `class_members`
   - `assignments`
   - `submissions`
   - `announcements`
3. `style.css` — color variables, layout, responsive grid, Google Classroom style
4. `index.php` — public landing page or dashboard redirect

## Phase 1 — Core Pages

- `auth/login.php`
- `auth/register.php`
- `auth/logout.php`
- `includes/navbar.php`
- `includes/sidebar.php`
- `home/dashboard.php`
- `classes/create.php`
- `classes/join.php`
- `classes/stream.php`
- `classes/classwork.php`
- `classes/people.php`
- `todo/index.php`
- `calendar/index.php`
- `assignments/submit.php`
- `assignments/view_work.php`
- `settings/index.php`

## Phase 2 — UI Flow and Data

- Implement teacher/student roles
- Add class cards and navigation
- Add class code generation and join logic
- Add announcement posting and stream UI
- Add assignment creation and submission flow
- Add calendar and to-do filters
- Add settings toggles and profile UI

## Phase 3 — Review & Test

1. Test register/login and session persistence
2. Check navigation between pages
3. Verify class create/join and class membership
4. Test assignment submission and status display
5. Validate UI on desktop and small screens
6. Review for duplicate files and folder structure clarity

## Important

- Mock any Google-specific integration
- Keep folder structure aligned with the design
- Build the UI first, then connect dynamic PHP + MySQL

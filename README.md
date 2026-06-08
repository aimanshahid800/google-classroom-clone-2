# Google Classroom Clone

This is the scaffold for a Google Classroom clone built with PHP, MySQL, and HTML/CSS.

## Project structure

- `config.php` — Database connection and shared config
- `db.sql` — Database schema for users, classes, assignments, submissions
- `style.css` — Shared styles and theme variables
- `auth/` — Login/Register/Logout
- `includes/` — Navbar and sidebar includes
- `home/` — Dashboard/home page
- `classes/` — Class stream, classwork, people, create/join logic
- `todo/` — To-do page with tabs
- `calendar/` — Calendar page UI
- `assignments/` — Assignment submit/view work
- `settings/` — Settings page

## Next steps

1. Build `config.php` and `db.sql`
2. Create authentication flow (`auth/register.php`, `auth/login.php`, `auth/logout.php`)
3. Add reusable layout with `includes/navbar.php` and `includes/sidebar.php`
4. Implement `home/dashboard.php` and `classes/stream.php`
5. Add assignments, to-do, calendar, and settings pages

## Development notes

- Use sessions for authentication
- Use prepared statements for database queries
- Start with a clean UI that matches Google Classroom layout
- Mock external features like "Manage your Google Account" and "Join with class code"

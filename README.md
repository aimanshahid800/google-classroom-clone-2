# Google Classroom Clone

A fully functional Google Classroom clone built with PHP, MySQL, and HTML/CSS as a university semester project.

## Tech Stack

- **Backend:** PHP (PDO, Sessions)
- **Database:** MySQL
- **Frontend:** HTML, CSS, JavaScript
- **Local Server:** XAMPP

## Project Structure

google-classroom-clone-2/
├── assignments/
│ ├── create.php
│ ├── delete_assignment.php
│ ├── submit.php
│ └── view_work.php
├── auth/
│ ├── login.php
│ ├── logout.php
│ └── register.php
├── calendar/
│ └── index.php
├── classes/
│ ├── add_comment.php
│ ├── archived.php
│ ├── classwork.php
│ ├── create.php
│ ├── delete_announcement.php
│ ├── get_comments.php
│ ├── join.php
│ ├── manage.php
│ ├── people.php
│ └── stream.php
├── home/
│ ├── archived_classes.php
│ └── dashboard.php
├── icons/
├── includes/
│ ├── navbar.php
│ └── sidebar.php
├── settings/
│ └── index.php
├── todo/
│ └── index.php
├── uploads/
├── config.php
├── db.sql
├── index.php
├── README.md
└── style.css

## Features

- User authentication (register, login, logout)
- Role-based access (Teacher / Student)
- Create and join classes via class code
- Class stream with announcements and comments
- Assignment creation, submission, and grading
- Classwork and people management
- Archived classes support
- To-do list
- Calendar page
- Settings page

## Database

Import `db.sql` into phpMyAdmin to set up the database (`classroom_clone`).

Tables: `users`, `classes`, `class_members`, `assignments`, `submissions`, `announcements`, `comments`

## Setup

1. Clone the repo into `htdocs/` (XAMPP)
2. Import `db.sql` in phpMyAdmin
3. Configure DB credentials in `config.php`
4. Run via `http://localhost/google-classroom-clone-2/`

## Development Notes

- Sessions used for authentication
- Prepared statements used for all database queries
- UI inspired by Google Classroom layout

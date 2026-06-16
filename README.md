# Google Classroom Clone

A fully functional Google Classroom clone built with PHP, MySQL, and HTML/CSS as a university semester project.

## Tech Stack

- **Backend:** PHP (PDO, Sessions)
- **Database:** MySQL
- **Frontend:** HTML, CSS, JavaScript
- **Local Server:** XAMPP

## Project Structure

google-classroom-clone-2/

├── assignments/ — Create, submit, and view assignments

├── auth/ — Login, register, logout

├── calendar/ — Calendar UI

├── classes/ — Stream, classwork, people, announcements, comments

├── home/ — Dashboard and archived classes

├── icons/ — Icon assets

├── includes/ — Shared navbar and sidebar

├── settings/ — Settings page

├── todo/ — To-do list with tabs

├── uploads/ — Uploaded files storage

├── config.php — Database connection and shared config

├── db.sql — Full database schema

├── index.php — Entry point

├── style.css — Shared styles and theme variables

└── README.md — Project documentation

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

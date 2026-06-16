# 🎓 Google Classroom Clone

A fully functional Google Classroom clone built as a university semester project by a team of 4.

![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white)
![XAMPP](https://img.shields.io/badge/XAMPP-FB7A24?style=for-the-badge&logo=xampp&logoColor=white)

---

## ✨ Features

- 🔐 User authentication (register, login, logout)
- 👩‍🏫 Role-based access — Teacher & Student views
- 🏫 Create & join classes via class code
- 📢 Class stream with announcements & comments
- 📝 Assignment creation, submission & grading
- 📚 Classwork and people management
- 🗄️ Archived classes support
- ✅ To-do list with tabs
- 📅 Calendar page
- ⚙️ Settings page
- 🌙 Dark mode support

---

## 🗂️ Project Structure

```
google-classroom-clone-2/
├── 📁 assignments/       — Create, submit, view & grade assignments
├── 📁 auth/              — Login, register, logout
├── 📁 calendar/          — Calendar UI
├── 📁 classes/           — Stream, classwork, people, announcements
├── 📁 home/              — Dashboard & archived classes
├── 📁 icons/             — Icon assets
├── 📁 includes/          — Shared navbar & sidebar
├── 📁 settings/          — Settings page
├── 📁 todo/              — To-do list
├── 📁 uploads/           — Uploaded files storage
├── ⚙️ config.php         — Database connection & shared config
├── 🗃️ db.sql             — Full database schema
├── 🏠 index.php          — Entry point
└── 🎨 style.css          — Shared styles & theme variables
```

## 🛠️ Tech Stack

| Layer        | Technology            |
| ------------ | --------------------- |
| Backend      | PHP (PDO, Sessions)   |
| Database     | MySQL                 |
| Frontend     | HTML, CSS, JavaScript |
| Local Server | XAMPP                 |

---

## ⚡ Setup & Installation

1. Clone the repo into your `htdocs/` folder

```bash
git clone https://github.com/aimanshahid800/google-classroom-clone-2.git
```

2. Import `db.sql` into phpMyAdmin
3. Configure your DB credentials in `config.php`
4. Start Apache & MySQL in XAMPP
5. Open in browser:
   http://localhost/Uni-Team-Project/google-classroom-clone-2/

---

## 🗃️ Database Tables

| Table           | Description                   |
| --------------- | ----------------------------- |
| `users`         | Stores all registered users   |
| `classes`       | Class details & codes         |
| `class_members` | Student-teacher relationships |
| `assignments`   | Assignment data               |
| `submissions`   | Student submissions           |
| `announcements` | Class announcements           |
| `comments`      | Comments on announcements     |

---

## 👩‍💻 Development Notes

- ✅ Sessions used for authentication
- ✅ Prepared statements for all DB queries
- ✅ UI inspired by Google Classroom
- ✅ Role-based UI — buttons & views differ per role

---

## 👥 Team

Built with ❤️ by a team of 4 — BSCS students at LCWU 🎓

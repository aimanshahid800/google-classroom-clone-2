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
├── 📁 assignments/
│   ├── 📄 create.php
│   ├── 📄 delete_assignment.php
│   ├── 📄 submit.php
│   └── 📄 view_work.php
├── 📁 auth/
│   ├── 📄 login.php
│   ├── 📄 logout.php
│   └── 📄 register.php
├── 📁 calendar/
│   └── 📄 index.php
├── 📁 classes/
│   ├── 📄 add_comment.php
│   ├── 📄 archived.php
│   ├── 📄 classwork.php
│   ├── 📄 create.php
│   ├── 📄 delete_announcement.php
│   ├── 📄 get_comments.php
│   ├── 📄 join.php
│   ├── 📄 manage.php
│   ├── 📄 people.php
│   └── 📄 stream.php
├── 📁 home/
│   ├── 📄 archived_classes.php
│   └── 📄 dashboard.php
├── 📁 icons/
├── 📁 includes/
│   ├── 📄 navbar.php
│   └── 📄 sidebar.php
├── 📁 settings/
│   └── 📄 index.php
├── 📁 todo/
│   └── 📄 index.php
├── 📁 uploads/
├── ⚙️ config.php
├── 🗃️ db.sql
├── 🏠 index.php
├── 📖 README.md
└── 🎨 style.css
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

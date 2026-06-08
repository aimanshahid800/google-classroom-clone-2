-- Google Classroom Clone database schema

CREATE DATABASE IF NOT EXISTS classroom_clone DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE classroom_clone;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('student','teacher') NOT NULL DEFAULT 'student',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE classes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  section VARCHAR(100) DEFAULT NULL,
  subject VARCHAR(100) DEFAULT NULL,
  room VARCHAR(100) DEFAULT NULL,
  code VARCHAR(10) NOT NULL UNIQUE,
  owner_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (owner_id) REFERENCES users(id)
);

CREATE TABLE class_members (
  id INT AUTO_INCREMENT PRIMARY KEY,
  class_id INT NOT NULL,
  user_id INT NOT NULL,
  role ENUM('student','teacher') NOT NULL DEFAULT 'student',
  joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (class_id) REFERENCES classes(id),
  FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE assignments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  class_id INT NOT NULL,
  title VARCHAR(255) NOT NULL,
  topic VARCHAR(150) DEFAULT NULL,
  due_date DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (class_id) REFERENCES classes(id)
);

CREATE TABLE submissions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  assignment_id INT NOT NULL,
  user_id INT NOT NULL,
  content TEXT,
  file_path VARCHAR(255) DEFAULT NULL,
  status ENUM('handed_in','missing','done') NOT NULL DEFAULT 'handed_in',
  submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (assignment_id) REFERENCES assignments(id),
  FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE announcements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  class_id INT NOT NULL,
  user_id INT NOT NULL,
  message TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (class_id) REFERENCES classes(id),
  FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE user_settings (
  user_id INT PRIMARY KEY,
  email_notifications TINYINT(1) NOT NULL DEFAULT 1,
  comment_notifications TINYINT(1) NOT NULL DEFAULT 1,
  due_date_reminders TINYINT(1) NOT NULL DEFAULT 1,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

<?php

namespace Tests\Integration;

use PDO;
use PHPUnit\Framework\TestCase;

abstract class DatabaseTestCase extends TestCase
{
    protected PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->createSchema();
    }

    private function createSchema(): void
    {
        $this->pdo->exec('
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(255) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                role TEXT NOT NULL DEFAULT "student",
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ');

        $this->pdo->exec('
            CREATE TABLE classes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(150) NOT NULL,
                section VARCHAR(100) DEFAULT NULL,
                subject VARCHAR(100) DEFAULT NULL,
                room VARCHAR(100) DEFAULT NULL,
                code VARCHAR(10) NOT NULL UNIQUE,
                owner_id INTEGER NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (owner_id) REFERENCES users(id)
            )
        ');

        $this->pdo->exec('
            CREATE TABLE class_members (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                class_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                role TEXT NOT NULL DEFAULT "student",
                joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (class_id) REFERENCES classes(id),
                FOREIGN KEY (user_id) REFERENCES users(id)
            )
        ');

        $this->pdo->exec('
            CREATE TABLE assignments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                class_id INTEGER NOT NULL,
                title VARCHAR(255) NOT NULL,
                topic VARCHAR(150) DEFAULT NULL,
                due_date DATETIME DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (class_id) REFERENCES classes(id)
            )
        ');

        $this->pdo->exec('
            CREATE TABLE submissions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                assignment_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                content TEXT,
                file_path VARCHAR(255) DEFAULT NULL,
                status TEXT NOT NULL DEFAULT "handed_in",
                submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (assignment_id) REFERENCES assignments(id),
                FOREIGN KEY (user_id) REFERENCES users(id)
            )
        ');

        $this->pdo->exec('
            CREATE TABLE announcements (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                class_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                message TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (class_id) REFERENCES classes(id),
                FOREIGN KEY (user_id) REFERENCES users(id)
            )
        ');
    }

    protected function createUser(string $name, string $email, string $password, string $role = 'student'): int
    {
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');
        $stmt->execute([$name, $email, $hashed, $role]);

        return (int) $this->pdo->lastInsertId();
    }
}

<?php

namespace App\Classroom;

use PDO;

class ClassService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function generateClassCode(): string
    {
        return strtoupper(substr(md5(uniqid()), 0, 6));
    }

    public function createClass(string $name, string $section, string $subject, string $room, int $ownerId): int
    {
        $code = $this->generateClassCode();

        $stmt = $this->pdo->prepare(
            'INSERT INTO classes (name, section, subject, room, code, owner_id) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$name, $section, $subject, $room, $code, $ownerId]);
        $classId = (int) $this->pdo->lastInsertId();

        $stmt = $this->pdo->prepare(
            'INSERT INTO class_members (class_id, user_id, role) VALUES (?, ?, ?)'
        );
        $stmt->execute([$classId, $ownerId, 'teacher']);

        return $classId;
    }

    public function joinClass(string $code, int $userId): ?int
    {
        $stmt = $this->pdo->prepare('SELECT id, name FROM classes WHERE code = ?');
        $stmt->execute([$code]);
        $class = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$class) {
            return null;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO class_members (class_id, user_id, role) VALUES (?, ?, ?)'
        );
        $stmt->execute([$class['id'], $userId, 'student']);

        return (int) $class['id'];
    }

    public function isMember(int $classId, int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT id FROM class_members WHERE class_id = ? AND user_id = ?'
        );
        $stmt->execute([$classId, $userId]);

        return (bool) $stmt->fetch();
    }

    public function isTeacher(int $classId, int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT id FROM class_members WHERE class_id = ? AND user_id = ? AND role = "teacher"'
        );
        $stmt->execute([$classId, $userId]);

        return (bool) $stmt->fetch();
    }

    public function getClassById(int $classId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.*, u.name AS teacher_name FROM classes c JOIN users u ON c.owner_id = u.id WHERE c.id = ?'
        );
        $stmt->execute([$classId]);
        $class = $stmt->fetch(PDO::FETCH_ASSOC);

        return $class ?: null;
    }

    public function getClassByCode(string $code): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, name, code FROM classes WHERE code = ?');
        $stmt->execute([$code]);
        $class = $stmt->fetch(PDO::FETCH_ASSOC);

        return $class ?: null;
    }

    public function getMembers(int $classId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT u.id, u.name, u.email, cm.role FROM class_members cm JOIN users u ON cm.user_id = u.id WHERE cm.class_id = ? ORDER BY cm.role DESC, u.name ASC'
        );
        $stmt->execute([$classId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUserClasses(int $userId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT c.id, c.name, c.section, c.subject, c.code, c.owner_id, u.name AS teacher_name
            FROM classes c
            JOIN class_members cm ON c.id = cm.class_id
            JOIN users u ON c.owner_id = u.id
            WHERE cm.user_id = ?
            ORDER BY c.created_at DESC
        ');
        $stmt->execute([$userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

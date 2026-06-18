<?php

namespace App\Assignment;

use PDO;

class AssignmentService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function createAssignment(int $classId, string $title, string $topic, ?string $dueDate): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO assignments (class_id, title, topic, due_date) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$classId, $title, $topic, $dueDate]);

        return (int) $this->pdo->lastInsertId();
    }

    public function getAssignment(int $assignmentId, int $classId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT a.*, c.name as class_name FROM assignments a JOIN classes c ON a.class_id = c.id WHERE a.id = ? AND a.class_id = ?'
        );
        $stmt->execute([$assignmentId, $classId]);
        $assignment = $stmt->fetch(PDO::FETCH_ASSOC);

        return $assignment ?: null;
    }

    public function getClassAssignments(int $classId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM assignments WHERE class_id = ? ORDER BY due_date ASC'
        );
        $stmt->execute([$classId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function submitWork(int $assignmentId, int $userId, string $content): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT id FROM submissions WHERE assignment_id = ? AND user_id = ?'
        );
        $stmt->execute([$assignmentId, $userId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $stmt = $this->pdo->prepare(
                'UPDATE submissions SET content = ?, status = ?, submitted_at = CURRENT_TIMESTAMP WHERE id = ?'
            );
            $stmt->execute([$content, 'handed_in', $existing['id']]);

            return (int) $existing['id'];
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO submissions (assignment_id, user_id, content, status) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$assignmentId, $userId, $content, 'handed_in']);

        return (int) $this->pdo->lastInsertId();
    }

    public function getSubmission(int $assignmentId, int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM submissions WHERE assignment_id = ? AND user_id = ?'
        );
        $stmt->execute([$assignmentId, $userId]);
        $submission = $stmt->fetch(PDO::FETCH_ASSOC);

        return $submission ?: null;
    }

    public function getAssignmentSubmissions(int $assignmentId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT s.*, u.name, u.email
            FROM submissions s
            JOIN users u ON s.user_id = u.id
            WHERE s.assignment_id = ?
            ORDER BY s.submitted_at DESC
        ');
        $stmt->execute([$assignmentId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function markSubmissionDone(int $submissionId): bool
    {
        $stmt = $this->pdo->prepare('UPDATE submissions SET status = ? WHERE id = ?');

        return $stmt->execute(['done', $submissionId]);
    }
}

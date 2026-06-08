<?php

namespace App\Auth;

use PDO;

class AuthService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function register(string $name, string $email, string $password, string $role): int
    {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$name, $email, $hashedPassword, $role]);

        return (int) $this->pdo->lastInsertId();
    }

    public function authenticate(string $email, string $password): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, name, email, password, role FROM users WHERE email = ?'
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            return [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role'],
            ];
        }

        return null;
    }

    public function emailExists(string $email): bool
    {
        $stmt = $this->pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);

        return (bool) $stmt->fetch();
    }

    public static function currentUser(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function isLoggedIn(): bool
    {
        return !empty($_SESSION['user_id']);
    }
}

<?php

namespace App\Validation;

class RegistrationValidator
{
    public function validate(array $data): array
    {
        $errors = [];

        $name = trim($data['name'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $passwordConfirm = $data['password_confirm'] ?? '';
        $role = $data['role'] ?? 'student';

        if (empty($name)) {
            $errors[] = 'Name is required.';
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Valid email is required.';
        }

        if (empty($password) || strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters.';
        }

        if ($password !== $passwordConfirm) {
            $errors[] = 'Passwords do not match.';
        }

        if (!in_array($role, ['student', 'teacher'])) {
            $errors[] = 'Invalid role selected.';
        }

        return $errors;
    }
}

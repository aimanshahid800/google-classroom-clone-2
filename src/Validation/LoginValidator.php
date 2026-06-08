<?php

namespace App\Validation;

class LoginValidator
{
    public function validate(array $data): array
    {
        $errors = [];

        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';

        if (empty($email)) {
            $errors[] = 'Email is required.';
        }

        if (empty($password)) {
            $errors[] = 'Password is required.';
        }

        return $errors;
    }
}

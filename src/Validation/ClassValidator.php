<?php

namespace App\Validation;

class ClassValidator
{
    public function validateCreate(array $data): array
    {
        $errors = [];

        $name = trim($data['name'] ?? '');

        if (empty($name)) {
            $errors[] = 'Class name is required.';
        }

        return $errors;
    }

    public function validateJoin(array $data): array
    {
        $errors = [];

        $code = strtoupper(trim($data['code'] ?? ''));

        if (empty($code)) {
            $errors[] = 'Class code is required.';
        }

        return $errors;
    }
}

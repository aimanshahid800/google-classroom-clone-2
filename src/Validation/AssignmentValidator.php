<?php

namespace App\Validation;

use DateTime;

class AssignmentValidator
{
    public function validateCreate(array $data): array
    {
        $errors = [];

        $title = trim($data['title'] ?? '');
        $dueDate = $data['due_date'] ?? '';

        if (empty($title)) {
            $errors[] = 'Assignment title is required.';
        }

        if (!empty($dueDate)) {
            $date = DateTime::createFromFormat('Y-m-d\TH:i', $dueDate);
            if (!$date) {
                $errors[] = 'Invalid date format.';
            }
        }

        return $errors;
    }

    public function validateSubmission(array $data): array
    {
        $errors = [];

        $content = trim($data['content'] ?? '');

        if (empty($content)) {
            $errors[] = 'Submission content is required.';
        }

        return $errors;
    }
}

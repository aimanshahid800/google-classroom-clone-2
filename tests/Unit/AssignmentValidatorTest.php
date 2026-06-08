<?php

namespace Tests\Unit;

use App\Validation\AssignmentValidator;
use PHPUnit\Framework\TestCase;

class AssignmentValidatorTest extends TestCase
{
    private AssignmentValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new AssignmentValidator();
    }

    // --- validateCreate ---

    public function testCreateValidDataReturnsNoErrors(): void
    {
        $errors = $this->validator->validateCreate([
            'title' => 'Homework 1',
            'due_date' => '2026-09-15T23:59',
        ]);

        $this->assertEmpty($errors);
    }

    public function testCreateValidWithoutDueDateReturnsNoErrors(): void
    {
        $errors = $this->validator->validateCreate([
            'title' => 'Homework 1',
            'due_date' => '',
        ]);

        $this->assertEmpty($errors);
    }

    public function testCreateEmptyTitleReturnsError(): void
    {
        $errors = $this->validator->validateCreate([
            'title' => '',
        ]);

        $this->assertContains('Assignment title is required.', $errors);
    }

    public function testCreateMissingTitleReturnsError(): void
    {
        $errors = $this->validator->validateCreate([]);

        $this->assertContains('Assignment title is required.', $errors);
    }

    public function testCreateWhitespaceOnlyTitleReturnsError(): void
    {
        $errors = $this->validator->validateCreate([
            'title' => '   ',
        ]);

        $this->assertContains('Assignment title is required.', $errors);
    }

    public function testCreateInvalidDateFormatReturnsError(): void
    {
        $errors = $this->validator->validateCreate([
            'title' => 'Homework 1',
            'due_date' => '2026-13-45',
        ]);

        $this->assertContains('Invalid date format.', $errors);
    }

    public function testCreateMalformedDateStringReturnsError(): void
    {
        $errors = $this->validator->validateCreate([
            'title' => 'Homework 1',
            'due_date' => 'not-a-date',
        ]);

        $this->assertContains('Invalid date format.', $errors);
    }

    public function testCreateValidDateTimeFormatAccepted(): void
    {
        $errors = $this->validator->validateCreate([
            'title' => 'Homework 1',
            'due_date' => '2026-12-31T23:59',
        ]);

        $this->assertNotContains('Invalid date format.', $errors);
    }

    // --- validateSubmission ---

    public function testSubmissionValidContentReturnsNoErrors(): void
    {
        $errors = $this->validator->validateSubmission([
            'content' => 'My submission content here.',
        ]);

        $this->assertEmpty($errors);
    }

    public function testSubmissionEmptyContentReturnsError(): void
    {
        $errors = $this->validator->validateSubmission([
            'content' => '',
        ]);

        $this->assertContains('Submission content is required.', $errors);
    }

    public function testSubmissionMissingContentReturnsError(): void
    {
        $errors = $this->validator->validateSubmission([]);

        $this->assertContains('Submission content is required.', $errors);
    }

    public function testSubmissionWhitespaceOnlyContentReturnsError(): void
    {
        $errors = $this->validator->validateSubmission([
            'content' => '   ',
        ]);

        $this->assertContains('Submission content is required.', $errors);
    }
}

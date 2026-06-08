<?php

namespace Tests\Unit;

use App\Validation\RegistrationValidator;
use PHPUnit\Framework\TestCase;

class RegistrationValidatorTest extends TestCase
{
    private RegistrationValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new RegistrationValidator();
    }

    public function testValidDataReturnsNoErrors(): void
    {
        $errors = $this->validator->validate([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'secret123',
            'password_confirm' => 'secret123',
            'role' => 'student',
        ]);

        $this->assertEmpty($errors);
    }

    public function testEmptyNameReturnsError(): void
    {
        $errors = $this->validator->validate([
            'name' => '',
            'email' => 'john@example.com',
            'password' => 'secret123',
            'password_confirm' => 'secret123',
            'role' => 'student',
        ]);

        $this->assertContains('Name is required.', $errors);
    }

    public function testMissingNameReturnsError(): void
    {
        $errors = $this->validator->validate([
            'email' => 'john@example.com',
            'password' => 'secret123',
            'password_confirm' => 'secret123',
            'role' => 'student',
        ]);

        $this->assertContains('Name is required.', $errors);
    }

    public function testWhitespaceOnlyNameReturnsError(): void
    {
        $errors = $this->validator->validate([
            'name' => '   ',
            'email' => 'john@example.com',
            'password' => 'secret123',
            'password_confirm' => 'secret123',
            'role' => 'student',
        ]);

        $this->assertContains('Name is required.', $errors);
    }

    public function testEmptyEmailReturnsError(): void
    {
        $errors = $this->validator->validate([
            'name' => 'John',
            'email' => '',
            'password' => 'secret123',
            'password_confirm' => 'secret123',
            'role' => 'student',
        ]);

        $this->assertContains('Valid email is required.', $errors);
    }

    public function testInvalidEmailReturnsError(): void
    {
        $errors = $this->validator->validate([
            'name' => 'John',
            'email' => 'not-an-email',
            'password' => 'secret123',
            'password_confirm' => 'secret123',
            'role' => 'student',
        ]);

        $this->assertContains('Valid email is required.', $errors);
    }

    public function testEmptyPasswordReturnsError(): void
    {
        $errors = $this->validator->validate([
            'name' => 'John',
            'email' => 'john@example.com',
            'password' => '',
            'password_confirm' => '',
            'role' => 'student',
        ]);

        $this->assertContains('Password must be at least 6 characters.', $errors);
    }

    public function testShortPasswordReturnsError(): void
    {
        $errors = $this->validator->validate([
            'name' => 'John',
            'email' => 'john@example.com',
            'password' => 'abc',
            'password_confirm' => 'abc',
            'role' => 'student',
        ]);

        $this->assertContains('Password must be at least 6 characters.', $errors);
    }

    public function testPasswordExactlySixCharsIsValid(): void
    {
        $errors = $this->validator->validate([
            'name' => 'John',
            'email' => 'john@example.com',
            'password' => '123456',
            'password_confirm' => '123456',
            'role' => 'student',
        ]);

        $this->assertNotContains('Password must be at least 6 characters.', $errors);
    }

    public function testPasswordMismatchReturnsError(): void
    {
        $errors = $this->validator->validate([
            'name' => 'John',
            'email' => 'john@example.com',
            'password' => 'secret123',
            'password_confirm' => 'different',
            'role' => 'student',
        ]);

        $this->assertContains('Passwords do not match.', $errors);
    }

    public function testInvalidRoleReturnsError(): void
    {
        $errors = $this->validator->validate([
            'name' => 'John',
            'email' => 'john@example.com',
            'password' => 'secret123',
            'password_confirm' => 'secret123',
            'role' => 'admin',
        ]);

        $this->assertContains('Invalid role selected.', $errors);
    }

    public function testTeacherRoleIsValid(): void
    {
        $errors = $this->validator->validate([
            'name' => 'John',
            'email' => 'john@example.com',
            'password' => 'secret123',
            'password_confirm' => 'secret123',
            'role' => 'teacher',
        ]);

        $this->assertEmpty($errors);
    }

    public function testDefaultRoleIsStudentWhenMissing(): void
    {
        $errors = $this->validator->validate([
            'name' => 'John',
            'email' => 'john@example.com',
            'password' => 'secret123',
            'password_confirm' => 'secret123',
        ]);

        $this->assertEmpty($errors);
    }

    public function testMultipleErrorsCanBeReturned(): void
    {
        $errors = $this->validator->validate([
            'name' => '',
            'email' => '',
            'password' => '',
            'password_confirm' => 'mismatch',
            'role' => 'admin',
        ]);

        $this->assertGreaterThanOrEqual(4, count($errors));
        $this->assertContains('Name is required.', $errors);
        $this->assertContains('Valid email is required.', $errors);
        $this->assertContains('Password must be at least 6 characters.', $errors);
        $this->assertContains('Invalid role selected.', $errors);
    }
}

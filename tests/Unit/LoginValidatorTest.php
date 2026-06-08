<?php

namespace Tests\Unit;

use App\Validation\LoginValidator;
use PHPUnit\Framework\TestCase;

class LoginValidatorTest extends TestCase
{
    private LoginValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new LoginValidator();
    }

    public function testValidDataReturnsNoErrors(): void
    {
        $errors = $this->validator->validate([
            'email' => 'john@example.com',
            'password' => 'secret123',
        ]);

        $this->assertEmpty($errors);
    }

    public function testEmptyEmailReturnsError(): void
    {
        $errors = $this->validator->validate([
            'email' => '',
            'password' => 'secret123',
        ]);

        $this->assertContains('Email is required.', $errors);
    }

    public function testMissingEmailReturnsError(): void
    {
        $errors = $this->validator->validate([
            'password' => 'secret123',
        ]);

        $this->assertContains('Email is required.', $errors);
    }

    public function testWhitespaceOnlyEmailReturnsError(): void
    {
        $errors = $this->validator->validate([
            'email' => '   ',
            'password' => 'secret123',
        ]);

        $this->assertContains('Email is required.', $errors);
    }

    public function testEmptyPasswordReturnsError(): void
    {
        $errors = $this->validator->validate([
            'email' => 'john@example.com',
            'password' => '',
        ]);

        $this->assertContains('Password is required.', $errors);
    }

    public function testMissingPasswordReturnsError(): void
    {
        $errors = $this->validator->validate([
            'email' => 'john@example.com',
        ]);

        $this->assertContains('Password is required.', $errors);
    }

    public function testBothFieldsEmptyReturnsTwoErrors(): void
    {
        $errors = $this->validator->validate([
            'email' => '',
            'password' => '',
        ]);

        $this->assertCount(2, $errors);
        $this->assertContains('Email is required.', $errors);
        $this->assertContains('Password is required.', $errors);
    }

    public function testEmptyArrayReturnsTwoErrors(): void
    {
        $errors = $this->validator->validate([]);

        $this->assertCount(2, $errors);
    }
}

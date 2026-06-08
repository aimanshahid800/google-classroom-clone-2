<?php

namespace Tests\Unit;

use App\Validation\ClassValidator;
use PHPUnit\Framework\TestCase;

class ClassValidatorTest extends TestCase
{
    private ClassValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new ClassValidator();
    }

    // --- validateCreate ---

    public function testCreateValidDataReturnsNoErrors(): void
    {
        $errors = $this->validator->validateCreate(['name' => 'Math 101']);

        $this->assertEmpty($errors);
    }

    public function testCreateEmptyNameReturnsError(): void
    {
        $errors = $this->validator->validateCreate(['name' => '']);

        $this->assertContains('Class name is required.', $errors);
    }

    public function testCreateMissingNameReturnsError(): void
    {
        $errors = $this->validator->validateCreate([]);

        $this->assertContains('Class name is required.', $errors);
    }

    public function testCreateWhitespaceOnlyNameReturnsError(): void
    {
        $errors = $this->validator->validateCreate(['name' => '   ']);

        $this->assertContains('Class name is required.', $errors);
    }

    // --- validateJoin ---

    public function testJoinValidCodeReturnsNoErrors(): void
    {
        $errors = $this->validator->validateJoin(['code' => 'ABC123']);

        $this->assertEmpty($errors);
    }

    public function testJoinEmptyCodeReturnsError(): void
    {
        $errors = $this->validator->validateJoin(['code' => '']);

        $this->assertContains('Class code is required.', $errors);
    }

    public function testJoinMissingCodeReturnsError(): void
    {
        $errors = $this->validator->validateJoin([]);

        $this->assertContains('Class code is required.', $errors);
    }

    public function testJoinWhitespaceOnlyCodeReturnsError(): void
    {
        $errors = $this->validator->validateJoin(['code' => '   ']);

        $this->assertContains('Class code is required.', $errors);
    }

    public function testJoinLowercaseCodeIsNormalized(): void
    {
        $errors = $this->validator->validateJoin(['code' => 'abc123']);

        $this->assertEmpty($errors);
    }
}

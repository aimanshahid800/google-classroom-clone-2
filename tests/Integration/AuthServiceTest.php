<?php

namespace Tests\Integration;

use App\Auth\AuthService;

class AuthServiceTest extends DatabaseTestCase
{
    private AuthService $authService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authService = new AuthService($this->pdo);
    }

    public function testRegisterCreatesUserAndReturnsId(): void
    {
        $userId = $this->authService->register('Alice', 'alice@example.com', 'secret123', 'student');

        $this->assertGreaterThan(0, $userId);

        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        $this->assertSame('Alice', $user['name']);
        $this->assertSame('alice@example.com', $user['email']);
        $this->assertSame('student', $user['role']);
        $this->assertTrue(password_verify('secret123', $user['password']));
    }

    public function testRegisterHashesPassword(): void
    {
        $userId = $this->authService->register('Bob', 'bob@example.com', 'mypassword', 'teacher');

        $stmt = $this->pdo->prepare('SELECT password FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        $this->assertNotSame('mypassword', $row['password']);
        $this->assertTrue(password_verify('mypassword', $row['password']));
    }

    public function testAuthenticateWithCorrectCredentials(): void
    {
        $this->authService->register('Alice', 'alice@example.com', 'secret123', 'student');

        $user = $this->authService->authenticate('alice@example.com', 'secret123');

        $this->assertNotNull($user);
        $this->assertSame('Alice', $user['name']);
        $this->assertSame('alice@example.com', $user['email']);
        $this->assertSame('student', $user['role']);
        $this->assertArrayNotHasKey('password', $user);
    }

    public function testAuthenticateWithWrongPasswordReturnsNull(): void
    {
        $this->authService->register('Alice', 'alice@example.com', 'secret123', 'student');

        $user = $this->authService->authenticate('alice@example.com', 'wrongpassword');

        $this->assertNull($user);
    }

    public function testAuthenticateWithNonExistentEmailReturnsNull(): void
    {
        $user = $this->authService->authenticate('nobody@example.com', 'secret123');

        $this->assertNull($user);
    }

    public function testEmailExistsReturnsTrueForExistingEmail(): void
    {
        $this->authService->register('Alice', 'alice@example.com', 'secret123', 'student');

        $this->assertTrue($this->authService->emailExists('alice@example.com'));
    }

    public function testEmailExistsReturnsFalseForMissingEmail(): void
    {
        $this->assertFalse($this->authService->emailExists('nobody@example.com'));
    }

    public function testCurrentUserReturnsNullWhenSessionEmpty(): void
    {
        $_SESSION = [];
        $this->assertNull(AuthService::currentUser());
    }

    public function testCurrentUserReturnsSessionUser(): void
    {
        $_SESSION['user'] = ['id' => 1, 'name' => 'Alice', 'email' => 'alice@example.com', 'role' => 'student'];

        $user = AuthService::currentUser();
        $this->assertSame('Alice', $user['name']);
    }

    public function testIsLoggedInReturnsFalseWhenSessionEmpty(): void
    {
        $_SESSION = [];
        $this->assertFalse(AuthService::isLoggedIn());
    }

    public function testIsLoggedInReturnsTrueWhenUserIdSet(): void
    {
        $_SESSION['user_id'] = 1;
        $this->assertTrue(AuthService::isLoggedIn());
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        parent::tearDown();
    }
}

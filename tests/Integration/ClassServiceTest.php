<?php

namespace Tests\Integration;

use App\Classroom\ClassService;

class ClassServiceTest extends DatabaseTestCase
{
    private ClassService $classService;
    private int $teacherId;
    private int $studentId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->classService = new ClassService($this->pdo);
        $this->teacherId = $this->createUser('Teacher', 'teacher@example.com', 'pass123', 'teacher');
        $this->studentId = $this->createUser('Student', 'student@example.com', 'pass123', 'student');
    }

    public function testGenerateClassCodeReturnsSixCharString(): void
    {
        $code = $this->classService->generateClassCode();

        $this->assertSame(6, strlen($code));
        $this->assertMatchesRegularExpression('/^[A-F0-9]{6}$/', $code);
    }

    public function testGenerateClassCodeIsUppercase(): void
    {
        $code = $this->classService->generateClassCode();

        $this->assertSame(strtoupper($code), $code);
    }

    public function testCreateClassReturnsIdAndAddsTeacherAsMember(): void
    {
        $classId = $this->classService->createClass('Math 101', 'A', 'Mathematics', 'Room 1', $this->teacherId);

        $this->assertGreaterThan(0, $classId);

        $stmt = $this->pdo->prepare('SELECT * FROM classes WHERE id = ?');
        $stmt->execute([$classId]);
        $class = $stmt->fetch(\PDO::FETCH_ASSOC);

        $this->assertSame('Math 101', $class['name']);
        $this->assertSame('A', $class['section']);
        $this->assertSame('Mathematics', $class['subject']);
        $this->assertSame('Room 1', $class['room']);
        $this->assertSame(6, strlen($class['code']));

        $this->assertTrue($this->classService->isMember($classId, $this->teacherId));
        $this->assertTrue($this->classService->isTeacher($classId, $this->teacherId));
    }

    public function testJoinClassWithValidCode(): void
    {
        $classId = $this->classService->createClass('Math 101', '', '', '', $this->teacherId);

        $class = $this->classService->getClassById($classId);
        $result = $this->classService->joinClass($class['code'], $this->studentId);

        $this->assertSame($classId, $result);
        $this->assertTrue($this->classService->isMember($classId, $this->studentId));
        $this->assertFalse($this->classService->isTeacher($classId, $this->studentId));
    }

    public function testJoinClassWithInvalidCodeReturnsNull(): void
    {
        $result = $this->classService->joinClass('ZZZZZZ', $this->studentId);

        $this->assertNull($result);
    }

    public function testIsMemberReturnsFalseForNonMember(): void
    {
        $classId = $this->classService->createClass('Math 101', '', '', '', $this->teacherId);

        $this->assertFalse($this->classService->isMember($classId, $this->studentId));
    }

    public function testIsTeacherReturnsFalseForStudent(): void
    {
        $classId = $this->classService->createClass('Math 101', '', '', '', $this->teacherId);
        $class = $this->classService->getClassById($classId);
        $this->classService->joinClass($class['code'], $this->studentId);

        $this->assertFalse($this->classService->isTeacher($classId, $this->studentId));
    }

    public function testGetClassByIdReturnsClassWithTeacherName(): void
    {
        $classId = $this->classService->createClass('Physics 201', 'B', 'Physics', 'Lab 3', $this->teacherId);

        $class = $this->classService->getClassById($classId);

        $this->assertNotNull($class);
        $this->assertSame('Physics 201', $class['name']);
        $this->assertSame('Teacher', $class['teacher_name']);
    }

    public function testGetClassByIdReturnsNullForMissingClass(): void
    {
        $class = $this->classService->getClassById(9999);

        $this->assertNull($class);
    }

    public function testGetClassByCodeReturnsCorrectClass(): void
    {
        $classId = $this->classService->createClass('Chemistry', '', '', '', $this->teacherId);
        $createdClass = $this->classService->getClassById($classId);

        $found = $this->classService->getClassByCode($createdClass['code']);

        $this->assertNotNull($found);
        $this->assertSame($classId, (int) $found['id']);
    }

    public function testGetClassByCodeReturnsNullForInvalidCode(): void
    {
        $found = $this->classService->getClassByCode('XXXXXX');

        $this->assertNull($found);
    }

    public function testGetMembersReturnsAllMembers(): void
    {
        $classId = $this->classService->createClass('English', '', '', '', $this->teacherId);
        $class = $this->classService->getClassById($classId);
        $this->classService->joinClass($class['code'], $this->studentId);

        $members = $this->classService->getMembers($classId);

        $this->assertCount(2, $members);
        $roles = array_column($members, 'role');
        $this->assertContains('teacher', $roles);
        $this->assertContains('student', $roles);
    }

    public function testGetUserClassesReturnsEnrolledClasses(): void
    {
        $this->classService->createClass('Math', '', '', '', $this->teacherId);
        $this->classService->createClass('Physics', '', '', '', $this->teacherId);

        $classes = $this->classService->getUserClasses($this->teacherId);

        $this->assertCount(2, $classes);
    }

    public function testGetUserClassesReturnsEmptyForUserWithNoClasses(): void
    {
        $classes = $this->classService->getUserClasses($this->studentId);

        $this->assertEmpty($classes);
    }
}

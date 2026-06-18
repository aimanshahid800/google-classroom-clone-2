<?php

namespace Tests\Integration;

use App\Assignment\AssignmentService;
use App\Classroom\ClassService;

class AssignmentServiceTest extends DatabaseTestCase
{
    private AssignmentService $assignmentService;
    private ClassService $classService;
    private int $teacherId;
    private int $studentId;
    private int $classId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assignmentService = new AssignmentService($this->pdo);
        $this->classService = new ClassService($this->pdo);
        $this->teacherId = $this->createUser('Teacher', 'teacher@example.com', 'pass123', 'teacher');
        $this->studentId = $this->createUser('Student', 'student@example.com', 'pass123', 'student');
        $this->classId = $this->classService->createClass('Test Class', '', '', '', $this->teacherId);

        $class = $this->classService->getClassById($this->classId);
        $this->classService->joinClass($class['code'], $this->studentId);
    }

    public function testCreateAssignmentReturnsId(): void
    {
        $id = $this->assignmentService->createAssignment($this->classId, 'Homework 1', 'Algebra', '2026-12-01 23:59:00');

        $this->assertGreaterThan(0, $id);
    }

    public function testCreateAssignmentWithNullDueDate(): void
    {
        $id = $this->assignmentService->createAssignment($this->classId, 'Homework 2', 'Calculus', null);

        $this->assertGreaterThan(0, $id);

        $assignment = $this->assignmentService->getAssignment($id, $this->classId);
        $this->assertNull($assignment['due_date']);
    }

    public function testGetAssignmentReturnsCorrectData(): void
    {
        $id = $this->assignmentService->createAssignment($this->classId, 'Homework 1', 'Algebra', '2026-12-01 23:59:00');

        $assignment = $this->assignmentService->getAssignment($id, $this->classId);

        $this->assertNotNull($assignment);
        $this->assertSame('Homework 1', $assignment['title']);
        $this->assertSame('Algebra', $assignment['topic']);
        $this->assertSame('Test Class', $assignment['class_name']);
    }

    public function testGetAssignmentReturnsNullForWrongClass(): void
    {
        $id = $this->assignmentService->createAssignment($this->classId, 'Homework 1', 'Algebra', null);

        $assignment = $this->assignmentService->getAssignment($id, 9999);

        $this->assertNull($assignment);
    }

    public function testGetAssignmentReturnsNullForMissingId(): void
    {
        $assignment = $this->assignmentService->getAssignment(9999, $this->classId);

        $this->assertNull($assignment);
    }

    public function testGetClassAssignmentsReturnsAll(): void
    {
        $this->assignmentService->createAssignment($this->classId, 'HW1', '', null);
        $this->assignmentService->createAssignment($this->classId, 'HW2', '', null);
        $this->assignmentService->createAssignment($this->classId, 'HW3', '', null);

        $assignments = $this->assignmentService->getClassAssignments($this->classId);

        $this->assertCount(3, $assignments);
    }

    public function testGetClassAssignmentsReturnsEmptyForClassWithNone(): void
    {
        $assignments = $this->assignmentService->getClassAssignments($this->classId);

        $this->assertEmpty($assignments);
    }

    public function testSubmitWorkCreatesNewSubmission(): void
    {
        $assignmentId = $this->assignmentService->createAssignment($this->classId, 'HW1', '', null);

        $subId = $this->assignmentService->submitWork($assignmentId, $this->studentId, 'My answer');

        $this->assertGreaterThan(0, $subId);

        $submission = $this->assignmentService->getSubmission($assignmentId, $this->studentId);
        $this->assertNotNull($submission);
        $this->assertSame('My answer', $submission['content']);
        $this->assertSame('handed_in', $submission['status']);
    }

    public function testSubmitWorkUpdatesExistingSubmission(): void
    {
        $assignmentId = $this->assignmentService->createAssignment($this->classId, 'HW1', '', null);

        $subId1 = $this->assignmentService->submitWork($assignmentId, $this->studentId, 'First draft');
        $subId2 = $this->assignmentService->submitWork($assignmentId, $this->studentId, 'Revised answer');

        $this->assertSame($subId1, $subId2);

        $submission = $this->assignmentService->getSubmission($assignmentId, $this->studentId);
        $this->assertSame('Revised answer', $submission['content']);
        $this->assertSame('handed_in', $submission['status']);
    }

    public function testGetSubmissionReturnsNullWhenNoneExists(): void
    {
        $assignmentId = $this->assignmentService->createAssignment($this->classId, 'HW1', '', null);

        $submission = $this->assignmentService->getSubmission($assignmentId, $this->studentId);

        $this->assertNull($submission);
    }

    public function testGetAssignmentSubmissionsReturnsAll(): void
    {
        $assignmentId = $this->assignmentService->createAssignment($this->classId, 'HW1', '', null);
        $student2Id = $this->createUser('Student2', 'student2@example.com', 'pass123', 'student');

        $this->assignmentService->submitWork($assignmentId, $this->studentId, 'Answer 1');
        $this->assignmentService->submitWork($assignmentId, $student2Id, 'Answer 2');

        $submissions = $this->assignmentService->getAssignmentSubmissions($assignmentId);

        $this->assertCount(2, $submissions);
    }

    public function testGetAssignmentSubmissionsIncludesStudentInfo(): void
    {
        $assignmentId = $this->assignmentService->createAssignment($this->classId, 'HW1', '', null);
        $this->assignmentService->submitWork($assignmentId, $this->studentId, 'Answer');

        $submissions = $this->assignmentService->getAssignmentSubmissions($assignmentId);

        $this->assertCount(1, $submissions);
        $this->assertSame('Student', $submissions[0]['name']);
        $this->assertSame('student@example.com', $submissions[0]['email']);
    }

    public function testMarkSubmissionDoneChangesStatus(): void
    {
        $assignmentId = $this->assignmentService->createAssignment($this->classId, 'HW1', '', null);
        $subId = $this->assignmentService->submitWork($assignmentId, $this->studentId, 'Answer');

        $result = $this->assignmentService->markSubmissionDone($subId);

        $this->assertTrue($result);

        $submission = $this->assignmentService->getSubmission($assignmentId, $this->studentId);
        $this->assertSame('done', $submission['status']);
    }

    public function testResubmitAfterMarkDoneResetsToHandedIn(): void
    {
        $assignmentId = $this->assignmentService->createAssignment($this->classId, 'HW1', '', null);
        $subId = $this->assignmentService->submitWork($assignmentId, $this->studentId, 'First');
        $this->assignmentService->markSubmissionDone($subId);

        $this->assignmentService->submitWork($assignmentId, $this->studentId, 'Updated');

        $submission = $this->assignmentService->getSubmission($assignmentId, $this->studentId);
        $this->assertSame('Updated', $submission['content']);
        $this->assertSame('handed_in', $submission['status']);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Administrator;
use App\Models\Course;
use App\Models\Student;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use PDOException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StudentTest extends TestCase
{
    use DatabaseTransactions;

    private Administrator $owner;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('uploads');

        $this->owner = $this->getOwner();
        $this->actingAs($this->owner);
    }

    private function failQuery(string $prefix, string $message): Closure
    {
        $enabled = true;
        DB::connection()->beforeExecuting(function (
            $query,
            $bindings,
            $connection
        ) use (
            &$enabled,
            $prefix,
            $message
        ): void {
            $normalized = strtolower(str_replace(['`', '"'], '', $query));
            if ($enabled && str_starts_with($normalized, $prefix)) {
                throw new QueryException($connection->getName(), $query, $bindings, new PDOException($message));
            }
        });

        return function () use (&$enabled): void {
            $enabled = false;
        };
    }

    private function getOwner(): Administrator
    {
        return Administrator::where('Role', 'owner')->first() ?? Administrator::create([
            'Email' => 'owner@example.com',
            'Name' => 'Test Owner',
            'Role' => 'owner',
            'Phone' => '0500000000',
            'Password' => Hash::make('password123'),
            'Image' => '/upload/test.jpg',
        ]);
    }

    private function createStudent(array $attributes = []): Student
    {
        return Student::create(array_merge([
            'Email' => uniqid() . '@example.com',
            'Name' => 'Test Student',
            'Phone' => '0501234567',
            'Image' => '/upload/test.jpg',
        ], $attributes));
    }

    private function createCourse(array $attributes = []): Course
    {
        return Course::create(array_merge([
            'Name' => 'Test Course',
            'Description' => 'Test description',
            'Image' => '/upload/test.jpg',
        ], $attributes));
    }

    public function test_authenticated_administrator_can_get_all_students(): void
    {
        $this->createStudent();
        $this->createStudent();
        $this->createStudent();

        $response = $this
            ->getJson('/api/student');

        $response
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => ['*' => [
                    'id',
                    'email',
                    'name',
                    'phone',
                    'image',
                ]],
            ]);
    }

    public function test_authenticated_administrator_can_get_student_by_id(): void
    {
        $student = $this->createStudent();

        $response = $this
            ->getJson("/api/student/{$student->Student_ID}");

        $response
            ->assertOk()
            ->assertJsonPath('id', $student->Student_ID)
            ->assertJsonPath('email', $student->Email)
            ->assertJsonPath('name', $student->Name);
    }

    public function test_get_non_existing_student_returns_404(): void
    {
        $response = $this
            ->getJson('/api/student/999999');

        $response
            ->assertNotFound()
            ->assertJson([
                'message' => 'Student not found',
            ]);
    }

    public function test_enrollment_failure_rolls_back_student_and_cleans_up_image(): void
    {
        $this->withoutExceptionHandling();
        $course = $this->createCourse();
        $restoreQueries = $this->failQuery('insert into school', 'Enrollment failed');

        try {
            $this->postJson('/api/student', [
                'email' => 'student@example.com',
                'name' => 'Test Student',
                'phone' => '0501234567',
                'image' => UploadedFile::fake()->image('student.jpg'),
                'courses' => [$course->Course_ID],
            ]);
            $this->fail('Expected the enrollment exception to be rethrown.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString('Enrollment failed', $exception->getMessage());
        } finally {
            $restoreQueries();
        }

        $this->assertDatabaseMissing('students', ['Email' => 'student@example.com']);
        $this->assertDatabaseCount('school', 0);
        $this->assertSame([], Storage::disk('uploads')->allFiles());
        $this->assertDatabaseHas('courses', ['Course_ID' => $course->Course_ID]);
    }

    public function test_authenticated_administrator_can_create_student(): void
    {
        $image = UploadedFile::fake()->image('student.jpg');

        $response = $this
            ->post('/api/student', [
                'email' => 'student@example.com',
                'name' => 'Test Student',
                'phone' => '0501234567',
                'image' => $image,
            ]);

        $response->assertCreated();

        $this->assertDatabaseHas('students', [
            'Email' => 'student@example.com',
            'Name' => 'Test Student',
            'Phone' => '0501234567',
        ]);

        $student = Student::where('Email', 'student@example.com')->firstOrFail();

        $this->assertNotEmpty($student->Image);

        $this->assertTrue(
            Storage::disk('uploads')->exists(
                basename($student->Image)
            )
        );
    }

    #[DataProvider('emailLengthProvider')]
    public function test_student_email_length_on_creation(int $length, bool $valid): void
    {
        $email = str_repeat('a', $length - strlen('@example.com')) . '@example.com';

        $response = $this->postJson('/api/student', [
            'email' => $email,
            'name' => 'Test Student',
            'phone' => '0501234567',
            'image' => UploadedFile::fake()->image('student.jpg'),
        ]);

        if ($valid) {
            $response->assertCreated();
            $this->assertDatabaseHas('students', ['Email' => $email]);
        } else {
            $response->assertUnprocessable()->assertJsonValidationErrors('email');
            $this->assertDatabaseCount('students', 0);
            $this->assertSame([], Storage::disk('uploads')->allFiles());
        }
    }

    #[DataProvider('emailLengthProvider')]
    public function test_student_email_length_on_update(int $length, bool $valid): void
    {
        $student = $this->createStudent();
        $original = $student->refresh()->getAttributes();
        $email = str_repeat('a', $length - strlen('@example.com')) . '@example.com';

        $response = $this->putJson("/api/student/{$student->Student_ID}", [
            'email' => $email,
            'name' => 'Updated Student',
            'phone' => $student->Phone,
        ]);

        if ($valid) {
            $response->assertOk();
            $this->assertSame($email, $student->refresh()->Email);
        } else {
            $response->assertUnprocessable()->assertJsonValidationErrors('email');
            $this->assertSame($original, $student->refresh()->getAttributes());
        }
    }

    public static function emailLengthProvider(): array
    {
        return [
            'maximum length' => [64, true],
            'over maximum length' => [65, false],
        ];
    }

    public function test_student_can_be_created_with_courses(): void
    {
        $courses = collect([
            $this->createCourse(['Name' => 'Course A']),
            $this->createCourse(['Name' => 'Course B']),
        ]);

        $response = $this
            ->post('/api/student', [
                'email' => 'student@example.com',
                'name' => 'Test Student',
                'phone' => '0501234567',
                'image' => UploadedFile::fake()->image('student.jpg'),
                'courses' => $courses->pluck('Course_ID')->all(),
            ]);

        $response->assertCreated();

        $student = Student::where('Email', 'student@example.com')->firstOrFail();

        $this->assertCount(2, $student->courses);

        $this->assertEqualsCanonicalizing(
            $courses->pluck('Course_ID')->all(),
            $student->courses->pluck('Course_ID')->all()
        );
    }

    public function test_email_must_be_unique_when_creating_student(): void
    {
        $this->createStudent([
            'Email' => 'student@example.com',
        ]);

        $response = $this
            ->withHeader('Accept', 'application/json')
            ->post('/api/student', [
                'email' => 'student@example.com',
                'name' => 'Another Student',
                'phone' => '0501234567',
                'image' => UploadedFile::fake()->image('student.jpg'),
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_authenticated_administrator_can_update_student(): void
    {
        $student = $this->createStudent();

        $response = $this
            ->putJson("/api/student/{$student->Student_ID}", [
                'email' => 'updated@example.com',
                'name' => 'Updated Student',
                'phone' => '0509999999',
            ]);

        $response->assertSuccessful();

        $this->assertDatabaseHas('students', [
            'Student_ID' => $student->Student_ID,
            'Email' => 'updated@example.com',
            'Name' => 'Updated Student',
            'Phone' => '0509999999',
        ]);
    }

    public function test_update_enrollment_failure_preserves_student_courses_and_image(): void
    {
        $this->withoutExceptionHandling();
        $student = $this->createStudent();
        $original = $student->refresh()->getAttributes();
        $oldCourse = $this->createCourse();
        $newCourse = $this->createCourse();
        $student->courses()->attach($oldCourse->Course_ID);
        Storage::disk('uploads')->put('test.jpg', 'original image');
        $restoreQueries = $this->failQuery('insert into school', 'Enrollment failed');

        try {
            $this->putJson("/api/student/{$student->Student_ID}", [
                'email' => 'updated@example.com',
                'name' => 'Updated Student',
                'phone' => '0509999999',
                'image' => UploadedFile::fake()->image('replacement.jpg'),
                'courses' => [$newCourse->Course_ID],
            ]);
            $this->fail('Expected the enrollment exception to be rethrown.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString('Enrollment failed', $exception->getMessage());
        } finally {
            $restoreQueries();
        }

        $this->assertSame($original, $student->refresh()->getAttributes());
        $this->assertSame([$oldCourse->Course_ID], $student->courses()->pluck('courses.Course_ID')->all());
        $this->assertSame(['test.jpg'], Storage::disk('uploads')->allFiles());
        $this->assertSame('original image', Storage::disk('uploads')->get('test.jpg'));
    }

    public function test_student_courses_can_be_updated(): void
    {
        $student = $this->createStudent();

        $oldCourses = collect([
            $this->createCourse(['Name' => 'Old Course A']),
            $this->createCourse(['Name' => 'Old Course B']),
        ]);

        $newCourses = collect([
            $this->createCourse(['Name' => 'New Course A']),
            $this->createCourse(['Name' => 'New Course B']),
        ]);

        $student->courses()->attach(
            $oldCourses->pluck('Course_ID')->all()
        );

        $response = $this
            ->putJson("/api/student/{$student->Student_ID}", [
                'name' => $student->Name,
                'phone' => $student->Phone,
                'email' => $student->Email,
                'courses' => $newCourses->pluck('Course_ID')->all(),
            ]);

        $response->assertSuccessful();

        $student->refresh();

        $this->assertEqualsCanonicalizing(
            $newCourses->pluck('Course_ID')->all(),
            $student->courses->pluck('Course_ID')->all()
        );
    }

    public function test_authenticated_administrator_can_delete_student(): void
    {
        Storage::disk('uploads')->put(
            'student.jpg',
            'student image'
        );

        $student = $this->createStudent([
            'Image' => '/upload/student.jpg',
        ]);

        $response = $this
            ->deleteJson("/api/student/{$student->Student_ID}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('students', [
            'Student_ID' => $student->Student_ID,
        ]);

        $this->assertFalse(
            Storage::disk('uploads')->exists('student.jpg')
        );
    }

    public function test_deletion_failure_preserves_student_enrollments_and_image(): void
    {
        $this->withoutExceptionHandling();
        $student = $this->createStudent();
        $course = $this->createCourse();
        $student->courses()->attach($course->Course_ID);
        Storage::disk('uploads')->put('test.jpg', 'original image');
        $restoreQueries = $this->failQuery('delete from students', 'Student deletion failed');

        try {
            $this->deleteJson("/api/student/{$student->Student_ID}");
            $this->fail('Expected the deletion exception to be rethrown.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString('Student deletion failed', $exception->getMessage());
        } finally {
            $restoreQueries();
        }

        $this->assertDatabaseHas('students', ['Student_ID' => $student->Student_ID]);
        $this->assertDatabaseHas('school', [
            'Student_ID' => $student->Student_ID,
            'Course_ID' => $course->Course_ID,
        ]);
        $this->assertSame('original image', Storage::disk('uploads')->get('test.jpg'));
    }

    public function test_student_image_can_be_updated(): void
    {
        Storage::disk('uploads')->put(
            'old.jpg',
            'old image'
        );

        $student = $this->createStudent([
            'Image' => '/upload/old.jpg',
        ]);

        $response = $this->post(
            "/api/student/{$student->Student_ID}",
            [
                '_method' => 'PUT',
                'email' => $student->Email,
                'name' => $student->Name,
                'phone' => $student->Phone,
                'image' => UploadedFile::fake()->image('new.jpg'),
            ]
        );

        $response->assertSuccessful();

        $student->refresh();

        $this->assertTrue(
            Storage::disk('uploads')->exists(
                basename($student->Image)
            )
        );

        $this->assertFalse(
            Storage::disk('uploads')->exists('old.jpg')
        );
    }

    #[DataProvider('requiredStudentFieldsProvider')]
    public function test_required_fields_are_validated_when_creating_student(
        string $field
    ): void {
        $data = [
            'email' => 'student@example.com',
            'name' => 'Test Student',
            'phone' => '0501234567',
            'image' => UploadedFile::fake()->image('student.jpg'),
        ];

        unset($data[$field]);

        $this->withHeader('Accept', 'application/json')
            ->post('/api/student', $data)
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);
    }

    public static function requiredStudentFieldsProvider(): array
    {
        return [
            ['email'],
            ['name'],
            ['phone'],
            ['image'],
        ];
    }

    #[DataProvider('requiredStudentUpdateFieldsProvider')]
    public function test_required_fields_are_validated_when_updating_student(
        string $field
    ): void {
        $student = $this->createStudent();

        $data = [
            'email' => $student->Email,
            'name' => $student->Name,
            'phone' => $student->Phone,
        ];

        unset($data[$field]);

        $this->putJson(
            "/api/student/{$student->Student_ID}",
            $data
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);
    }

    public static function requiredStudentUpdateFieldsProvider(): array
    {
        return [
            ['email'],
            ['name'],
            ['phone'],
        ];
    }

    public function test_updating_student_without_courses_preserves_existing_courses(): void
    {
        $student = $this->createStudent();

        $courses = collect([
            $this->createCourse(['Name' => 'Course A']),
            $this->createCourse(['Name' => 'Course B']),
        ]);

        $student->courses()->attach(
            $courses->pluck('Course_ID')->all()
        );

        $this->putJson(
            "/api/student/{$student->Student_ID}",
            [
                'email' => $student->Email,
                'name' => 'Updated Student',
                'phone' => $student->Phone,
            ]
        )->assertOk();

        $student->refresh();

        $this->assertEqualsCanonicalizing(
            $courses->pluck('Course_ID')->all(),
            $student->courses->pluck('Course_ID')->all()
        );
    }

    public function test_updating_student_with_empty_courses_removes_all_courses(): void
    {
        $student = $this->createStudent();

        $courses = collect([
            $this->createCourse(['Name' => 'Course A']),
            $this->createCourse(['Name' => 'Course B']),
        ]);

        $student->courses()->attach(
            $courses->pluck('Course_ID')->all()
        );

        $this->putJson(
            "/api/student/{$student->Student_ID}",
            [
                'email' => $student->Email,
                'name' => $student->Name,
                'phone' => $student->Phone,
                'courses' => [],
            ]
        )->assertOk();

        $student->refresh();

        $this->assertCount(0, $student->courses);
    }

    public function test_updating_student_without_image_preserves_image(): void
    {
        Storage::disk('uploads')->put('student.jpg', 'student image');

        $student = $this->createStudent([
            'Image' => '/upload/student.jpg',
        ]);

        $this->putJson(
            "/api/student/{$student->Student_ID}",
            [
                'email' => $student->Email,
                'name' => 'Updated Student',
                'phone' => $student->Phone,
            ]
        )->assertOk();

        $student->refresh();

        $this->assertSame('/upload/student.jpg', $student->Image);

        $this->assertTrue(
            Storage::disk('uploads')->exists('student.jpg')
        );
    }

    public function test_student_image_can_be_updated_when_old_image_is_missing(): void
    {
        $student = $this->createStudent([
            'Image' => '/upload/missing.jpg',
        ]);

        $this->assertFalse(
            Storage::disk('uploads')->exists('missing.jpg')
        );

        $this->post(
            "/api/student/{$student->Student_ID}",
            [
                '_method' => 'PUT',
                'email' => $student->Email,
                'name' => $student->Name,
                'phone' => $student->Phone,
                'image' => UploadedFile::fake()->image('new.jpg'),
            ]
        )->assertOk();

        $student->refresh();

        $this->assertNotSame('/upload/missing.jpg', $student->Image);

        $this->assertTrue(
            Storage::disk('uploads')->exists(
                basename($student->Image)
            )
        );
    }

    public function test_unsubscribe_student_from_course(): void
    {
        $student = $this->createStudent();
        $course = $this->createCourse();

        $student->courses()->attach($course->Course_ID);

        $this->assertTrue(
            $student->courses()
                ->where('courses.Course_ID', $course->Course_ID)
                ->exists()
        );

        $this->deleteJson(
            "/api/student/{$student->Student_ID}/course/{$course->Course_ID}"
        )->assertNoContent();

        $this->assertFalse(
            $student->courses()
                ->where('courses.Course_ID', $course->Course_ID)
                ->exists()
        );

        $this->assertDatabaseHas('students', [
            'Student_ID' => $student->Student_ID,
        ]);

        $this->assertDatabaseHas('courses', [
            'Course_ID' => $course->Course_ID,
        ]);
    }

    public function test_unsubscribe_returns_404_when_student_not_found(): void
    {
        $course = $this->createCourse();

        $response = $this->delete(
            "/api/student/999999/course/{$course->Course_ID}"
        );

        $response->assertNotFound();
    }

    public function test_unsubscribe_returns_204_when_student_is_not_subscribed(): void
    {
        $student = $this->createStudent();
        $course = $this->createCourse();

        $this->assertFalse(
            $student->courses()
                ->where('courses.Course_ID', $course->Course_ID)
                ->exists()
        );

        $this->deleteJson(
            "/api/student/{$student->Student_ID}/course/{$course->Course_ID}"
        )->assertNoContent();

        $this->assertFalse(
            $student->courses()
                ->where('courses.Course_ID', $course->Course_ID)
                ->exists()
        );
    }

    public function test_unsubscribe_returns_404_when_course_not_found(): void
    {
        $student = $this->createStudent();

        $response = $this->delete(
            "/api/student/{$student->Student_ID}/course/999999"
        );

        $response->assertNotFound();
    }
}

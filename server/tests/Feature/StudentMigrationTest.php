<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StudentMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_and_enrollment_migrations_can_be_rolled_back_and_reapplied(): void
    {
        $students = require database_path('migrations/2026_09_01_165533_create_students_table.php');
        $enrollments = require database_path('migrations/2026_09_01_165622_create_school_table.php');

        $enrollments->down();
        $students->down();

        $this->assertFalse(Schema::hasTable('school'));
        $this->assertFalse(Schema::hasTable('students'));

        $students->up();
        $enrollments->up();

        $this->assertTrue(Schema::hasTable('students'));
        $this->assertTrue(Schema::hasTable('school'));
    }
}

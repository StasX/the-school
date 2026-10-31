<?php

namespace Tests\Feature;

use App\Models\Administrator;
use App\Models\Course;
use App\Models\Student;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PaginationTest extends TestCase
{
    use DatabaseTransactions;

    public static function lists(): array
    {
        return [
            'students' => ['student'],
            'courses' => ['course'],
            'administrators' => ['administrator'],
        ];
    }

    #[DataProvider('lists')]
    public function test_lists_have_ordered_pages_and_totals(string $endpoint): void
    {
        $owner = Administrator::create([
            'Email' => 'pagination-owner@example.com',
            'Name' => 'Owner',
            'Role' => 'owner',
            'Phone' => '0501234567',
            'Password' => 'unused',
            'Image' => '/upload/test.jpg',
        ]);
        $this->actingAs($owner);

        for ($index = 0; $index < 22; $index++) {
            $attributes = [
                'Name' => "Record $index",
                'Image' => '/upload/test.jpg',
            ];
            if ($endpoint === 'course') {
                Course::create($attributes + ['Description' => 'Test course']);
            } elseif ($endpoint === 'student') {
                Student::create($attributes + [
                    'Email' => "student-$index@example.com",
                    'Phone' => '0501234567',
                ]);
            } else {
                Administrator::create($attributes + [
                    'Email' => "admin-$index@example.com",
                    'Phone' => '0501234567',
                    'Role' => 'manager',
                    'Password' => 'unused',
                ]);
            }
        }

        $total = $endpoint === 'administrator' ? 23 : 22;
        $first = $this->getJson("/api/$endpoint")
            ->assertOk()
            ->assertJsonCount(20, 'data')
            ->assertJsonPath('meta.total', $total)
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.last_page', 2);
        $second = $this->getJson("/api/$endpoint?page=2")
            ->assertOk()
            ->assertJsonCount($total - 20, 'data')
            ->assertJsonPath('meta.current_page', 2);

        $firstIds = array_column($first->json('data'), 'id');
        $secondIds = array_column($second->json('data'), 'id');
        $this->assertGreaterThan(max($firstIds), min($secondIds));
        $this->getJson("/api/$endpoint?page=3")
            ->assertOk()
            ->assertJsonCount(0, 'data');
        $this->getJson("/api/$endpoint?page=0")->assertUnprocessable();
        $this->getJson("/api/$endpoint?page=abc")->assertUnprocessable();

        if ($endpoint !== 'administrator') {
            $relation = $endpoint === 'student' ? 'courses' : 'students';
            $first->assertJsonMissingPath("data.0.$relation");
        } else {
            $manager = Administrator::where('Role', 'manager')->firstOrFail();
            $this->actingAs($manager)->getJson('/api/administrator')
                ->assertOk()
                ->assertJsonPath('meta.total', 22)
                ->assertJsonMissing(['role' => 'owner']);
        }
    }
}

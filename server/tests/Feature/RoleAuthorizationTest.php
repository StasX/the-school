<?php

namespace Tests\Feature;

use App\Models\Administrator;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    use DatabaseTransactions;

    private function createAdministrator(string $role): Administrator
    {
        return Administrator::create([
            'Email' => "{$role}@example.com",
            'Name' => ucfirst($role),
            'Role' => $role,
            'Phone' => '0500000000',
            'Password' => Hash::make('password123'),
            'Image' => '/upload/test.jpg',
        ]);
    }

    public function test_owner_can_access_administrator_management(): void
    {
        $owner = $this->createAdministrator('owner');

        $this->actingAs($owner)
            ->getJson('/api/administrator')
            ->assertOk();
    }

    public function test_manager_can_access_administrator_management(): void
    {
        $manager = $this->createAdministrator('manager');

        $this->actingAs($manager)
            ->getJson('/api/administrator')
            ->assertOk();
    }

    public function test_sales_cannot_access_administrator_management(): void
    {
        $sales = $this->createAdministrator('sales');

        $this->actingAs($sales)
            ->getJson('/api/administrator')
            ->assertForbidden()
            ->assertJson([
                'error' => 'Forbidden',
            ]);
    }

    public function test_sales_cannot_create_courses(): void
    {
        $sales = $this->createAdministrator('sales');

        $this->actingAs($sales)
            ->postJson('/api/course', [])
            ->assertForbidden();
    }

    public function test_sales_cannot_update_courses(): void
    {
        $sales = $this->createAdministrator('sales');

        $this->actingAs($sales)
            ->putJson('/api/course/1', [])
            ->assertForbidden();
    }

    public function test_sales_cannot_delete_courses(): void
    {
        $sales = $this->createAdministrator('sales');

        $this->actingAs($sales)
            ->deleteJson('/api/course/1')
            ->assertForbidden();
    }

    public function test_unknown_role_cannot_access_students(): void
    {
        $administrator = $this->createAdministrator('unknown');

        $this->actingAs($administrator)
            ->getJson('/api/student')
            ->assertForbidden()
            ->assertJson([
                'error' => 'Forbidden',
            ]);
    }

    public function test_sales_can_access_students(): void
    {
        $sales = $this->createAdministrator('sales');

        $this->actingAs($sales)
            ->getJson('/api/student')
            ->assertOk();
    }

    public function test_sales_can_view_courses(): void
    {
        $sales = $this->createAdministrator('sales');

        $this->actingAs($sales)
            ->getJson('/api/course')
            ->assertOk();
    }
}

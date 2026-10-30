<?php

namespace Tests\Feature;

use App\Models\Administrator;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private function createAdministrator(array $attributes = []): Administrator
    {
        return Administrator::create(array_merge([
            'Email' => 'owner@example.com',
            'Name' => 'Test Owner',
            'Role' => 'owner',
            'Phone' => '0500000000',
            'Password' => Hash::make('password123'),
            'Image' => '/upload/test.jpg',
        ], $attributes));
    }

    public function test_administrator_can_login_with_valid_credentials(): void
    {
        $administrator = $this->createAdministrator();

        $response = $this->postJson('/api/login', [
            'email' => 'owner@example.com',
            'password' => 'password123',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath(
                'administrator.id',
                $administrator->Administrator_ID
            )
            ->assertJsonPath(
                'administrator.email',
                $administrator->Email
            )
            ->assertJsonPath(
                'administrator.role',
                'owner'
            )
            ->assertJsonStructure([
                'administrator' => [
                    'id',
                    'email',
                    'name',
                    'role',
                    'image',
                ],
                'token',
            ]);

        $this->assertAuthenticatedAs($administrator);
    }

    public function test_login_fails_with_incorrect_password(): void
    {
        $this->createAdministrator();

        $this->postJson('/api/login', [
            'email' => 'owner@example.com',
            'password' => 'incorrect-password',
        ])
            ->assertUnauthorized()
            ->assertJson([
                'message' => 'Invalid username or password.',
            ]);

        $this->assertGuest();
    }

    public function test_login_fails_with_unknown_email(): void
    {
        $this->postJson('/api/login', [
            'email' => 'unknown@example.com',
            'password' => 'password123',
        ])
            ->assertUnauthorized()
            ->assertJson([
                'message' => 'Invalid username or password.',
            ]);

        $this->assertGuest();
    }

    public function test_login_fails_when_credentials_are_missing(): void
    {
        $this->postJson('/api/login', [])
            ->assertUnauthorized()
            ->assertJson([
                'message' => 'Invalid username or password.',
            ]);

        $this->assertGuest();
    }

    public function test_authenticated_administrator_can_get_own_data(): void
    {
        $administrator = $this->createAdministrator();

        $this->actingAs($administrator)
            ->getJson('/api/auth')
            ->assertOk()
            ->assertJson([
                'id' => $administrator->Administrator_ID,
                'email' => $administrator->Email,
                'name' => $administrator->Name,
                'role' => $administrator->Role,
                'image' => $administrator->Image,
            ])
            ->assertJsonMissingPath('password')
            ->assertJsonMissingPath('Password');
    }

    public function test_guest_cannot_get_authenticated_user_data(): void
    {
        $this->getJson('/api/auth')
            ->assertUnauthorized();
    }

    public function test_authenticated_administrator_can_logout(): void
    {
        $administrator = $this->createAdministrator();

        $this->actingAs($administrator)
            ->postJson('/api/logout')
            ->assertOk()
            ->assertJson([
                'message' => 'Logout successful',
            ]);

        $this->assertGuest();
    }

    public function test_guest_cannot_access_protected_endpoints(): void
    {
        $this->getJson('/api/student')
            ->assertUnauthorized();

        $this->getJson('/api/course')
            ->assertUnauthorized();

        $this->getJson('/api/administrator')
            ->assertUnauthorized();
    }

    public function test_login_rejects_missing_email(): void
    {
        $this->postJson('/api/login', [
            'password' => 'password123',
        ])
            ->assertUnauthorized()
            ->assertJson([
                'message' => 'Invalid username or password.',
            ]);
        $this->assertGuest();
    }

    public function test_login_rejects_invalid_email(): void
    {
        $this->postJson('/api/login', [
            'email' => 'not-an-email',
            'password' => 'password123',
        ])
            ->assertUnauthorized()
            ->assertJson([
                'message' => 'Invalid username or password.',
            ]);
        $this->assertGuest();
    }

    public function test_login_rejects_missing_password(): void
    {
        $this->postJson('/api/login', [
            'email' => 'owner@example.com',
        ])
            ->assertUnauthorized()
            ->assertJson([
                'message' => 'Invalid username or password.',
            ]);
        $this->assertGuest();
    }

    public function test_login_regenerates_session(): void
    {
        $this->createAdministrator();

        $oldSessionId = session()->getId();

        $this->postJson('/api/login', [
            'email' => 'owner@example.com',
            'password' => 'password123',
        ])->assertOk();

        $this->assertNotSame($oldSessionId, session()->getId());
    }

    public function test_login_rejects_non_string_email(): void
    {
        $this->postJson('/api/login', [
            'email' => ['owner@example.com'],
            'password' => 'password123',
        ])
            ->assertUnauthorized()
            ->assertJson([
                'message' => 'Invalid username or password.',
            ]);
        $this->assertGuest();
    }

    public function test_login_rejects_empty_password(): void
    {
        $this->postJson('/api/login', [
            'email' => 'owner@example.com',
            'password' => '',
        ])
            ->assertUnauthorized()
            ->assertJson([
                'message' => 'Invalid username or password.',
            ]);
        $this->assertGuest();
    }

    public function test_login_rejects_non_string_password(): void
    {
        $this->postJson('/api/login', [
            'email' => 'owner@example.com',
            'password' => ['password123'],
        ])
            ->assertUnauthorized()
            ->assertJson([
                'message' => 'Invalid username or password.',
            ]);
        $this->assertGuest();
    }

    public function test_login_accepts_password_at_minimum_length(): void
    {
        $administrator = $this->createAdministrator([
            'Password' => Hash::make('12345678'),
        ]);

        $this->postJson('/api/login', [
            'email' => 'owner@example.com',
            'password' => '12345678',
        ])
            ->assertOk();

        $this->assertAuthenticatedAs($administrator);
    }

    public function test_administrator_email_must_be_unique(): void
    {
        $this->createAdministrator();

        $this->expectException(QueryException::class);

        $this->createAdministrator([
            'Email' => 'owner@example.com',
            'Name' => 'Duplicate Owner',
        ]);
    }

    public function test_login_rejects_password_longer_than_maximum_length(): void
    {
        $this->postJson('/api/login', [
            'email' => 'owner@example.com',
            'password' => str_repeat('a', 33),
        ])
            ->assertUnauthorized()
            ->assertJson([
                'message' => 'Invalid username or password.',
            ]);

        $this->assertGuest();
    }
}

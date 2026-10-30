<?php

namespace Tests\Feature;

use App\Models\Administrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdministratorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('uploads');
    }

    private function createAdministrator(
        array $attributes = []
    ): Administrator {
        return Administrator::create(array_merge([
            'Email' => 'administrator@example.com',
            'Name' => 'Test Administrator',
            'Role' => 'manager',
            'Phone' => '0500000000',
            'Password' => Hash::make('password123'),
            'Image' => '/upload/test.jpg',
        ], $attributes));
    }

    public function test_owner_can_list_administrators(): void
    {
        $owner = $this->createAdministrator([
            'Email' => 'owner@example.com',
            'Role' => 'owner',
        ]);

        $this->createAdministrator([
            'Email' => 'manager@example.com',
        ]);

        $this->actingAs($owner)
            ->getJson('/api/administrator')
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'email',
                    'name',
                    'phone',
                    'role',
                    'image',
                ],
            ])
            ->assertJsonMissingPath('0.password');
    }

    public function test_owner_can_get_administrator_by_id(): void
    {
        $owner = $this->createAdministrator([
            'Email' => 'owner@example.com',
            'Role' => 'owner',
        ]);

        $manager = $this->createAdministrator([
            'Email' => 'manager@example.com',
        ]);

        $this->actingAs($owner)
            ->getJson(
                "/api/administrator/{$manager->Administrator_ID}"
            )
            ->assertOk()
            ->assertJson([
                'id' => $manager->Administrator_ID,
                'email' => 'manager@example.com',
                'name' => 'Test Administrator',
                'role' => 'manager',
            ])
            ->assertJsonMissingPath('password');
    }

    public function test_getting_missing_administrator_returns_not_found(): void
    {
        $owner = $this->createAdministrator([
            'Email' => 'owner@example.com',
            'Role' => 'owner',
        ]);

        $this->actingAs($owner)
            ->getJson('/api/administrator/999')
            ->assertNotFound()
            ->assertJson([
                'message' => 'Administrator not found',
            ]);
    }

    public function test_owner_can_create_administrator(): void
    {
        $owner = $this->createAdministrator([
            'Email' => 'owner@example.com',
            'Role' => 'owner',
        ]);

        $response = $this->actingAs($owner)
            ->post('/api/administrator', [
                'email' => 'sales@example.com',
                'name' => 'Sales Administrator',
                'role' => 'sales',
                'phone' => '0501111111',
                'password' => 'secret123',
                'image' => UploadedFile::fake()->image('sales.jpg'),
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('email', 'sales@example.com')
            ->assertJsonPath('role', 'sales')
            ->assertJsonMissingPath('password');

        $administrator = Administrator::where(
            'Email',
            'sales@example.com'
        )->firstOrFail();

        $this->assertTrue(
            Hash::check('secret123', $administrator->Password)
        );

        $this->assertTrue(
            Storage::disk('uploads')->exists(
                basename($administrator->Image)
            )
        );
    }

    public function test_administrator_email_must_be_unique(): void
    {
        $owner = $this->createAdministrator([
            'Email' => 'owner@example.com',
            'Role' => 'owner',
        ]);

        $this->createAdministrator([
            'Email' => 'manager@example.com',
        ]);

        $this->actingAs($owner)
            ->withHeader('Accept', 'application/json')
            ->post('/api/administrator', [
                'email' => 'manager@example.com',
                'name' => 'Another Manager',
                'role' => 'manager',
                'phone' => '0502222222',
                'password' => 'secret123',
                'image' => UploadedFile::fake()->image('manager.jpg'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_owner_role_cannot_be_assigned_when_creating_administrator(): void
    {
        $owner = $this->createAdministrator([
            'Email' => 'owner@example.com',
            'Role' => 'owner',
        ]);

        $this->actingAs($owner)
            ->withHeader('Accept', 'application/json')
            ->post('/api/administrator', [
                'email' => 'another-owner@example.com',
                'name' => 'Another Owner',
                'role' => 'owner',
                'phone' => '0502222222',
                'password' => 'secret123',
                'image' => UploadedFile::fake()->image('owner.jpg'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('role');
    }

    public function test_owner_can_update_administrator_without_changing_password(): void
    {
        $owner = $this->createAdministrator([
            'Email' => 'owner@example.com',
            'Role' => 'owner',
        ]);

        $manager = $this->createAdministrator([
            'Email' => 'manager@example.com',
        ]);

        $oldPassword = $manager->Password;

        $this->actingAs($owner)
            ->putJson(
                "/api/administrator/{$manager->Administrator_ID}",
                [
                    'email' => 'updated@example.com',
                    'name' => 'Updated Manager',
                    'phone' => '0503333333',
                    'role' => 'manager',
                ]
            )
            ->assertOk()
            ->assertJsonPath('email', 'updated@example.com');

        $manager->refresh();

        $this->assertSame($oldPassword, $manager->Password);
    }

    public function test_owner_can_update_administrator_password(): void
    {
        $owner = $this->createAdministrator([
            'Email' => 'owner@example.com',
            'Role' => 'owner',
        ]);

        $manager = $this->createAdministrator([
            'Email' => 'manager@example.com',
        ]);

        $this->actingAs($owner)
            ->putJson(
                "/api/administrator/{$manager->Administrator_ID}",
                [
                    'email' => $manager->Email,
                    'name' => $manager->Name,
                    'phone' => $manager->Phone,
                    'role' => $manager->Role,
                    'password' => 'new-password',
                ]
            )
            ->assertOk();

        $manager->refresh();

        $this->assertTrue(
            Hash::check('new-password', $manager->Password)
        );
    }

    public function test_updating_image_removes_old_image(): void
    {
        $owner = $this->createAdministrator([
            'Email' => 'owner@example.com',
            'Role' => 'owner',
        ]);

        Storage::disk('uploads')->put('old.jpg', 'old image');

        $manager = $this->createAdministrator([
            'Email' => 'manager@example.com',
            'Image' => '/upload/old.jpg',
        ]);

        $response = $this->actingAs($owner)
            ->post(
                "/api/administrator/{$manager->Administrator_ID}",
                [
                    '_method' => 'PUT',
                    'email' => $manager->Email,
                    'name' => $manager->Name,
                    'phone' => $manager->Phone,
                    'role' => $manager->Role,
                    'image' => UploadedFile::fake()->image('new.jpg'),
                ]
            );

        $response->assertOk();

        $manager->refresh();

        $this->assertTrue(
            Storage::disk('uploads')->exists(
                basename($manager->Image)
            )
        );

        $this->assertFalse(
            Storage::disk('uploads')->exists('old.jpg')
        );
    }

    public function test_manager_cannot_modify_owner(): void
    {
        $owner = $this->createAdministrator([
            'Email' => 'owner@example.com',
            'Role' => 'owner',
        ]);

        $manager = $this->createAdministrator([
            'Email' => 'manager@example.com',
        ]);

        $this->actingAs($manager)
            ->putJson(
                "/api/administrator/{$owner->Administrator_ID}",
                [
                    'email' => $owner->Email,
                    'name' => 'Modified Owner',
                    'phone' => $owner->Phone,
                    'role' => 'owner',
                ]
            )
            ->assertForbidden()
            ->assertJson([
                'message' => 'Only an owner can modify an owner',
            ]);
    }

    public function test_owner_cannot_be_deleted(): void
    {
        $owner = $this->createAdministrator([
            'Email' => 'owner@example.com',
            'Role' => 'owner',
        ]);

        $this->actingAs($owner)
            ->deleteJson(
                "/api/administrator/{$owner->Administrator_ID}"
            )
            ->assertForbidden();

        $this->assertDatabaseHas('administrators', [
            'Administrator_ID' => $owner->Administrator_ID,
        ]);
    }

    public function test_deleting_administrator_removes_image(): void
    {
        $owner = $this->createAdministrator([
            'Email' => 'owner@example.com',
            'Role' => 'owner',
        ]);

        Storage::disk('uploads')->put(
            'manager.jpg',
            'manager image'
        );

        $manager = $this->createAdministrator([
            'Email' => 'manager@example.com',
            'Image' => '/upload/manager.jpg',
        ]);

        $this->actingAs($owner)
            ->deleteJson(
                "/api/administrator/{$manager->Administrator_ID}"
            )
            ->assertNoContent();

        $this->assertDatabaseMissing('administrators', [
            'Administrator_ID' => $manager->Administrator_ID,
        ]);

        $this->assertFalse(
            Storage::disk('uploads')->exists('manager.jpg')
        );
    }

    #[DataProvider('requiredAdministratorFieldsProvider')]
    public function test_required_fields_are_validated_when_creating_administrator(
        string $field
    ): void {
        $owner = $this->createAdministrator([
            'Email' => 'owner@example.com',
            'Role' => 'owner',
        ]);

        $data = [
            'email' => 'manager@example.com',
            'name' => 'Manager',
            'role' => 'manager',
            'phone' => '0501234567',
            'password' => 'password123',
            'image' => UploadedFile::fake()->image('manager.jpg'),
        ];

        unset($data[$field]);

        $this->actingAs($owner)
            ->post('/api/administrator', $data, [
                'Accept' => 'application/json',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);
    }

    public static function requiredAdministratorFieldsProvider(): array
    {
        return [
            ['email'],
            ['name'],
            ['role'],
            ['phone'],
            ['password'],
            ['image'],
        ];
    }

    public function test_updating_administrator_without_image_preserves_image(): void
    {
        $owner = $this->createAdministrator([
            'Email' => 'owner@example.com',
            'Role' => 'owner',
        ]);

        Storage::disk('uploads')->put('manager.jpg', 'old image');

        $manager = $this->createAdministrator([
            'Email' => 'manager@example.com',
            'Image' => '/upload/manager.jpg',
        ]);

        $this->actingAs($owner)
            ->putJson(
                "/api/administrator/{$manager->Administrator_ID}",
                [
                    'email' => 'updated@example.com',
                    'name' => 'Updated Manager',
                    'phone' => '0503333333',
                    'role' => 'manager',
                ]
            )
            ->assertOk();

        $manager->refresh();

        $this->assertSame('/upload/manager.jpg', $manager->Image);

        $this->assertTrue(
            Storage::disk('uploads')->exists('manager.jpg')
        );
    }

    public function test_administrator_image_can_be_updated_when_old_image_is_missing(): void
    {
        $owner = $this->createAdministrator([
            'Email' => 'owner@example.com',
            'Role' => 'owner',
        ]);

        $manager = $this->createAdministrator([
            'Email' => 'manager@example.com',
            'Image' => '/upload/missing.jpg',
        ]);

        $this->assertFalse(
            Storage::disk('uploads')->exists('missing.jpg')
        );

        $this->actingAs($owner)
            ->post(
                "/api/administrator/{$manager->Administrator_ID}",
                [
                    '_method' => 'PUT',
                    'email' => $manager->Email,
                    'name' => $manager->Name,
                    'phone' => $manager->Phone,
                    'role' => $manager->Role,
                    'image' => UploadedFile::fake()->image('new.jpg'),
                ]
            )
            ->assertOk();

        $manager->refresh();

        $this->assertNotSame('/upload/missing.jpg', $manager->Image);

        $this->assertTrue(
            Storage::disk('uploads')->exists(
                basename($manager->Image)
            )
        );
    }

    public function test_updating_missing_administrator_returns_not_found(): void
    {
        $owner = $this->createAdministrator([
            'Email' => 'owner@example.com',
            'Role' => 'owner',
        ]);

        $this->actingAs($owner)
            ->putJson('/api/administrator/999999', [
                'email' => 'manager@example.com',
                'name' => 'Manager',
                'phone' => '0501234567',
                'role' => 'manager',
            ])
            ->assertNotFound()
            ->assertJson([
                'message' => 'Administrator not found',
            ]);
    }

    public function test_deleting_missing_administrator_returns_not_found(): void
    {
        $owner = $this->createAdministrator([
            'Email' => 'owner@example.com',
            'Role' => 'owner',
        ]);

        $this->actingAs($owner)
            ->deleteJson('/api/administrator/999999')
            ->assertNotFound();
    }

    public function test_owner_role_cannot_be_assigned_when_updating_administrator(): void
    {
        $owner = $this->createAdministrator([
            'Email' => 'owner@example.com',
            'Role' => 'owner',
        ]);

        $manager = $this->createAdministrator([
            'Email' => 'manager@example.com',
            'Role' => 'manager',
        ]);

        $this->actingAs($owner)
            ->putJson(
                "/api/administrator/{$manager->Administrator_ID}",
                [
                    'email' => $manager->Email,
                    'name' => $manager->Name,
                    'phone' => $manager->Phone,
                    'role' => 'owner',
                ]
            )
            ->assertForbidden()
            ->assertJson([
                'message' => 'Owner role cannot be assigned',
            ]);

        $manager->refresh();

        $this->assertSame('manager', $manager->Role);
    }

    #[DataProvider('validRolesProvider')]
    public function test_can_create_administrator_with_valid_role(
        string $role
    ): void {
        $owner = $this->createAdministrator([
            'Email' => 'owner@example.com',
            'Role' => 'owner',
        ]);

        $response = $this->actingAs($owner)
            ->post('/api/administrator', [
                'email' => "{$role}@example.com",
                'name' => 'Test Admin',
                'role' => $role,
                'phone' => '0500000000',
                'password' => 'password123',
                'image' => UploadedFile::fake()->image('avatar.jpg'),
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('role', $role);

        $this->assertDatabaseHas('administrators', [
            'Email' => "{$role}@example.com",
            'Role' => $role,
        ]);
    }

    public static function validRolesProvider(): array
    {
        return [
            'manager' => ['manager'],
            'sales' => ['sales'],
        ];
    }

    public function test_manager_cannot_see_owner_in_administrator_list(): void
    {
        $owner = $this->createAdministrator([
            'Email' => 'owner@example.com',
            'Role' => 'owner',
        ]);

        $manager = $this->createAdministrator([
            'Email' => 'manager@example.com',
            'Role' => 'manager',
        ]);

        $this->actingAs($manager)
            ->getJson('/api/administrator')
            ->assertOk()
            ->assertJsonFragment([
                'id' => $manager->Administrator_ID,
            ])
            ->assertJsonMissing([
                'id' => $owner->Administrator_ID,
            ]);
    }

    public function test_manager_cannot_get_owner_by_id(): void
    {
        $owner = $this->createAdministrator([
            'Email' => 'owner@example.com',
            'Role' => 'owner',
        ]);

        $manager = $this->createAdministrator([
            'Email' => 'manager@example.com',
            'Role' => 'manager',
        ]);

        $this->actingAs($manager)
            ->getJson(
                "/api/administrator/{$owner->Administrator_ID}"
            )
            ->assertNotFound();
    }

    public function test_owner_can_see_owner(): void
    {
        $owner = $this->createAdministrator([
            'Email' => 'owner@example.com',
            'Role' => 'owner',
        ]);

        $this->actingAs($owner)
            ->getJson(
                "/api/administrator/{$owner->Administrator_ID}"
            )
            ->assertOk()
            ->assertJsonPath(
                'id',
                $owner->Administrator_ID
            );
    }
}

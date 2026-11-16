<?php

namespace Tests\Feature;

use App\Models\Administrator;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadAuthorizationTest extends TestCase
{
    public function test_guests_cannot_download_uploaded_images(): void
    {
        Storage::fake('uploads');
        Storage::disk('uploads')->put('photo.png', 'private image');

        $this->get('/upload/photo.png')->assertRedirect('/');
        $this->getJson('/upload/photo.png')->assertUnauthorized();
        $this->get('/upload/missing.png')->assertRedirect('/');
    }

    public function test_authenticated_administrators_can_download_images(): void
    {
        Storage::fake('uploads');
        Storage::disk('uploads')->put('photo.png', 'private image');

        $response = $this->actingAs(new Administrator())->get('/upload/photo.png');

        $response->assertOk();
        $this->assertSame('private image', $response->streamedContent());
        $this->assertTrue($response->headers->hasCacheControlDirective('private'));
        $this->assertTrue($response->headers->hasCacheControlDirective('no-store'));
    }

    public function test_authenticated_administrators_receive_the_fallback_image(): void
    {
        Storage::fake('uploads');
        Storage::disk('uploads')->put('NotFound.png', 'fallback image');

        $response = $this->actingAs(new Administrator())->get('/upload/missing.png');

        $response->assertOk();
        $this->assertSame('fallback image', $response->streamedContent());
    }

    public function test_missing_image_and_fallback_return_not_found(): void
    {
        Storage::fake('uploads');

        $this->actingAs(new Administrator())->get('/upload/missing.png')->assertNotFound();
    }
}

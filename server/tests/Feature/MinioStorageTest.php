<?php

namespace Tests\Feature;

use App\Models\Administrator;
use App\Services\ImageStorage;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MinioStorageTest extends TestCase
{
    public function test_image_built_with_seed_data_serves_the_private_fallback(): void
    {
        if (config('filesystems.disks.uploads.driver') !== 's3') {
            $this->markTestSkipped('Run with the MinIO test stack.');
        }

        $disk = Storage::disk('uploads');
        $this->assertInstanceOf(AwsS3V3Adapter::class, $disk);
        $this->assertTrue($disk->exists('NotFound.png'));
        $this->assertSame('private', $disk->getVisibility('NotFound.png'));
        $response = $this->actingAs(new Administrator())->get('/upload/missing-seed-test.png');
        $response->assertOk();
        $this->assertSame($disk->get('NotFound.png'), $response->streamedContent());
    }

    public function test_private_images_can_be_stored_streamed_and_deleted_in_minio(): void
    {
        if (config('filesystems.disks.uploads.driver') !== 's3') {
            $this->markTestSkipped('Run with the MinIO test stack.');
        }

        $disk = Storage::disk('uploads');
        $this->assertInstanceOf(AwsS3V3Adapter::class, $disk);
        $images = new ImageStorage();
        $file = UploadedFile::fake()->image('minio.png', 10, 10);
        $contents = file_get_contents($file->getPathname());
        $filename = $images->store($file);

        try {
            $this->assertSame($contents, $disk->get($filename));
            $this->assertSame('private', $disk->getVisibility($filename));
            $this->get('/upload/' . $filename)->assertRedirect('/');
            $response = $this->actingAs(new Administrator())->get('/upload/' . $filename);
            $response->assertOk();
            $this->assertSame($contents, $response->streamedContent());
            $this->assertTrue($response->headers->hasCacheControlDirective('private'));
            $this->assertTrue($response->headers->hasCacheControlDirective('no-store'));
        } finally {
            $images->cleanup('/upload/' . $filename);
        }

        $this->assertFalse($disk->exists($filename));
    }
}

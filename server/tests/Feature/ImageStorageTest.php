<?php

namespace Tests\Feature;

use App\Services\ImageStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageStorageTest extends TestCase
{
    public function test_uploads_keep_their_extension_and_do_not_overwrite_each_other(): void
    {
        Storage::fake('uploads');
        $images = new ImageStorage();
        $first = UploadedFile::fake()->image('first.png', 10, 10);
        $second = UploadedFile::fake()->image('second.png', 20, 20);
        $firstContents = file_get_contents($first->getPathname());
        $secondContents = file_get_contents($second->getPathname());

        $firstName = $images->store($first);
        $secondName = $images->store($second);

        $this->assertSame('png', pathinfo($firstName, PATHINFO_EXTENSION));
        $this->assertSame('png', pathinfo($secondName, PATHINFO_EXTENSION));
        $this->assertNotSame('', pathinfo($firstName, PATHINFO_FILENAME));
        $this->assertNotSame($firstName, $secondName);
        $this->assertSame($firstContents, Storage::disk('uploads')->get($firstName));
        $this->assertSame($secondContents, Storage::disk('uploads')->get($secondName));
    }
}

<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class ImageStorage
{
    public function store(UploadedFile $file): string
    {
        $filename = uniqid() . '.' . $file->extension();

        $path = Storage::disk('uploads')->putFileAs(
            '',
            $file,
            $filename
        );

        if ($path === false) {
            throw new RuntimeException('Failed to store image.');
        }

        return $filename;
    }

    //------------------------------------------------------------------------

    public function cleanup(?string $image): void
    {
        if (! $image) {
            return;
        }

        try {
            $disk = Storage::disk('uploads');
            $filename = basename($image);

            if ($disk->exists($filename) && ! $disk->delete($filename)) {
                throw new RuntimeException('Failed to delete image.');
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    //------------------------------------------------------------------------
}

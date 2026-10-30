<?php

namespace App\Http\Controllers;

use App\Http\Resources\CourseResource;
use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class CourseController extends Controller
{
    private function storeImage(UploadedFile $file): string
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

    private function cleanupImage(?string $image): void
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

    public function getAll(): JsonResponse
    {
        return CourseResource::collection(
            Course::with('students')->get()
        )->response();
    }

    //------------------------------------------------------------------------

    public function getById(int $id): JsonResponse
    {
        $course = Course::with('students')->find($id);

        if (! $course) {
            return response()->json([
                'message' => 'Course not found',
            ], Response::HTTP_NOT_FOUND);
        }

        return (new CourseResource($course))->response();
    }

    //------------------------------------------------------------------------

    public function add(Request $request): JsonResponse
    {
        /**
         * @var array{
         *     name: string,
         *     description: string,
         *     image: UploadedFile
         * } $validated
         */
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:32', 'min:2'],
            'description' => ['required', 'string', 'max:500', 'min:8'],
            'image' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,gif',
                'max:1024',
                'dimensions:max_width=350,max_height=350',
            ],
        ]);
        $file = $request->file('image');
        if (! $file instanceof UploadedFile) {
            return response()->json([
                'message' => 'Invalid image',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $filename = $this->storeImage($file);
        try {
            /** @var array<string, mixed> $data */
            $data = [
                'Name' => $validated['name'],
                'Description' => $validated['description'],
                'Image' => "/upload/$filename",
            ];
            $course = Course::create($data);
        } catch (Throwable $exception) {
            $this->cleanupImage("/upload/$filename");
            throw $exception;
        }
        return (new CourseResource($course))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    //------------------------------------------------------------------------

    public function update(Request $request, int $id): JsonResponse
    {
        $course = Course::find($id);

        if (! $course) {
            return response()->json(['message' => 'Course not found'], Response::HTTP_NOT_FOUND);
        }
        /**
         * @var array{
         *     name: string,
         *     description: string,
         *     image?: UploadedFile|null
         * } $validated
         */
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:32', 'min:2'],
            'description' => ['required', 'string', 'max:500', 'min:8'],
            'image' => [
                'sometimes',
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,gif',
                'max:1024',
                'dimensions:max_width=350,max_height=350',
            ],
        ]);
        /** @var array<string, mixed> $data */
        $data = [
            'Name' => $validated['name'],
            'Description' => $validated['description'],
        ];
        $oldImage = $course->Image;
        $newImage = null;
        $imageChanged = $request->hasFile('image');

        if ($imageChanged) {
            $file = $request->file('image');

            if (! $file instanceof UploadedFile) {
                return response()->json([
                    'message' => 'Invalid image',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            $filename = $this->storeImage($file);
            $newImage = "/upload/$filename";
            $data['Image'] = $newImage;
        }
        try {
            $course->update($data);
        } catch (Throwable $exception) {
            $this->cleanupImage($newImage);
            throw $exception;
        }

        if ($imageChanged) {
            $this->cleanupImage($oldImage);
        }
        return (new CourseResource(
            $course->refresh()->load('students')
        ))->response();
    }

    //------------------------------------------------------------------------

    public function remove(int $id): Response
    {
        $course = Course::find($id);

        if (! $course) {
            return response('', Response::HTTP_NOT_FOUND);
        }
        if ($course->students()->exists()) {
            return response('', Response::HTTP_CONFLICT);
        }
        $oldImage = $course->Image;
        $course->delete();
        $this->cleanupImage($oldImage);

        return response('', Response::HTTP_NO_CONTENT);
    }
}

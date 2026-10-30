<?php

namespace App\Http\Controllers;

use App\Http\Resources\StudentResource;
use App\Models\Course;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use RuntimeException;
use Throwable;

class StudentController extends Controller
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
        return StudentResource::collection(
            Student::with('courses')->get()
        )->response();
    }

    //------------------------------------------------------------------------

    public function getById(int $id): JsonResponse
    {
        $student = Student::with('courses')->find($id);

        if (! $student) {
            return response()->json([
                'message' => 'Student not found',
            ], Response::HTTP_NOT_FOUND);
        }

        return (new StudentResource($student))->response();
    }

    //------------------------------------------------------------------------

    public function add(Request $request): JsonResponse
    {
        /**
         * @var array{
         *     email: string,
         *     name: string,
         *     phone: string,
         *     image: UploadedFile,
         *     courses?: array<int, int>
         * } $validated
         */
        $validated = $request->validate([
            'email' => [
                'required',
                'email',
                'unique:students,Email',
            ],
            'name' => [
                'required',
                'string',
                'max:32',
                'min:2',
            ],
            'phone' => [
                'required',
                'string',
                'max:16',
                'regex:/^\+?[0-9](?:[0-9\s\-().]*[0-9])?$/',
            ],
            'image' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,gif',
                'max:500',
                'dimensions:max_width=250,max_height=250',
            ],
            'courses' => [
                'sometimes',
                'array',
            ],
            'courses.*' => [
                'integer',
                'distinct',
                'exists:courses,Course_ID',
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
                'Email' => $validated['email'],
                'Name' => $validated['name'],
                'Phone' => $validated['phone'],
                'Image' => "/upload/$filename",
            ];
            $courses = $validated['courses'] ?? [];
            $student = Student::create($data);
            $student->courses()->sync($courses);
        } catch (Throwable $exception) {
            $this->cleanupImage("/upload/$filename");
            throw $exception;
        }
        return (new StudentResource($student->load('courses')))
            ->response()
            ->setStatusCode(201);
    }

    //------------------------------------------------------------------------

    public function update(Request $request, int $id): JsonResponse
    {
        $student = Student::find($id);

        if (! $student) {
            return response()->json([
                'message' => 'Student not found',
            ], Response::HTTP_NOT_FOUND);
        }
        /**
         * @var array{
         *     email: string,
         *     name: string,
         *     phone: string,
         *     image?: UploadedFile|null,
         *     courses?: array<int, int>
         * } $validated
         */
        $validated = $request->validate([
            'email' => [
                'required',
                'email',
                Rule::unique('students', 'Email')
                    ->ignore($id, 'Student_ID'),
                'max:60',
                'min:7',
            ],
            'name' => [
                'required',
                'string',
                'max:32',
                'min:2',
            ],
            'phone' => [
                'required',
                'string',
                'max:16',
                'regex:/^\+?[0-9](?:[0-9\s\-().]*[0-9])?$/',
            ],
            'image' => [
                'sometimes',
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,gif',
                'max:500',
                'dimensions:max_width=250,max_height=250',
            ],
            'courses' => [
                'sometimes',
                'array',
            ],
            'courses.*' => [
                'integer',
                'distinct',
                'exists:courses,Course_ID',
            ],
        ]);
        /** @var array<string, mixed> $data */
        $data = [
            'Email' => $validated['email'],
            'Name' => $validated['name'],
            'Phone' => $validated['phone'],
        ];

        $courses = $validated['courses'] ?? null;

        $oldImage = $student->Image;
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

            Storage::disk('uploads')->putFileAs(
                '',
                $file,
                $filename
            );
            $newImage = "/upload/$filename";
            $data['Image'] = $newImage;
        }

        try {
            $student->update($data);
        } catch (Throwable $exception) {
            $this->cleanupImage($newImage);
            throw $exception;
        }

        if ($imageChanged) {
            $this->cleanupImage($oldImage);
        }

        if ($courses !== null) {
            $student->courses()->sync($courses);
        }

        $student->refresh();
        return (new StudentResource($student->load('courses')))
            ->response();
    }

    //------------------------------------------------------------------------

    public function remove(int $id): Response
    {
        $student = Student::find($id);

        if (! $student) {
            return response('', Response::HTTP_NOT_FOUND);
        }
        $oldImage = $student->Image;
        $student->courses()->detach();
        $student->delete();
        $this->cleanupImage($oldImage);

        return response('', Response::HTTP_NO_CONTENT);
    }

    //------------------------------------------------------------------------

    public function unsubscribe(int $studentId, int $courseId): Response|JsonResponse
    {
        $student = Student::find($studentId);

        if (! $student) {
            return response()->json([
                'message' => 'Student not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $course = Course::find($courseId);

        if (! $course) {
            return response()->json([
                'message' => 'Course not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $student->courses()->detach($courseId);

        return response('', Response::HTTP_NO_CONTENT);
    }
}

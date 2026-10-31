<?php

namespace App\Http\Controllers;

use App\Http\Resources\StudentResource;
use App\Http\Validation\CommonRules;
use App\Http\Validation\StudentRules;
use App\Models\Course;
use App\Models\Student;
use App\Services\ImageStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

class StudentController extends Controller
{
    public function __construct(private readonly ImageStorage $images)
    {
    }

    public function getAll(Request $request): JsonResponse
    {
        $request->validate(CommonRules::pagination());

        return StudentResource::collection(
            Student::orderBy('Student_ID')->paginate(20)
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
        $validated = $request->validate(StudentRules::create());
        $file = $request->file('image');
        if (! $file instanceof UploadedFile) {
            return response()->json([
                'message' => 'Invalid image',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $filename = $this->images->store($file);
        try {
            /** @var array<string, mixed> $data */
            $data = [
                'Email' => $validated['email'],
                'Name' => $validated['name'],
                'Phone' => $validated['phone'],
                'Image' => "/upload/$filename",
            ];
            $courses = $validated['courses'] ?? [];
            $student = DB::transaction(function () use ($data, $courses): Student {
                $student = Student::create($data);
                $student->courses()->sync($courses);
                return $student;
            });
        } catch (Throwable $exception) {
            $this->images->cleanup("/upload/$filename");
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
         *     courses?: array<int, int>|null
         * } $validated
         */
        $validated = $request->validate(StudentRules::update($id));
        /** @var array<string, mixed> $data */
        $data = [
            'Email' => $validated['email'],
            'Name' => $validated['name'],
            'Phone' => $validated['phone'],
        ];

        $courses = array_key_exists('courses', $validated)
            ? ($validated['courses'] ?? [])
            : null;

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

            $filename = $this->images->store($file);

            $newImage = "/upload/$filename";
            $data['Image'] = $newImage;
        }

        try {
            DB::transaction(function () use ($student, $data, $courses): void {
                $student->update($data);
                if ($courses !== null) {
                    $student->courses()->sync($courses);
                }
            });
        } catch (Throwable $exception) {
            $this->images->cleanup($newImage);
            throw $exception;
        }

        if ($imageChanged) {
            $this->images->cleanup($oldImage);
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
        DB::transaction(function () use ($student): void {
            $student->courses()->detach();
            $student->delete();
        });
        $this->images->cleanup($oldImage);

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

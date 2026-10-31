<?php

namespace App\Http\Controllers;

use App\Http\Resources\CourseResource;
use App\Http\Validation\CommonRules;
use App\Http\Validation\CourseRules;
use App\Models\Course;
use App\Services\ImageStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Throwable;

class CourseController extends Controller
{
    public function __construct(private readonly ImageStorage $images)
    {
    }

    public function getAll(Request $request): JsonResponse
    {
        $request->validate(CommonRules::pagination());

        return CourseResource::collection(
            Course::orderBy('Course_ID')->paginate(20)
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
        $validated = $request->validate(CourseRules::create());
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
                'Name' => $validated['name'],
                'Description' => $validated['description'],
                'Image' => "/upload/$filename",
            ];
            $course = Course::create($data);
        } catch (Throwable $exception) {
            $this->images->cleanup("/upload/$filename");
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
        $validated = $request->validate(CourseRules::update($id));
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
            $filename = $this->images->store($file);
            $newImage = "/upload/$filename";
            $data['Image'] = $newImage;
        }
        try {
            $course->update($data);
        } catch (Throwable $exception) {
            $this->images->cleanup($newImage);
            throw $exception;
        }

        if ($imageChanged) {
            $this->images->cleanup($oldImage);
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
        $this->images->cleanup($oldImage);

        return response('', Response::HTTP_NO_CONTENT);
    }
}

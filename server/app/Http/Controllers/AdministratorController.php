<?php

namespace App\Http\Controllers;

use App\Http\Resources\AdministratorResource;
use App\Http\Validation\AdministratorRules;
use App\Http\Validation\CommonRules;
use App\Models\Administrator;
use App\Services\ImageStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Throwable;

class AdministratorController extends Controller
{
    public function __construct(private readonly ImageStorage $images)
    {
    }

    public function getAll(Request $request): JsonResponse
    {
        $request->validate(CommonRules::pagination());

        /** @var Administrator $admin */
        $admin = Auth::user();

        return AdministratorResource::collection(
            Administrator::visibleTo($admin)->orderBy('Administrator_ID')->paginate(20)
        )->response();
    }

    //------------------------------------------------------------------------

    public function getById(int $id): JsonResponse
    {

        /** @var Administrator $admin */
        $admin = Auth::user();

        $administrator = Administrator::visibleTo($admin)
            ->where('Administrator_ID', $id)
            ->first();

        if (! $administrator) {
            return response()->json([
                'message' => 'Administrator not found',
            ], Response::HTTP_NOT_FOUND);
        }

        return (new AdministratorResource($administrator))->response();
    }

    //------------------------------------------------------------------------

    public function add(Request $request): JsonResponse
    {
        /**
         * @var array{
         *     email: string,
         *     name: string,
         *     role: 'manager'|'sales',
         *     phone: string,
         *     password: string,
         *     image: UploadedFile
         * } $validated
         */
        $validated = $request->validate(AdministratorRules::create());
        $file = $request->file('image');
        if (! $file instanceof UploadedFile) {
            return response()->json([
                'error' => 'Invalid image',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $filename = $this->images->store($file);

        try {
            /** @var array<string, mixed> $data */
            $data = [
                'Email' => $validated['email'],
                'Name' => $validated['name'],
                'Role' => $validated['role'],
                'Phone' => $validated['phone'],
                'Password' => Hash::make($validated['password']),
                'Image' => "/upload/$filename",
            ];
            $administrator = Administrator::create($data);
        } catch (Throwable $exception) {
            $this->images->cleanup("/upload/$filename");
            throw $exception;
        }

        return (new AdministratorResource($administrator))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    //------------------------------------------------------------------------

    public function update(Request $request, int $id): JsonResponse
    {
        /** @var Administrator $admin */
        $admin = Auth::user();
        $administrator = Administrator::find($id);
        if (! $administrator) {
            return response()->json([
                'message' => 'Administrator not found',
            ], Response::HTTP_NOT_FOUND);
        }

        if ($administrator->Role === 'owner' && $admin->Role !== 'owner') {
            return response()->json([
                'message' => 'Only an owner can modify an owner',
            ], Response::HTTP_FORBIDDEN);
        }
        /**
         * @var array{
         *     email: string,
         *     name: string,
         *     role?: 'manager'|'owner'|'sales',
         *     phone: string,
         *     password?: string|null,
         *     image?: UploadedFile|null
         * } $validated
         */
        $validated = $request->validate(AdministratorRules::update($id));
        /** @var array<string, mixed> $data */
        $data = [
            'Email' => $validated['email'],
            'Name' => $validated['name'],
            'Role' => $validated['role'] ?? $administrator->Role,
            'Phone' => $validated['phone'],
        ];

        if ($administrator->Role === 'owner') {
            unset($data['Role']);
        } elseif (
            isset($data['Role']) &&
            $data['Role'] === 'owner'
        ) {
            return response()->json([
                'message' => 'Owner role cannot be assigned',
            ], Response::HTTP_FORBIDDEN);
        }

        if (! empty($validated['password'])) {
            $data['Password'] = Hash::make($validated['password']);
        }

        $oldImage = $administrator->Image;
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
            $administrator->update($data);
        } catch (Throwable $exception) {
            $this->images->cleanup($newImage);
            throw $exception;
        }

        if ($imageChanged) {
            $this->images->cleanup($oldImage);
        }
        return (new AdministratorResource($administrator))->response();
    }

    //------------------------------------------------------------------------

    public function remove(int $id): Response
    {

        $administrator = Administrator::find($id);

        if (! $administrator) {
            return response('', Response::HTTP_NOT_FOUND);
        }

        if ($administrator->Role === 'owner') {
            return response('', Response::HTTP_FORBIDDEN);
        }

        $oldImage = $administrator->Image;
        $administrator->delete();

        $this->images->cleanup($oldImage);
        return response('', Response::HTTP_NO_CONTENT);
    }

    //------------------------------------------------------------------------

    public function getTotalCount(): JsonResponse
    {
        return response()->json([
            'count' => Administrator::count(),
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Resources\AdministratorResource;
use App\Models\Administrator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use RuntimeException;
use Throwable;

class AdministratorController extends Controller
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
        /** @var Administrator $admin */
        $admin = Auth::user();

        return AdministratorResource::collection(
            Administrator::visibleTo($admin)->get()
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
        $validated = $request->validate([
            'email' => [
                'required',
                'email',
                'unique:administrators,Email',
                'max:64',
                'min:7',
            ],
            'name' => [
                'required',
                'string',
                'max:32',
                'min:2',
            ],
            'role' => [
                'required',
                Rule::in(['manager', 'sales']),
            ],
            'phone' => [
                'required',
                'string',
                'max:16',
                'regex:/^\+?[0-9](?:[0-9\s\-().]*[0-9])?$/',
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'max:32',
            ],
            'image' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,gif',
                'max:500',
                'dimensions:max_width=250,max_height=250',
            ],
        ]);
        $file = $request->file('image');
        if (! $file instanceof UploadedFile) {
            return response()->json([
                'error' => 'Invalid image',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $filename = $this->storeImage($file);

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
            $this->cleanupImage("/upload/$filename");
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
        $validated = $request->validate([
            'email' => [
                'required',
                'email',
                'max:64',
                Rule::unique('administrators', 'Email')
                    ->ignore($id, 'Administrator_ID'),
            ],
            'name' => [
                'required',
                'string',
                'max:32',
                'min:2',
            ],
            'role' => [
                'sometimes',
                Rule::in(['manager', 'owner', 'sales']),
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
            'password' => [
                'sometimes',
                'nullable',
                'string',
                'min:8',
            ],
        ]);
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

            $filename = $this->storeImage($file);
            $newImage = "/upload/$filename";

            $data['Image'] = $newImage;
        }

        try {
            $administrator->update($data);
        } catch (Throwable $exception) {
            $this->cleanupImage($newImage);
            throw $exception;
        }

        if ($imageChanged) {
            Storage::disk('uploads')->delete(basename($oldImage));
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

        if ($oldImage && Storage::disk('uploads')->exists(basename($oldImage))) {
            Storage::disk('uploads')->delete(basename($oldImage));
        }
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

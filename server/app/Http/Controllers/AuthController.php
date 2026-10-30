<?php

namespace App\Http\Controllers;

use App\Http\Resources\AdministratorResource;
use App\Models\Administrator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => [
                'required',
                'email',
                'max:64',
                'min:7',
            ],
            'password' => [
                'required',
                'string',
                'max:32',
                'min:8',
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid username or password.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $validated = $validator->validated();

        /** @var string $email */
        $email = $validated['email'];

        /** @var string $password */
        $password = $validated['password'];

        $users = Administrator::where(
            'Email',
            $email
        )->get();

        if ($users->count() !== 1) {
            return response()->json([
                'message' => 'Invalid username or password.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $user = $users->first();

        if (
            $user === null ||
            ! Hash::check($password, $user->Password)
        ) {
            return response()->json([
                'message' => 'Invalid username or password.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        Auth::login($user);

        $request->session()->regenerate();

        return response()->json([
            'administrator' => new AdministratorResource($user),
            'token' => csrf_token(),
        ], Response::HTTP_OK);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Logout successful',
        ]);
    }

    public function auth(): JsonResponse
    {
        $administrator = Auth::user();

        if (! $administrator instanceof Administrator) {
            return response()->json([
                'error' => 'Unauthorized',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return (new AdministratorResource($administrator))->response();
    }
}

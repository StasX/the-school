<?php

use App\Http\Controllers\AdministratorController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\StudentController;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/upload/{filename}', function (string $filename) {
    $disk         = Storage::disk('uploads');
    $filePath     = $disk->path($filename);
    $notFoundPath = $disk->path('NotFound.png');

    $path = ($disk->exists($filename)) ? $filePath : $notFoundPath;
    return response()->file($path);
});

Route::prefix('api')->group(function () {
    Route::get('/health', function () {
        return response('', Response::HTTP_OK);
    });

    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

    Route::get('/help', function () {
                if (! Auth::check()) {
                    return redirect('/');
                }
                return view('help');
            });

    Route::middleware('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/auth', [AuthController::class, 'auth']);

        Route::middleware('role:owner,manager,sales')->group(function () {
            Route::get('/student', [StudentController::class, 'getAll']);
            Route::get('/student/{id}', [StudentController::class, 'getById']);
            Route::post('/student', [StudentController::class, 'add']);
            Route::put('/student/{id}', [StudentController::class, 'update']);
            Route::delete('/student/{id}', [StudentController::class, 'remove']);
            Route::delete('/student/{studentId}/course/{courseId}', [StudentController::class, 'unsubscribe']);

            Route::get('/course', [CourseController::class, 'getAll']);
            Route::get('/course/{id}', [CourseController::class, 'getById']);
        });

        Route::middleware('role:owner,manager')->group(function () {
            Route::post('/course', [CourseController::class, 'add']);
            Route::put('/course/{id}', [CourseController::class, 'update']);
            Route::delete('/course/{id}', [CourseController::class, 'remove']);

            Route::get('/administrator', [AdministratorController::class, 'getAll']);
            Route::get('/administrator/count', [AdministratorController::class, 'getTotalCount']);
            Route::get('/administrator/{id}', [AdministratorController::class, 'getById'])->whereNumber('id');
            Route::post('/administrator', [AdministratorController::class, 'add']);
            Route::put('/administrator/{id}', [AdministratorController::class, 'update']);
            Route::delete('/administrator/{id}', [AdministratorController::class, 'remove']);
        });
    });
});

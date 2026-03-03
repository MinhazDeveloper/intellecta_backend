<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\InstructorController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\StudentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/
// Route::get('/test', function () {
//     return response()->json([
//         'message' => 'API route is working'
//     ]);
// });

// / Public Routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::get('/auth/google', [AuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);

// Password reset
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// / Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    // email verification notification
    Route::post('/email/verification-notification', [AuthController::class, 'sendVerificationEmail']);

    // for admin
    Route::middleware(['role:admin'])->prefix('admin')->group(function () {
        // Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
        Route::get('/dashboard-stats', [AdminController::class, 'getStats']);
        Route::get('/user/all', [AdminController::class, 'userAll'])->name('admin.userAll');
        Route::post('/user/store', [AdminController::class, 'storeUser']);
        Route::put('/user/update/{id}', [AdminController::class, 'updateUser']);
        Route::delete('/user/delete/{id}', [AdminController::class, 'destroy']);
        Route::get('/exams', [ExamController::class, 'getExamlist']);
        Route::get('/exam-history', [AdminController::class, 'getAllSubmissions']);

    });
    // for instructors
    Route::middleware(['role:instructor'])->prefix('instructor')->group(function () {
        Route::get('/dashboard', [InstructorController::class, 'dashboard'])->name('instructor.dashboard');
        Route::get('/stats', [InstructorController::class, 'getStats']);
        Route::apiResource('exams', ExamController::class);
        Route::apiResource('questions', QuestionController::class);

    });
    // for students
    Route::middleware(['role:student'])->prefix('student')->group(function () {
        Route::get('/dashboard', [StudentController::class, 'dashboard'])->name('student.dashboard');
        Route::get('/exams', [ExamController::class, 'getExamList']);
        Route::get('/exam-result/{id}', [ExamController::class, 'getResult']);
        Route::get('/exam-history', [ExamController::class, 'getExamHistory']);
        Route::get('/exams/{id}', [QuestionController::class, 'getQuestionsForStudent']);
        Route::post('exam/submit', [ExamController::class, 'ExamSubmit']);

    });

});

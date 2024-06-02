<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\ActivityTypeController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\EmailVerificationController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\ResetPasswordController;
use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::group([

    'middleware' => 'api',

], function ($router) {

    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::get('user-profile', [AuthController::class, 'userProfile']);
   

Route::resource('locations', LocationController::class);


Route::group(['prefix' => '/roles'], function () {
    Route::get('/', [RoleController::class, 'index']);
    Route::post('/', [RoleController::class, 'store']);
});

Route::get('/users', [UserController::class, 'getUsers']);
Route::get('/users/search', [UserController::class, 'searchUsers']);
Route::get('/users/{id}', [UserController::class, 'getOneUser']);
Route::put('/update-user-profile/{id}', [UserController::class, 'updateUser']);
Route::post('/update-user-profile-picture/{id}', [UserController::class, 'updateProfilePicture']);
Route::put('/update-user-password/{id}', [UserController::class, 'updatePassword']);

Route::get('/admin-panel', [AdminController::class, 'index']);

Route::get('/activity-types', [ActivityTypeController::class, 'getAllActivityTypes']);
Route::get('/activity-types/{id}', [ActivityTypeController::class, 'getOneActivityType']);
Route::post('/activity-types', [ActivityTypeController::class, 'store']);
Route::post('/activity-types/{id}', [ActivityTypeController::class, 'update']);
Route::delete('/activity-types/{id}', [ActivityTypeController::class, 'delete']);

Route::get('/activities', [ActivityController::class, 'getAllActivities']);
Route::get('/activities/latest', [ActivityController::class, 'getLatestActivities']);
Route::get('/activities/search', [ActivityController::class, 'searchActivities']);
Route::get('/activities/{id}', [ActivityController::class, 'getOneActivity']);
Route::post('/activities', [ActivityController::class, 'store']);
Route::post('/activities/{id}', [ActivityController::class, 'update']);
Route::delete('/activities/{id}', [ActivityController::class, 'delete']);
Route::get('/my-activities/{id}', [ActivityController::class, 'activitiesOrganizedBy']);
Route::get('/my-applied-activities/{id}', [ActivityController::class, 'userAppliedActivities']);
Route::get('/my-applied-activities-accepted/{id}', [ActivityController::class, 'userAppliedActivitiesAccepted']);
Route::get('/my-applied-activities-rejected/{id}', [ActivityController::class, 'userAppliedActivitiesRejected']);
Route::get('/my-saved-activities/{id}', [ActivityController::class, 'userSavedActivities']);
Route::post('/activities/{id}/apply', [ActivityController::class, 'apply']);
Route::post('/activities/{id}/unapply', [ActivityController::class, 'unapply']);
Route::post('/activities/{id}/save', [ActivityController::class, 'save']);
Route::post('/activities/{id}/unsave', [ActivityController::class, 'unsave']);

Route::put('/activities/{id}/close', [ActivityController::class, 'closeActivity']);

Route::get('/activities/{activityId}/applications', [ActivityController::class, 'getUsersByActivityId']);
Route::get('/activities/{activityId}/applications/rejected', [ActivityController::class, 'getRejectedUsersByActivityId']);
Route::post('/activities/{activityId}/users/{userId}/reject', [ActivityController::class, 'rejectApplication']);

Route::get('/activities/{activityId}/comments', [CommentController::class, 'getCommentsByActivityId']);
Route::post('/activities/{activityId}/comments', [CommentController::class, 'createComment']);
Route::delete('/comments/{id}', [CommentController::class, 'delete']);

});

Route::post('/sendPasswordResetLink', [ResetPasswordController::class, 'sendEmail']);
Route::post('/resetPassword', [ResetPasswordController::class, 'resetPassword']);

Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])->name('verification.verify');
Route::post('/email/resend', [EmailVerificationController::class, 'resend'])->name('verification.resend');
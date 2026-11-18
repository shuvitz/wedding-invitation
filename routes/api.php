<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FormSubmissionController;
use App\Http\Controllers\Api\GuestController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\NavigationController;
use App\Http\Controllers\Api\PageController;
use App\Http\Controllers\Api\Public\FormSubmissionController as PublicFormSubmissionController;
use App\Http\Controllers\Api\Public\NavigationController as PublicNavigationController;
use App\Http\Controllers\Api\Public\PageController as PublicPageApiController;
use App\Http\Controllers\Api\Public\SettingController as PublicSettingController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    // Public routes
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);

        // User management — authorized via UserPolicy (admin only, see Gate::before)
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::match(['put', 'patch'], '/users/{user}', [UserController::class, 'update']);
        Route::patch('/users/{user}/status', [UserController::class, 'updateStatus']);

        // Settings — singleton record. Editors may view, only admins may update.
        Route::get('/settings', [SettingController::class, 'show']);
        Route::match(['put', 'patch'], '/settings', [SettingController::class, 'update']);

        // Navigation — flat items only (V1). Editors may view, only admins may modify.
        Route::get('/navigation', [NavigationController::class, 'index']);
        Route::post('/navigation', [NavigationController::class, 'store']);
        Route::match(['put', 'patch'], '/navigation/{item}', [NavigationController::class, 'update']);
        Route::patch('/navigation/{item}/visibility', [NavigationController::class, 'updateVisibility']);
        Route::delete('/navigation/{item}', [NavigationController::class, 'destroy']);

        // Media — editors may view, only admins may upload/edit/delete.
        Route::get('/media', [MediaController::class, 'index']);
        Route::post('/media', [MediaController::class, 'store']);
        Route::get('/media/{media}', [MediaController::class, 'show']);
        Route::patch('/media/{media}', [MediaController::class, 'update']);
        Route::delete('/media/{media}', [MediaController::class, 'destroy']);

        // Pages — editors may view/create/edit drafts; only admins may
        // publish, set the homepage, or delete (see PagePolicy).
        Route::get('/pages', [PageController::class, 'index']);
        Route::post('/pages', [PageController::class, 'store']);
        Route::get('/pages/{page}', [PageController::class, 'show']);
        Route::match(['put', 'patch'], '/pages/{page}', [PageController::class, 'update']);
        Route::patch('/pages/{page}/status', [PageController::class, 'updateStatus']);
        Route::patch('/pages/{page}/home', [PageController::class, 'updateHome']);
        Route::delete('/pages/{page}', [PageController::class, 'destroy']);

        // Form submissions — public-entered personal data, admin only (see FormSubmissionPolicy).
        Route::get('/pages/{page}/submissions', [FormSubmissionController::class, 'index']);
        Route::get('/pages/{page}/submissions/export', [FormSubmissionController::class, 'export']);
        Route::delete('/submissions/{submission}', [FormSubmissionController::class, 'destroy']);

        // Guest list — personal data, admin only (see GuestPolicy).
        Route::get('/guests', [GuestController::class, 'index']);
        Route::post('/guests', [GuestController::class, 'store']);
        Route::match(['put', 'patch'], '/guests/{guest}', [GuestController::class, 'update']);
        Route::delete('/guests/{guest}', [GuestController::class, 'destroy']);
    });
});

/*
|--------------------------------------------------------------------------
| Public API
|--------------------------------------------------------------------------
|
| Unauthenticated, read-only (except form submissions, below), safe for a separate frontend (Nuxt) to call
| server-to-server — deliberately outside the 'web' middleware group above
| since there's no session/CSRF concern for stateless public GETs, and no
| reason to pay the cost of booting a session for them. Only ever returns
| published pages and visible navigation items; see the Public\* resources
| for exactly what's excluded (no user info, no draft content, no internal
| admin fields).
|
*/

Route::prefix('public')->group(function () {
    Route::get('/settings', [PublicSettingController::class, 'show']);
    Route::get('/navigation', [PublicNavigationController::class, 'index']);
    Route::get('/home', [PublicPageApiController::class, 'home']);
    Route::get('/pages/{slug}', [PublicPageApiController::class, 'show']);

    // The one write: a visitor submitting a published page's `form` block.
    Route::post('/pages/{slug}/forms/{formKey}', [PublicFormSubmissionController::class, 'store'])
        ->middleware('throttle:form-submissions');
});

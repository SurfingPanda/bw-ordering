<?php

use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SiteContentController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

// Legacy Sanctum auth (unused by the frontend — kept for now).
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Store locator.
Route::get('/stores', [StoreController::class, 'index']);

// Products catalogue (menu is public; editing requires admin/editor).
Route::get('/products', [ProductController::class, 'index']);

// Landing/franchise CMS blob (public read).
Route::get('/site-content', [SiteContentController::class, 'show']);

// Careers — applicants are anonymous, so resume upload + submit are public.
Route::post('/resumes', [ApplicationController::class, 'uploadResume']);
Route::post('/applications', [ApplicationController::class, 'store']);
// Signed, time-limited resume download (no auth header needed; signature gates).
Route::get('/resumes/download/{path}', [ApplicationController::class, 'download'])
    ->name('resumes.download')
    ->middleware('signed');

// Authenticated via the Supabase access token (see SupabaseAuth middleware).
Route::middleware('supabase')->group(function () {
    Route::post('/products/sync', [ProductController::class, 'sync']);

    Route::put('/site-content', [SiteContentController::class, 'update']);
    Route::post('/uploads', [UploadController::class, 'store']);

    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/mine', [OrderController::class, 'mine']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::patch('/orders/{id}/status', [OrderController::class, 'updateStatus']);

    Route::get('/applications', [ApplicationController::class, 'index']);
    Route::get('/resumes/url', [ApplicationController::class, 'resumeUrl']);
});

// Legacy Sanctum-protected routes.
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

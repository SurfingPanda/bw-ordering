<?php

use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SiteContentController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VoucherController;
use Illuminate\Support\Facades\Route;

// Legacy Sanctum auth (unused by the frontend — kept for now).
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Store locator.
Route::get('/stores', [StoreController::class, 'index']);

// Public: which online payment methods are available.
Route::get('/config/payments', [OrderController::class, 'paymentConfig']);

// PayMongo webhook (public; gated by signature verification in the controller).
Route::post('/webhooks/paymongo', [OrderController::class, 'paymongoWebhook']);

// Products catalogue (menu is public; editing requires admin/editor).
Route::get('/products', [ProductController::class, 'index']);

// Landing/franchise CMS blob (public read).
Route::get('/site-content', [SiteContentController::class, 'show']);

// Active voucher codes for the checkout preview (public; the order endpoint
// still re-validates server-side).
Route::get('/vouchers/active', [VoucherController::class, 'active']);

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

    Route::get('/vouchers', [VoucherController::class, 'index']);
    Route::post('/vouchers/sync', [VoucherController::class, 'sync']);

    Route::post('/stores/sync', [StoreController::class, 'sync']);

    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/mine', [OrderController::class, 'mine']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders/{id}/pay', [OrderController::class, 'pay']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::patch('/orders/{id}/status', [OrderController::class, 'updateStatus']);
    Route::patch('/orders/{id}/payment-status', [OrderController::class, 'updatePaymentStatus']);

    Route::get('/applications', [ApplicationController::class, 'index']);
    Route::get('/resumes/url', [ApplicationController::class, 'resumeUrl']);

    // Current user's effective role (any signed-in user).
    Route::get('/me', [UserController::class, 'me']);
    // User management — admin only (enforced in the controller).
    Route::get('/users', [UserController::class, 'index']);
    Route::patch('/users/role', [UserController::class, 'updateRole']);
    Route::post('/users/rename', [UserController::class, 'renameEmail']);
});

// Legacy Sanctum-protected routes.
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

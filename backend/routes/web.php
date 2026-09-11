<?php

use App\Http\Controllers\Admin\AuditLogController as AdminAuditLogController;
use App\Http\Controllers\Admin\AssistantChatController as AdminAssistantChatController;
use App\Http\Controllers\Admin\ContactController as AdminContactController;
use App\Http\Controllers\Admin\CustomCakeController as AdminCustomCakeController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\SiteContentController as AdminSiteContentController;
use App\Http\Controllers\Admin\StoreController as AdminStoreController;
use App\Http\Controllers\Admin\SiteRatingController as AdminSiteRatingController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\VoucherController as AdminVoucherController;
use App\Http\Controllers\AboutController;
use App\Http\Controllers\Auth\CompleteProfileController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegistrationController;
use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CustomCakeController;
use App\Http\Controllers\FranchiseController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\MyOrdersController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\SiteRatingController;
use App\Http\Controllers\StoresController;
use Illuminate\Support\Facades\Route;

// Blade migration — routes ported from the SPA land here (see CLAUDE.md).
// routes/api.php is trimmed in lockstep as each page moves.

Route::get('/', [LandingController::class, 'index']);
Route::get('/menu', [MenuController::class, 'index'])->name('menu');
Route::get('/stores', [StoresController::class, 'index'])->name('stores');
Route::get('/franchise', [FranchiseController::class, 'index'])->name('franchise');
Route::get('/about', [AboutController::class, 'index'])->name('about');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

// Legal pages — linked from the site footer.
Route::get('/privacy-policy', [LegalController::class, 'privacy'])->name('privacy-policy');
Route::get('/terms-of-service', [LegalController::class, 'terms'])->name('terms-of-service');
Route::get('/data-deletion', [LegalController::class, 'dataDeletion'])->name('data-deletion');

// Custom cake inquiry wizard (public — guests can ask for a quote).
Route::get('/custom-cake', [CustomCakeController::class, 'show'])->name('custom-cake');
Route::post('/custom-cake', [CustomCakeController::class, 'store'])->name('custom-cake.store');

// Contact form (public — guests can send a message too).
Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:5,1')->name('contact.store');

// Lightweight, voluntary 1-5 star site-experience prompt on the landing page.
Route::post('/site-rating', [SiteRatingController::class, 'store'])
    ->middleware('throttle:5,1')->name('site-rating.store');

// Shop assistant widget (public — guests + signed-in). 404s when GROQ_API_KEY
// is unset. Throttled per IP since it's on every marketing page and unauthed.
Route::post('/assistant/chat', [\App\Http\Controllers\AssistantController::class, 'chat'])
    ->middleware('throttle:20,1')->name('assistant.chat');

// The SPA's Dashboard.jsx was literally `return <Menu/>` — signed-in customers
// land on the menu. Kept as a redirect because old links/OAuth callbacks still
// point at /dashboard.
Route::redirect('/dashboard', '/menu');

Route::get('/login', [SessionController::class, 'create'])->name('login');
Route::post('/login', [SessionController::class, 'store'])->middleware('throttle:5,1');
Route::post('/logout', [SessionController::class, 'destroy'])->name('logout');

Route::get('/register', [RegistrationController::class, 'create'])->name('register');
Route::post('/register', [RegistrationController::class, 'store'])->middleware('throttle:5,1');

// Re-send the signup confirmation email (from the login page's "not confirmed
// yet" prompt) — see Auth\RegistrationController::resendConfirmation.
Route::post('/resend-confirmation', [RegistrationController::class, 'resendConfirmation'])
    ->name('confirmation.resend')->middleware('throttle:5,1');

// Forgot/reset password (port of the SPA's ForgotPassword.jsx/ResetPassword.jsx):
// Supabase emails a recovery link that lands on /reset-password with the token
// in the URL #fragment — see Auth\PasswordResetController.
Route::get('/forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
Route::post('/forgot-password', [PasswordResetController::class, 'email'])->name('password.email')->middleware('throttle:5,1');
Route::get('/reset-password', [PasswordResetController::class, 'reset'])->name('password.reset');
Route::post('/reset-password', [PasswordResetController::class, 'update'])->name('password.update');

// Social sign-in (port of the SPA's supabase.auth.signInWithOAuth): redirect
// out to Supabase's authorize endpoint, come back to /auth/callback where the
// tokens arrive in the URL #fragment (never sent to the server), and let that
// page's JS POST them in so the session is established server-side — the
// browser never keeps the Supabase JWT, same as the password login.
Route::get('/auth/{provider}/redirect', [SessionController::class, 'oauthRedirect'])
    ->whereIn('provider', ['google', 'facebook'])->name('oauth.redirect');
Route::get('/auth/callback', [SessionController::class, 'oauthCallback'])->name('oauth.callback');
Route::post('/auth/callback', [SessionController::class, 'oauthStore'])->name('oauth.store');

// Public — renders a PNG, no session needed to view a QR code.
Route::get('/checkout/qr', [CheckoutController::class, 'qr'])->name('checkout.qr');

Route::middleware('supabase.session')->group(function () {
    // Phone-number intake for OAuth sign-ups — EnsureSupabaseSession redirects
    // customers without a contact number here (and only here).
    Route::get('/complete-profile', [CompleteProfileController::class, 'show'])->name('complete-profile');
    Route::post('/complete-profile', [CompleteProfileController::class, 'store'])->name('complete-profile.store');

    Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout');
    Route::post('/checkout', [CheckoutController::class, 'store'])
        ->middleware('throttle:checkout')->name('checkout.store');
    Route::get('/my-orders', [MyOrdersController::class, 'index'])->name('my-orders');

    // Account settings (port of Profile.jsx) — two forms, two POSTs.
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::post('/profile/info', [ProfileController::class, 'updateInfo'])->name('profile.info');
    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // Admin shell (navy sidebar, matching the old AdminDashboard.jsx design).
    // Products, Site Content, Stores, and Vouchers are fully working; the rest
    // are honest "coming soon" placeholders so the sidebar looks/navigates
    // like the original instead of 404ing on unported sections.
    // The separate /admin panel is folded into the Site Editor shell (its
    // Admin sidebar group). Route name kept so landingRoute() and the menu
    // header badges keep working.
    Route::get('/admin', fn () => redirect()->route('admin.content'))->name('admin.dashboard');

    // Custom cake inquiries — staff review + quote workflow (new/quoted/closed).
    Route::get('/admin/custom-cakes', [AdminCustomCakeController::class, 'index'])->name('admin.custom-cakes');
    Route::post('/admin/custom-cakes/{customCakeRequest}/status', [AdminCustomCakeController::class, 'updateStatus'])->name('admin.custom-cakes.status');
    Route::get('/admin/custom-cakes/{customCakeRequest}/reference', [AdminCustomCakeController::class, 'reference'])->name('admin.custom-cakes.reference');

    // Contact form submissions — staff review queue (new/read/replied).
    Route::get('/admin/contact-messages', [AdminContactController::class, 'index'])->name('admin.contact-messages');
    Route::post('/admin/contact-messages/{contactMessage}/status', [AdminContactController::class, 'updateStatus'])->name('admin.contact-messages.status');

    // Public Moymoy assistant transcripts â€” grouped by browser conversation.
    Route::get('/admin/assistant-chats', [AdminAssistantChatController::class, 'index'])->name('admin.assistant-chats');

    // Rating prompt controls + visitor rating report.
    Route::get('/admin/ratings', [AdminSiteRatingController::class, 'index'])->name('admin.ratings');
    Route::put('/admin/ratings/settings', [AdminSiteRatingController::class, 'updateSettings'])->name('admin.ratings.settings');

    // Users / role manager (port of AdminUsers.jsx — admin only). One POST
    // saves a row's role + extra access grants together (the Edit modal).
    Route::get('/admin/users', [AdminUserController::class, 'index'])->name('admin.users');
    Route::post('/admin/users/update', [AdminUserController::class, 'update'])->name('admin.users.update');

    // Orders queue (Site Editor shell, Admin group).
    Route::get('/admin/orders', [AdminOrderController::class, 'index'])->name('admin.orders');
    Route::post('/admin/orders/{id}/status', [AdminOrderController::class, 'updateStatus'])->name('admin.orders.status');
    Route::post('/admin/orders/{id}/payment', [AdminOrderController::class, 'updatePayment'])->name('admin.orders.payment');

    // Site Content CMS (replaces the old AdminContent.jsx mega-editor).
    // The upload route backs the editor's image "Upload" buttons — same
    // controller as the API's POST /api/uploads, just session-authed here.
    Route::post('/admin/uploads', [\App\Http\Controllers\UploadController::class, 'store'])->name('admin.uploads');
    Route::get('/admin/content', [AdminSiteContentController::class, 'edit'])->name('admin.content');
    Route::put('/admin/content', [AdminSiteContentController::class, 'update'])->name('admin.content.update');
    // Live-preview draft: stashes the current form in the session so the
    // preview iframe (?preview=1) reflects unsaved edits. Does not touch the DB.
    Route::post('/admin/content/preview', [AdminSiteContentController::class, 'preview'])->name('admin.content.preview');
    Route::post('/admin/content/categories', [AdminSiteContentController::class, 'saveCategories'])->name('admin.content.categories');
    Route::post('/admin/content/categories/{category}/rename', [AdminSiteContentController::class, 'renameCategory'])->name('admin.content.categories.rename');
    Route::post('/admin/content/categories/{category}/delete', [AdminSiteContentController::class, 'deleteCategory'])->name('admin.content.categories.delete');

    // Products — the Site Editor's card-grid bulk editor (edit everything,
    // one "Save changes"; removed cards are archived), like the old SPA.
    Route::get('/admin/products', [AdminProductController::class, 'index'])->name('admin.products.index');
    Route::post('/admin/products/sync', [AdminProductController::class, 'sync'])->name('admin.products.sync');

    // Stores & Vouchers — same card-grid bulk editors as Products (edit
    // everything, one "Save changes"; removed cards are deleted).
    Route::get('/admin/stores', [AdminStoreController::class, 'index'])->name('admin.stores.index');
    Route::post('/admin/stores/sync', [AdminStoreController::class, 'sync'])->name('admin.stores.sync');
    Route::put('/admin/stores/page-header', [AdminSiteContentController::class, 'updateStoresPage'])->name('admin.stores.page-header');
    Route::post('/admin/stores/page-header/preview', [AdminSiteContentController::class, 'previewStoresPage'])->name('admin.stores.page-header.preview');

    Route::get('/admin/vouchers', [AdminVoucherController::class, 'index'])->name('admin.vouchers.index');
    Route::post('/admin/vouchers/sync', [AdminVoucherController::class, 'sync'])->name('admin.vouchers.sync');

    // Audit Log — read-only history of the staff actions above (admin only).
    Route::get('/admin/audit-log', [AdminAuditLogController::class, 'index'])->name('admin.audit-log');
});

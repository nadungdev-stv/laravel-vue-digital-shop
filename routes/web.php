<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\OrdersController as AdminOrdersController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\VisitorController;
use App\Http\Controllers\Admin\AccountStockController;
use App\Http\Controllers\Admin\ChatController;
use App\Http\Controllers\Admin\LayoutController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\QrGeneratorController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\SepayWebhookController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\WishlistController;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/about', [HomeController::class, 'about'])->name('about');
Route::get('/terms', [HomeController::class, 'terms'])->name('terms');

// Info Pages
Route::get('/support', fn() => Inertia::render('Support'))->name('support');
Route::get('/buying-guide', fn() => Inertia::render('BuyingGuide'))->name('buying-guide');
Route::get('/return-policy', fn() => Inertia::render('ReturnPolicy'))->name('return-policy');
Route::get('/terms-of-service', fn() => Inertia::render('TermsOfService'))->name('terms-of-service');
Route::get('/faq', fn() => Inertia::render('Faq'))->name('faq');
Route::get('/contact', [ContactController::class, 'index'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');

// Public Chat API
use App\Http\Controllers\PublicChatController;
Route::prefix('api/chat')->group(function () {
    Route::post('/start', [PublicChatController::class, 'startSession'])->name('chat.start');
    Route::post('/send', [PublicChatController::class, 'sendMessage'])->name('chat.send');
    Route::get('/messages', [PublicChatController::class, 'getMessages'])->name('chat.messages');
});

// Auth Routes
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->name('login.post');
    Route::get('register', [AuthController::class, 'showRegistrationForm'])->name('register');
    Route::post('register', [AuthController::class, 'register'])->name('register.post');

    Route::get('forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [ForgotPasswordController::class, 'store'])->name('password.email');
    Route::get('reset-password/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [ResetPasswordController::class, 'store'])->name('password.store');
});
Route::post('logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// User Dashboard Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/account', [UserController::class, 'profile'])->name('user.profile');
    Route::get('/account2', [UserController::class, 'profile2'])->name('user.profile2');
    Route::post('/account/profile', [UserController::class, 'updateProfile'])->name('user.profile.update');
    Route::post('/account/password', [UserController::class, 'updatePassword'])->name('user.password.update');

    Route::get('/wallet', [WalletController::class, 'index'])->name('user.wallet');
    Route::post('/wallet/withdraw', [WalletController::class, 'withdraw'])->name('user.wallet.withdraw');

    Route::get('/orders', [OrderController::class, 'index'])->name('user.orders');
    Route::get('/order-detail', function (\Illuminate\Http\Request $request) {
        return redirect()->route('user.orders.show', ['code' => $request->code]);
    });
    Route::post('/orders/{code}/confirm', [OrderController::class, 'confirmPayment'])->name('user.orders.confirm');

    // Wishlist Routes
    Route::get('/wishlist', [WishlistController::class, 'index'])->name('user.wishlist');
    Route::post('/wishlist/toggle', [WishlistController::class, 'toggle'])->name('user.wishlist.toggle');

    // Notification API Routes
    Route::post('/api/notifications/mark-all-read', [UserController::class, 'markAllNotificationsRead'])->name('api.notifications.markAllRead');
    Route::post('/api/notifications/{id}/mark-read', [UserController::class, 'markNotificationRead'])->name('api.notifications.markRead');
});

// Product Routes
Route::get('products', [ProductController::class, 'index'])->name('products.index');
Route::get('api/products', [ProductController::class, 'apiIndex'])->name('products.api'); // JSON endpoint for load more
Route::get('api/search', [ProductController::class, 'search'])->name('api.search');
Route::get('products/{category:slug}', [ProductController::class, 'category'])->name('products.category');
// Cart Routes
Route::get('cart', [CartController::class, 'index'])->name('cart.index');
Route::post('cart/add', [CartController::class, 'add'])->name('cart.add');
Route::post('cart/buy-now', [CartController::class, 'buyNow'])->name('cart.buyNow');
Route::get('cart/prepare-checkout', [CartController::class, 'prepareCheckout'])->name('cart.prepareCheckout');
Route::post('cart/update', [CartController::class, 'update'])->name('cart.update');
Route::post('cart/remove', [CartController::class, 'remove'])->name('cart.remove');
Route::post('cart/apply-coupon', [CartController::class, 'applyCoupon'])->name('cart.coupon.apply');
Route::post('cart/remove-coupon', [CartController::class, 'removeCoupon'])->name('cart.coupon.remove');

// Checkout Routes
Route::get('checkout', [CheckoutController::class, 'index'])->name('checkout.index');
Route::post('checkout', [CheckoutController::class, 'store'])->name('checkout.store');

// Order View (allow both logged-in users and guests to view orders)
Route::get('/orders/{code}', [OrderController::class, 'show'])->name('user.orders.show');
// Guest order payment confirmation (public route)
Route::post('/orders/{code}/confirm-guest', [OrderController::class, 'confirmPaymentGuest'])->name('orders.confirm.guest');

// Admin Routes
Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('admin.dashboard');

    // Orders Management
    Route::get('/orders', [AdminOrdersController::class, 'index'])->name('admin.orders.index');
    Route::get('/orders/{id}', [AdminOrdersController::class, 'show'])->name('admin.orders.show');
    Route::post('/orders/{id}/update-status', [AdminOrdersController::class, 'updateStatus'])->name('admin.orders.updateStatus');
    Route::post('/orders/{id}/quick-action', [AdminOrdersController::class, 'quickAction'])->name('admin.orders.quickAction');
    Route::post('/orders/{id}/deliver-account', [AdminOrdersController::class, 'deliverAccount'])->name('admin.orders.deliverAccount');

    // Products Management
    Route::get('/products', [AdminProductController::class, 'index'])->name('admin.products.index');
    Route::get('/products/create', [AdminProductController::class, 'create'])->name('admin.products.create');
    Route::post('/products', [AdminProductController::class, 'store'])->name('admin.products.store');
    Route::post('/products/{product}/toggle-featured', [AdminProductController::class, 'toggleFeatured'])->name('admin.products.toggleFeatured');
    Route::get('/products/{product}/edit', [AdminProductController::class, 'edit'])->name('admin.products.edit');
    Route::put('/products/{product}', [AdminProductController::class, 'update'])->name('admin.products.update');
    Route::delete('/products/{product}', [AdminProductController::class, 'destroy'])->name('admin.products.destroy');
    Route::patch('/product-variants/{variant}', [AdminProductController::class, 'updateVariant'])->name('admin.product-variants.update');

    // Users Management
    Route::get('/users', [AdminUserController::class, 'index'])->name('admin.users.index');
    Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('admin.users.show');
    Route::post('/users/update', [AdminUserController::class, 'update'])->name('admin.users.update');
    Route::post('/users/update-balance', [AdminUserController::class, 'updateBalance'])->name('admin.users.updateBalance');
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('admin.users.destroy');

    // Coupons Management
    Route::get('/coupons', [CouponController::class, 'index'])->name('admin.coupons.index');
    Route::post('/coupons', [CouponController::class, 'store'])->name('admin.coupons.store');
    Route::put('/coupons/{coupon}', [CouponController::class, 'update'])->name('admin.coupons.update');
    Route::delete('/coupons/{coupon}', [CouponController::class, 'destroy'])->name('admin.coupons.destroy');

    // Reviews Management
    Route::get('/reviews', [ReviewController::class, 'index'])->name('admin.reviews.index');
    Route::post('/reviews/{id}/status', [ReviewController::class, 'updateStatus'])->name('admin.reviews.updateStatus');
    Route::post('/reviews/{id}/reply', [ReviewController::class, 'reply'])->name('admin.reviews.reply');
    Route::delete('/reviews/{id}', [ReviewController::class, 'destroy'])->name('admin.reviews.destroy');

    // Notifications Management
    Route::get('/notifications', [NotificationController::class, 'index'])->name('admin.notifications.index');
    Route::post('/notifications/send', [NotificationController::class, 'send'])->name('admin.notifications.send');
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])->name('admin.notifications.destroy');

    // Visitors Analytics
    Route::get('/visitors', [VisitorController::class, 'index'])->name('admin.visitors.index');

    // Account Stock Management
    Route::get('/accounts', [AccountStockController::class, 'index'])->name('admin.accounts.index');
    Route::get('/accounts/create', [AccountStockController::class, 'create'])->name('admin.accounts.create');
    Route::post('/accounts', [AccountStockController::class, 'store'])->name('admin.accounts.store');
    Route::get('/accounts/import', [AccountStockController::class, 'import'])->name('admin.accounts.import');
    Route::post('/accounts/import', [AccountStockController::class, 'importPost'])->name('admin.accounts.importPost');
    Route::post('/accounts/inline-update', [AccountStockController::class, 'inlineUpdate'])->name('admin.accounts.inlineUpdate');
    Route::post('/accounts/bulk-action', [AccountStockController::class, 'bulkAction'])->name('admin.accounts.bulkAction');
    Route::post('/accounts/bulk-export', [AccountStockController::class, 'bulkExport'])->name('admin.accounts.bulkExport');
    Route::get('/accounts/export-all', [AccountStockController::class, 'exportAll'])->name('admin.accounts.exportAll');
    Route::delete('/accounts/{id}', [AccountStockController::class, 'destroy'])->name('admin.accounts.destroy');

    // Chat Support
    Route::get('/chats', [ChatController::class, 'index'])->name('admin.chats.index');
    Route::get('/chats/{id}', [ChatController::class, 'show'])->name('admin.chats.show');
    Route::post('/chats/{id}/send', [ChatController::class, 'sendMessage'])->name('admin.chats.send');
    Route::post('/chats/{id}/close', [ChatController::class, 'closeChat'])->name('admin.chats.close');
    Route::post('/chats/{id}/reopen', [ChatController::class, 'reopenChat'])->name('admin.chats.reopen');
    Route::get('/chats/{id}/messages', [ChatController::class, 'pollMessages'])->name('admin.chats.poll');

    // Layout/Banner Management
    Route::get('/layout', [LayoutController::class, 'index'])->name('admin.layout.index');
    Route::post('/layout/category-order', [LayoutController::class, 'updateCategoryOrder'])->name('admin.layout.categoryOrder');
    Route::post('/layout/banner-order', [LayoutController::class, 'updateBannerOrder'])->name('admin.layout.bannerOrder');
    Route::post('/layout/banner', [LayoutController::class, 'createBanner'])->name('admin.layout.createBanner');
    Route::post('/layout/banner/{id}', [LayoutController::class, 'updateBanner'])->name('admin.layout.updateBanner');
    Route::delete('/layout/banner/{id}', [LayoutController::class, 'deleteBanner'])->name('admin.layout.deleteBanner');

    // Categories Management

    Route::post('/categories', [CategoryController::class, 'store'])->name('admin.categories.store');

    Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('admin.categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('admin.categories.destroy');

    // Settings
    Route::get('/settings', [SettingController::class, 'index'])->name('admin.settings.index');
    Route::post('/settings', [SettingController::class, 'update'])->name('admin.settings.update');

    // QR Generator
    Route::get('/qr-generator', [QrGeneratorController::class, 'index'])->name('admin.qr.index');
});

Route::get('{slug}', [ProductController::class, 'show'])->name('products.show');


// Webhooks (Disable CSRF for this route via middleware exclusion or use api.php)
// Since we are using web.php key convenience, we need to exclude this route from CSRF.
// In Laravel 11, do this in bootstrap/app.php. But if older or using standard route method:
Route::post('/api/sepay/webhook', [SepayWebhookController::class, 'handle'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->name('webhook.sepay');

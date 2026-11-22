<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\Seller;
use App\Http\Controllers\StoreController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| PUBLIC PAGES (anyone can visit)
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/marketplace', [MarketplaceController::class, 'index'])->name('marketplace');
Route::get('/artworks/{artwork:slug}', [MarketplaceController::class, 'show'])->name('artworks.show');
Route::get('/store/{store:slug}', [StoreController::class, 'show'])->name('stores.show');

/*
|--------------------------------------------------------------------------
| GUEST ONLY (login / register / forgot password)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/forgot-password', [AuthController::class, 'showForgot'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

/*
|--------------------------------------------------------------------------
| LOGGED-IN USERS (every user is a buyer, and can open a store)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Cart
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/{artwork}', [CartController::class, 'add'])->name('cart.add');
    Route::delete('/cart/items/{cartItem}', [CartController::class, 'remove'])->name('cart.remove');

    // Checkout and orders
    Route::post('/checkout', [OrderController::class, 'checkout'])->name('checkout');
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/pay', [OrderController::class, 'pay'])->name('orders.pay'); // start GCash payment
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');

    // GCash payment
    Route::get('/orders/{order}/gcash-test', [PaymentController::class, 'testPage'])->name('payments.gcash.test');   // test simulator
    Route::post('/orders/{order}/gcash-test', [PaymentController::class, 'testPay'])->name('payments.gcash.testPay');
    Route::get('/payments/{payment}/gcash/return', [PaymentController::class, 'gcashReturn'])->name('payments.gcash.return'); // back from real GCash
    Route::get('/payments/{payment}/gcash/cancel', [PaymentController::class, 'gcashCancel'])->name('payments.gcash.cancel');

    // My purchases + one-time downloads + reviews
    Route::get('/purchases', [PurchaseController::class, 'index'])->name('purchases.index');
    Route::post('/purchases/{orderItem}/download', [PurchaseController::class, 'download'])->name('purchases.download');
    Route::post('/reviews/{orderItem}', [ReviewController::class, 'save'])->name('reviews.save');

    // Notification center
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.readAll');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');

    // Create / edit MY store
    Route::get('/my-store/create', [StoreController::class, 'create'])->name('store.create');
    Route::post('/my-store', [StoreController::class, 'store'])->name('store.store');
    Route::get('/my-store/edit', [StoreController::class, 'edit'])->name('store.edit');
    Route::put('/my-store', [StoreController::class, 'update'])->name('store.update');

    /*
    | SELLER AREA (needs a store) -> /seller/...
    */
    Route::middleware('seller')->prefix('seller')->name('seller.')->group(function () {
        Route::get('/', [Seller\DashboardController::class, 'index'])->name('dashboard');
        Route::get('/sales', [Seller\DashboardController::class, 'sales'])->name('sales');
        Route::resource('artworks', Seller\ArtworkController::class)->except('show');
    });

    /*
    | ADMIN AREA -> /admin/...
    */
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

        Route::get('/users', [Admin\UserController::class, 'index'])->name('users.index');
        Route::get('/users/{user}/edit', [Admin\UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [Admin\UserController::class, 'update'])->name('users.update');
        Route::put('/users/{user}/password', [Admin\UserController::class, 'resetPassword'])->name('users.password');
        Route::delete('/users/{user}', [Admin\UserController::class, 'destroy'])->name('users.destroy');

        Route::get('/artworks', [Admin\ArtworkController::class, 'index'])->name('artworks.index');
        Route::get('/artworks/{artwork}', [Admin\ArtworkController::class, 'show'])->name('artworks.show');
        Route::get('/artworks/{artwork}/file', [Admin\ArtworkController::class, 'file'])->name('artworks.file');
        Route::post('/artworks/{artwork}/approve', [Admin\ArtworkController::class, 'approve'])->name('artworks.approve');
        Route::post('/artworks/{artwork}/reject', [Admin\ArtworkController::class, 'reject'])->name('artworks.reject');
        Route::delete('/artworks/{artwork}', [Admin\ArtworkController::class, 'destroy'])->name('artworks.destroy');

        Route::get('/orders', [Admin\OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [Admin\OrderController::class, 'show'])->name('orders.show');
        Route::get('/downloads', [Admin\OrderController::class, 'downloads'])->name('downloads.index');

        Route::get('/categories', [Admin\CategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [Admin\CategoryController::class, 'store'])->name('categories.store');
        Route::delete('/categories/{category}', [Admin\CategoryController::class, 'destroy'])->name('categories.destroy');
    });
});

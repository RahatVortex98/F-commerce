<?php

use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CustomerAccountController;
use App\Http\Controllers\CustomerAuthController;
use App\Http\Controllers\PathaoLocationController;
use App\Http\Controllers\PhoneVerificationController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\StoreInquiryController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ShopController::class, 'index'])->name('shop');
Route::get('/product/{product}', [ShopController::class, 'show'])->name('product.show');
Route::middleware('guest:customer')->group(function (): void {
    Route::get('/login', [CustomerAuthController::class, 'createLogin'])->name('customer.login');
    Route::post('/login', [CustomerAuthController::class, 'login'])->middleware('throttle:customer-login')->name('customer.login.store');
    Route::get('/register', [CustomerAuthController::class, 'createRegistration'])->name('customer.register');
    Route::post('/register', [CustomerAuthController::class, 'register'])->middleware('throttle:customer-login')->name('customer.register.store');
});
Route::post('/logout', [CustomerAuthController::class, 'logout'])->middleware('auth:customer')->name('customer.logout');
Route::middleware('auth:customer')->prefix('account')->name('account.')->group(function (): void {
    Route::get('/', [CustomerAccountController::class, 'index'])->name('dashboard');
    Route::get('/orders/{order}', [CustomerAccountController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/received', [CustomerAccountController::class, 'confirmReceived'])->name('orders.received');
});
Route::get('/checkout/{product}', [CheckoutController::class, 'create'])->middleware('auth:customer')->name('checkout');
Route::post('/checkout/{product}', [CheckoutController::class, 'store'])->middleware(['auth:customer', 'throttle:10,1'])->name('checkout.store');
Route::get('/order/verify/{token}', [PhoneVerificationController::class, 'show'])->name('order.verify');
Route::post('/order/verify/{token}', [PhoneVerificationController::class, 'store'])->middleware('throttle:10,1')->name('order.verify.store');
Route::post('/order/verify/{token}/resend', [PhoneVerificationController::class, 'resend'])->middleware('throttle:3,10')->name('order.verify.resend');
Route::get('/order/{token}/success', [PhoneVerificationController::class, 'success'])->name('order.success');
Route::prefix('pathao')->middleware('throttle:30,1')->group(function (): void {
    Route::get('/cities', [PathaoLocationController::class, 'cities']);
    Route::get('/cities/{cityId}/zones', [PathaoLocationController::class, 'zones']);
    Route::get('/zones/{zoneId}/areas', [PathaoLocationController::class, 'areas']);
});
Route::view('/trust', 'trust')->name('trust');
Route::post('/inquiries', [StoreInquiryController::class, 'store'])->middleware('throttle:5,10')->name('inquiries.store');

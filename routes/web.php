<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BuildController;
use App\Http\Controllers\ShopController;
use Illuminate\Support\Facades\Route;

Route::get('/danh-sach/{kind}', [\App\Http\Controllers\CollectionController::class, 'index'])->name('collection');
Route::post('/danh-sach/{kind}/{product}', [\App\Http\Controllers\CollectionController::class, 'toggle'])->name('collection.toggle');

Route::get('/', [ShopController::class, 'home'])->name('home');
Route::get('/san-pham', [ShopController::class, 'catalog'])->name('catalog');
Route::get('/san-pham/{product}', [ShopController::class, 'product'])->name('product');
Route::get('/lien-he', [ShopController::class, 'contact'])->name('contact');
Route::get('/gio-hang', [ShopController::class, 'cart'])->name('cart');
Route::post('/gio-hang/{product}', [ShopController::class, 'add'])->name('cart.add');
Route::patch('/gio-hang/{product}', [ShopController::class, 'update'])->name('cart.update');
Route::get('/build-pc', [BuildController::class, 'index'])->name('builder');
Route::post('/build-pc/check', [BuildController::class, 'check'])->name('builder.check');
Route::post('/build-pc/cart', [BuildController::class, 'cart'])->name('builder.cart');
Route::middleware('guest')->group(function () {
    Route::get('/dang-nhap', [AuthController::class, 'login'])->name('login');
    Route::post('/dang-nhap', [AuthController::class, 'authenticate'])->middleware('throttle:10,1');
    Route::get('/dang-ky', [AuthController::class, 'register'])->name('register');
    Route::post('/dang-ky', [AuthController::class, 'store'])->middleware('throttle:10,1');
});
Route::middleware('auth')->group(function () {
    Route::post('/dang-xuat', [AuthController::class, 'logout'])->name('logout');
    Route::get('/thanh-toan', [ShopController::class, 'checkout'])->name('checkout');
    Route::post('/thanh-toan', [ShopController::class, 'place'])->name('checkout.place')->middleware('throttle:20,1');
    Route::get('/don-hang', [ShopController::class, 'orders'])->name('orders');
    Route::get('/don-hang/{order}', [ShopController::class, 'order'])->name('orders.show');
    Route::post('/don-hang/{order}/huy', [ShopController::class, 'cancel'])->name('orders.cancel');
    Route::get('/cau-hinh', [BuildController::class, 'list'])->name('builds');
    Route::post('/cau-hinh', [BuildController::class, 'save'])->name('builds.save');
    Route::delete('/cau-hinh/{build}', [BuildController::class, 'destroy'])->name('builds.delete');
});
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/danh-sach/{kind}', [\App\Http\Controllers\CollectionController::class, 'index'])->name('collection');
Route::post('/danh-sach/{kind}/{product}', [\App\Http\Controllers\CollectionController::class, 'toggle'])->name('collection.toggle');

Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/products', [AdminController::class, 'products'])->name('products');
    Route::get('/products/create', [AdminController::class, 'create'])->name('products.create');
    Route::post('/products', [AdminController::class, 'store'])->name('products.store');
    Route::get('/products/{product}/edit', [AdminController::class, 'edit'])->name('products.edit');
    Route::put('/products/{product}', [AdminController::class, 'update'])->name('products.update');
    Route::delete('/products/{product}', [AdminController::class, 'destroy'])->name('products.delete');
    Route::get('/categories', [AdminController::class, 'categories'])->name('categories');
    Route::post('/categories', [AdminController::class, 'categorySave'])->name('categories.store');
    Route::put('/categories/{category}', [AdminController::class, 'categorySave'])->name('categories.update');
    Route::delete('/categories/{category}', [AdminController::class, 'categoryDelete'])->name('categories.delete');
    Route::get('/orders', [AdminController::class, 'orders'])->name('orders');
    Route::get('/orders/{order}', [AdminController::class, 'order'])->name('orders.show');
    Route::patch('/orders/{order}',[AdminController::class, 'status'])->name('orders.status');
});

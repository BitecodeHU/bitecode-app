<?php

use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/


Route::get('/', function () {
    return view('welcome');
})->name('welcome');

Route::get('lang/{lang}', function($lang) {
    app()->setLocale($lang);
    session()->put('locale', $lang);

    return redirect()->route('welcome');
})->name('lang');

Route::get('profile-lang/{lang}', function($lang) {
    app()->setLocale($lang);
    session()->put('locale', $lang);

    return redirect()->route('profile.edit');
})->name('profile-lang');


Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/user-search', [UserController::class, 'search'])->name('users.search');
    Route::get('/add-user', [UserController::class, 'add'])->name('users.add');
    Route::post('/add-user', [UserController::class, 'create'])->name('users.create');

    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/create-invoice', [InvoiceController::class, 'createInvoice'])->name('invoices.create-invoice');
    Route::post('/invoices', [InvoiceController::class, 'store'])->name('invoices.store');
    Route::get('/invoices/{invoice_id}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::get('/invoice-search', [InvoiceController::class, 'search'])->name('invoices.search');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::patch('/settings', [SettingsController::class, 'checkUpdate'])->name('settings.check_update');
    Route::patch('/settings/update', [SettingsController::class, 'update'])->name('settings.update');
    Route::get('settings-lang/{lang}', function($lang) {
        app()->setLocale($lang);
        session()->put('locale', $lang);
    
        return redirect()->route('settings.index');
    })->name('settings-lang');
});

Route::get('/invoice/{invoice_id}', [InvoiceController::class, 'show'])->name('invoice.show');

Route::get('/checkout/{invoice_id}', [StripeController::class, 'checkout'])->name('checkout');
Route::post('/checkout/{invoice_id}', [StripeController::class, 'processCheckout']);
Route::get('/success/{invoice_id}', [StripeController::class, 'success'])->name('success');
Route::get('/cancel', [StripeController::class, 'cancel'])->name('cancel');

require __DIR__.'/auth.php';

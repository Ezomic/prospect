<?php

use App\Http\Controllers\CompanyController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::get('companies', [CompanyController::class, 'index'])->name('companies.index');
});

require __DIR__.'/settings.php';

<?php

use App\Http\Controllers\CompanyController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::resource('companies', CompanyController::class)->except('show');
});

require __DIR__.'/settings.php';

<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LetterController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('companies', CompanyController::class);

    Route::post('companies/{company}/letters', [LetterController::class, 'store'])->name('letters.store');
    Route::post('letters/{letter}/send', [LetterController::class, 'send'])->name('letters.send');
    Route::get('letters/{letter}/pdf', [LetterController::class, 'pdf'])->name('letters.pdf');
    Route::resource('letters', LetterController::class)->only(['edit', 'update', 'destroy']);
});

require __DIR__.'/settings.php';

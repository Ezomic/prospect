<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CompanyImportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FollowUpController;
use App\Http\Controllers\InteractionController;
use App\Http\Controllers\LetterController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('Welcome'))->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('follow-ups', [FollowUpController::class, 'index'])->name('follow-ups.index');
    Route::patch('follow-ups/{company}/snooze', [FollowUpController::class, 'snooze'])->name('follow-ups.snooze');

    Route::get('companies/import', [CompanyImportController::class, 'create'])->name('companies.import.create');
    Route::post('companies/import/preview', [CompanyImportController::class, 'preview'])->name('companies.import.preview');
    Route::post('companies/import', [CompanyImportController::class, 'store'])->name('companies.import.store');

    // Both must precede the resource: companies/{company} would otherwise
    // match "export" as an id and 404 on the binding.
    Route::get('companies/export', [CompanyController::class, 'export'])->name('companies.export');
    Route::post('companies/bulk', [CompanyController::class, 'bulk'])->name('companies.bulk');

    Route::resource('companies', CompanyController::class);
    Route::get('companies/{company}/merge', [CompanyController::class, 'merge'])->name('companies.merge');
    Route::post('companies/{company}/merge', [CompanyController::class, 'applyMerge'])->name('companies.merge.apply');
    Route::patch('companies/{company}/status', [CompanyController::class, 'updateStatus'])->name('companies.status');
    Route::patch('companies/{company}/do-not-contact', [CompanyController::class, 'doNotContact'])->name('companies.do-not-contact');
    Route::patch('companies/{company}/follow-up', [CompanyController::class, 'followUp'])->name('companies.follow-up');

    Route::post('companies/{company}/interactions', [InteractionController::class, 'store'])->name('interactions.store');
    Route::delete('interactions/{interaction}', [InteractionController::class, 'destroy'])->name('interactions.destroy');

    Route::post('companies/{company}/letters', [LetterController::class, 'store'])->name('letters.store');
    Route::post('letters/{letter}/send', [LetterController::class, 'send'])->name('letters.send');
    Route::post('letters/{letter}/cancel', [LetterController::class, 'cancel'])->name('letters.cancel');
    Route::post('letters/{letter}/release', [LetterController::class, 'release'])->name('letters.release');
    Route::get('letters/{letter}/pdf', [LetterController::class, 'pdf'])->name('letters.pdf');
    Route::resource('letters', LetterController::class)->only(['edit', 'update', 'destroy']);
});

require __DIR__.'/settings.php';

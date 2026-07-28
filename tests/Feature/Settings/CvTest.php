<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

it('renders the cv settings page with no cv', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('cv.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/Cv')
            ->where('cv', null)
        );
});

it('uploads a cv', function () {
    Storage::fake('local');
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->post(route('cv.update'), [
        'cv' => UploadedFile::fake()->create('resume.pdf', 200, 'application/pdf'),
    ])->assertRedirect(route('cv.edit'));

    $user->refresh();

    expect($user->cv_path)->not->toBeNull()
        ->and($user->cv_original_name)->toBe('resume.pdf');

    Storage::disk('local')->assertExists($user->cv_path);
});

it('rejects a non-pdf cv', function () {
    Storage::fake('local');
    $this->actingAs(User::factory()->create());

    $this->post(route('cv.update'), [
        'cv' => UploadedFile::fake()->create('resume.docx', 200, 'application/msword'),
    ])->assertSessionHasErrors('cv');
});

it('replaces the existing cv and deletes the old file', function () {
    Storage::fake('local');
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->post(route('cv.update'), ['cv' => UploadedFile::fake()->create('first.pdf', 100, 'application/pdf')]);
    $firstPath = $user->refresh()->cv_path;

    $this->post(route('cv.update'), ['cv' => UploadedFile::fake()->create('second.pdf', 100, 'application/pdf')]);
    $user->refresh();

    expect($user->cv_original_name)->toBe('second.pdf');
    Storage::disk('local')->assertMissing($firstPath);
    Storage::disk('local')->assertExists($user->cv_path);
});

it('removes the cv', function () {
    Storage::fake('local');
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->post(route('cv.update'), ['cv' => UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf')]);
    $path = $user->refresh()->cv_path;

    $this->delete(route('cv.destroy'))->assertRedirect(route('cv.edit'));

    $user->refresh();
    expect($user->cv_path)->toBeNull();
    Storage::disk('local')->assertMissing($path);
});

it('downloads the cv', function () {
    Storage::fake('local');
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->post(route('cv.update'), ['cv' => UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf')]);

    $this->get(route('cv.download'))->assertOk();
});
